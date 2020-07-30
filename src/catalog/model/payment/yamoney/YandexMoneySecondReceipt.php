<?php

use YandexCheckout\Client;
use YandexCheckout\Model\PaymentInterface;
use YandexCheckout\Model\Receipt\PaymentMode;
use YandexCheckout\Model\ReceiptCustomer;
use YandexCheckout\Model\ReceiptItem;
use YandexCheckout\Model\ReceiptType;
use YandexCheckout\Model\Settlement;
use YandexCheckout\Request\Receipts\CreatePostReceiptRequest;
use YandexCheckout\Request\Receipts\ReceiptResponseInterface;
use YandexCheckout\Request\Receipts\ReceiptResponseItemInterface;

/**
 * Class YandexMoneySecondReceipt
 */
class YandexMoneySecondReceipt
{
    /**
     * @var
     */
    private $model;

    /**
     * YandexMoneySecondReceipt constructor.
     * @param $model
     */
    public function __construct($model)
    {
        $this->model = $model;
    }

    /**
     * @param $orderInfo
     * @param $status
     */
    public function sendSecondReceipt($orderInfo, $status)
    {
        $this->model->language->load('payment/yamoney');

        $this->model->log("info", "hook second receipt send");

        if (!$this->isNeedSecondReceipt($status)) {
            $this->model->log("info", "Second receipt isn't need");
            return;
        }

        $paymentMethod = $this->model->getPaymentMethod($this->model->config->get('ya_mode'));

        if ($paymentMethod instanceof YandexMoneyPaymentKassa) {

            $client = $this->model->getClient($paymentMethod);

            try{
                $paymentInfo = $this->model->getPaymentByOrderId($paymentMethod, $orderInfo['order_id']);
            } catch (Exception $e) {
                $this->model->log("error", "Get payment info error:" . $e->getMessage());
                return;
            }

            if (!$this->isPaymentInfoValid($paymentInfo)) {
                return;
            }

            try {
                $lastReceipt = $this->getLastReceipt($client, $paymentInfo->getId());
            } catch (Exception $e) {
                $this->model->log("error", "Get last receipt error:" . $e->getMessage());
                return;
            }


            if (empty($lastReceipt)) {
                return;
            }

            $receiptRequest = $this->buildSecondReceipt($lastReceipt, $paymentInfo, $orderInfo);
            if (!empty($receiptRequest)) {

                $this->model->log("info", "Second receipt request data: " . json_encode($receiptRequest->jsonSerialize()));

                try {
                    $response = $client->createReceipt($receiptRequest);
                } catch (Exception $e) {
                    $this->model->log("error", "Request second receipt error: " . $e->getMessage());
                    return;
                }

                $this->addNoteToOrderHistory($response, $orderInfo['order_id'], $status);
                $this->model->log("info", "Request second receipt result: " . json_encode($response->jsonSerialize()));
            }
        }
    }

    /**
     * @param ReceiptResponseInterface $response
     * @param $orderId
     * @param $status
     */
    private function addNoteToOrderHistory($response, $orderId, $status)
    {
        $amount  = $this->getSettlementsAmountSum($response);
        $comment = sprintf($this->model->language->get("kassa_second_receipt_history_info"), $amount);

        if ($this->model->updateOrderHistory($orderId, $status, $comment)) {
            $this->model->log('info', 'Update order history orderId = ' . $orderId);
        } else {
            $this->model->log('error', 'Fail update order history, orderId = ' . $orderId . '. Check DB connection');
        }
    }

    /**
     * @param ReceiptResponseInterface $response
     * @return string
     */
    private function getSettlementsAmountSum($response)
    {
        $amount = 0;

        foreach ($response->getSettlements() as $settlement) {
            $amount += $settlement->getAmount()->getIntegerValue();
        }

        return number_format($amount / 100.0, 2, '.', ' ');
    }

    /**
     * @param PaymentInterface $paymentInfo
     * @return bool
     */
    private function isPaymentInfoValid($paymentInfo)
    {
        if (empty($paymentInfo)) {
            $this->model->log("error", "Fail send second receipt paymentInfo is null: " . print_r($paymentInfo, true));
            return false;
        }

        if ($paymentInfo->getStatus() !== \YandexCheckout\Model\PaymentStatus::SUCCEEDED) {
            $this->model->log("error", "Fail send second receipt payment have incorrect status: " . $paymentInfo->getStatus());
            return false;
        }

        return true;
    }

    /**
     * @param $newStatus
     * @return bool
     */
    private function isNeedSecondReceipt($newStatus)
    {
        if (!$this->isSendReceiptEnable()) {
            return false;
        } elseif (!$this->isSecondReceiptEnable()) {
            return false;
        } elseif ($this->getSecondReceiptStatus() != $newStatus) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    private function isSendReceiptEnable()
    {
        return (bool)$this->model->config->get('ya_kassa_send_receipt');
    }

    /**
     * @return bool
     */
    private function isSecondReceiptEnable()
    {
        return (bool)$this->model->config->get('ya_kassa_second_receipt_enable');
    }

    /**
     * @return int
     */
    public function getSecondReceiptStatus()
    {
        return (int)$this->model->config->get('ya_kassa_second_receipt_status');
    }

    /**
     * @param ReceiptResponseInterface $lastReceipt
     * @param PaymentInterface $paymentInfo
     * @param $orderInfo
     *
     * @return void|CreatePostReceiptRequest
     */
    private function buildSecondReceipt($lastReceipt, $paymentInfo, $orderInfo)
    {
        if ($lastReceipt instanceof ReceiptResponseInterface) {
            if ($lastReceipt->getType() === "refund") {
                return;
            }

            $resendItems = $this->getResendItems($lastReceipt->getItems());

            if (count($resendItems['items']) < 1) {
                $this->model->log("info", "Второй чек не трубется");
                return;
            }

            try {
                $receiptBuilder = CreatePostReceiptRequest::builder();
                $customer = $this->getReceiptCustomer($orderInfo);

                if (empty($customer)) {
                    $this->model->log("info", "Need customer phone or email for second receipt");
                    return;
                }

                $receiptBuilder->setObjectId($paymentInfo->getId())
                    ->setType(ReceiptType::PAYMENT)
                    ->setItems($resendItems['items'])
                    ->setSettlements(
                        array(
                            new Settlement(
                                array(
                                    'type' => 'prepayment',
                                    'amount' => array(
                                        'value' => $resendItems['amount'],
                                        'currency' => 'RUB',
                                    ),
                                )
                            ),
                        )
                    )
                    ->setCustomer($customer)
                    ->setSend(true);

                return $receiptBuilder->build();
            } catch (Exception $e) {
                $this->model->log("error", $e->getMessage() . ". Property name:". $e->getProperty());
            }
        }
    }

    /**
     * @param $orderInfo
     * @return bool|ReceiptCustomer
     */
    private function getReceiptCustomer($orderInfo)
    {
        $customerData = array();

        if (isset($orderInfo['email']) && !empty($orderInfo['email'])) {
            $customerData['email'] = $orderInfo['email'];
        }

        if (isset($orderInfo['telephone']) && !empty($orderInfo['telephone'])) {
            $customerData['phone'] = $orderInfo['telephone'];
        }


        return new ReceiptCustomer($customerData);
    }

    /**
     * @param ReceiptResponseItemInterface[] $items
     *
     * @return array
     */
    private function getResendItems($items)
    {
        $resendItems = array(
            'items'  => array(),
            'amount' => 0,
        );

        foreach ($items as $item) {
            if ( $this->isNeedResendItem($item->getPaymentMode()) ) {
                $item->setPaymentMode(PaymentMode::FULL_PAYMENT);
                $resendItems['items'][] = new ReceiptItem($item->jsonSerialize());
                $resendItems['amount'] += $item->getAmount() / 100.0;
            }
        }

        return $resendItems;
    }

    /**
     * @param $paymentMode
     *
     * @return bool
     */
    private function isNeedResendItem($paymentMode)
    {
        $secondReceiptPaymentModeValid = array (
            PaymentMode::FULL_PREPAYMENT,
//        пока не нужно, возможно в скором времени будем делать и для них.
//        PaymentMode::PARTIAL_PREPAYMENT,
//        PaymentMode::ADVANCE,
        );
        return in_array($paymentMode, $secondReceiptPaymentModeValid);
    }

    /**
     * @param Client $client
     * @param $paymentId
     *
     * @return mixed|ReceiptResponseInterface
     * @throws YandexCheckout\Common\Exceptions\ApiException;
     * @throws YandexCheckout\Common\Exceptions\BadApiRequestException;
     * @throws YandexCheckout\Common\Exceptions\ForbiddenException;
     * @throws YandexCheckout\Common\Exceptions\InternalServerError;
     * @throws YandexCheckout\Common\Exceptions\NotFoundException;
     * @throws YandexCheckout\Common\Exceptions\ResponseProcessingException;
     * @throws YandexCheckout\Common\Exceptions\TooManyRequestsException;n
     * @throws YandexCheckout\Common\Exceptions\UnauthorizedException;
     */
    private function getLastReceipt($client, $paymentId)
    {
        $receipts = $client->getReceipts(array(
            'payment_id' => $paymentId,
        ))->getItems();

        return array_shift($receipts);
    }
}