<?php

require_once dirname(__FILE__).'/yamoney/autoload.php';

/**
 * Class ModelPaymentYaMoney
 *
 * @property-read Url $url
 * @property-read ModelCheckoutOrder $model_checkout_order
 */
class ModelPaymentYaMoney extends Model
{
    private $paymentMethods;

    private $client;

    public function getMethod($address, $total)
    {
        $this->language->load('payment/yamoney');

        $query = $this->db->query(
            "SELECT * FROM `".DB_PREFIX."zone_to_geo_zone` WHERE `geo_zone_id` = '"
            .(int)$this->config->get('ya_idZone')."' AND `country_id` = '".(int)$address['country_id']
            ."' AND (`zone_id` = '".(int)$address['zone_id']."' OR `zone_id` = '0')"
        );

        if ($total == 0) {
            $status = false;
        } elseif (!$this->config->get('ya_idZone')) {
            $status = true;
        } elseif ($query->num_rows) {
            $status = true;
        } else {
            $status = false;
        }

        $method_data = array();
        if ($status) {
            $paymentMethod = $this->getPaymentMethod($this->config->get('ya_mode'));
            if ($paymentMethod->isModeKassa()) {
                $title = $this->config->get('ya_kassa_payment_method_name');
                if (empty($title)) {
                    $title = $this->language->get('kassa_page_title_default');
                }
                $sortKey = 'ya_kassa_sort_order';
            } elseif ($paymentMethod->isModeMoney()) {
                $title = $this->config->get('ya_namePaySys');
                if (empty($title)) {
                    $title = $this->language->get('text_title');
                }
                $sortKey = 'ya_sortOrder';
            } else {
                $title   = 'Яндекс.Платежка (банковские карты, кошелек)';
                $sortKey = 'ya_sortOrder';
            }
            $method_data = array(
                'code'       => 'yamoney',
                'title'      => $title,
                'sort_order' => (int)$this->config->get($sortKey),
            );
        }

        return $method_data;
    }

    /**
     * @return YandexMoneyPaymentMethod[]
     */
    public function getPaymentMethods()
    {
        if ($this->paymentMethods === null) {
            $path = dirname(__FILE__).'/yamoney/';
            require_once $path.'YandexMoneyPaymentMethod.php';
            require_once $path.'YandexMoneyPaymentKassa.php';
            require_once $path.'YandexMoneyPaymentMoney.php';
            require_once $path.'YandexMoneyPaymentBilling.php';
            $this->paymentMethods = array(
                YandexMoneyPaymentMethod::MODE_NONE    => new YandexMoneyPaymentMethod($this->config),
                YandexMoneyPaymentMethod::MODE_KASSA   => new YandexMoneyPaymentKassa($this->config),
                YandexMoneyPaymentMethod::MODE_MONEY   => new YandexMoneyPaymentMoney($this->config),
                YandexMoneyPaymentMethod::MODE_BILLING => new YandexMoneyPaymentBilling($this->config),
            );
        }

        return $this->paymentMethods;
    }

    /**
     * @param int $type
     *
     * @return YandexMoneyPaymentMethod
     */
    public function getPaymentMethod($type)
    {
        $methods = $this->getPaymentMethods();
        if (array_key_exists($type, $methods)) {
            return $methods[$type];
        }

        return $methods[0];
    }

    /**
     * @param YandexMoneyPaymentKassa $paymentMethod
     * @param $payments
     *
     * @return \YaMoney\Model\PaymentInterface[]
     */
    public function updatePaymentsStatuses(YandexMoneyPaymentKassa $paymentMethod, $payments)
    {
        $result = array();

        $this->getPaymentMethods();
        $client   = $this->getClient($paymentMethod);
        $statuses = array(
            \YaMoney\Model\PaymentStatus::PENDING,
            \YaMoney\Model\PaymentStatus::WAITING_FOR_CAPTURE,
        );
        foreach ($payments as $payment) {
            if (in_array($payment['status'], $statuses)) {
                try {
                    $paymentObject = $client->getPaymentInfo($payment['payment_id']);
                    if ($paymentObject === null) {
                        $this->updatePaymentStatus($payment['payment_id'], \YaMoney\Model\PaymentStatus::CANCELED);
                    } else {
                        $result[] = $paymentObject;
                        if ($paymentObject->getStatus() !== $payment['status']) {
                            $this->updatePaymentStatus($payment['payment_id'], $paymentObject->getStatus(),
                                $paymentObject->getCapturedAt());
                        }
                    }
                } catch (\Exception $e) {
                    // nothing to do
                }
            }
        }

        return $result;
    }

    /**
     * @param YandexMoneyPaymentKassa $paymentMethod
     * @param $orderInfo
     * @param $paymentMethodData
     *
     * @return \YaMoney\Model\PaymentInterface
     * @throws Exception
     */
    public function createPayment(YandexMoneyPaymentKassa $paymentMethod, $orderInfo)
    {
        $client = $this->getClient($paymentMethod);

        try {
            $builder = \YaMoney\Request\Payments\CreatePaymentRequest::builder();
            $amount  = $this->currency->format($orderInfo['total'], 'RUB', '', false);

            $builder->setAmount($amount)
                    ->setCurrency('RUB')
                    ->setCapture(false)
                    ->setClientIp($_SERVER['REMOTE_ADDR'])
                    ->setSavePaymentMethod(false)
                    ->setMetadata(array(
                        'order_id'       => $orderInfo['order_id'],
                        'cms_name'       => 'ya_api_opencart',
                        'module_version' => '1.0.7',
                    ));

            if ($paymentMethod->getSendReceipt()) {
                $taxRates = $this->config->get('ya_kassa_receipt_tax_id');
                $this->setReceiptItems($builder, $orderInfo);
            }
            $paymentType  = null;
            $confirmation = array(
                'type'      => \YaMoney\Model\ConfirmationType::REDIRECT,
                'returnUrl' => str_replace(
                    array('&amp;'),
                    array('&'),
                    $this->url->link('payment/yamoney/confirm', 'order_id='.$orderInfo['order_id'], true)
                ),
            );
            if (!empty($_GET['paymentType'])) {
                $paymentType = $_GET['paymentType'];
                if ($paymentType === \YaMoney\Model\PaymentMethodType::QIWI) {
                    $paymentType = array(
                        'type'  => $paymentType,
                        'phone' => preg_replace('/[^\d]/', '', $_GET['qiwiPhone']),
                    );
                } elseif ($paymentType === \YaMoney\Model\PaymentMethodType::ALFABANK) {
                    $paymentType  = array(
                        'type'  => $paymentType,
                        'login' => $_GET['alphaLogin'],
                    );
                    $confirmation = \YaMoney\Model\ConfirmationType::EXTERNAL;
                }
            }
            if ($paymentType !== null) {
                $builder->setPaymentMethodData($paymentType);
            }
            $builder->setConfirmation($confirmation);

            $request = $builder->build();
            $receipt = $request->getReceipt();
            if ($receipt instanceof \YaMoney\Model\Receipt) {
                $receipt->normalize($request->getAmount());
            }
        } catch (InvalidArgumentException $e) {
            $this->log('error', 'Failed to build API request object: '.$e->getMessage());

            return null;
        }

        $key = uniqid('', true);
        try {
            $paymentInfo = $client->createPayment($request, $key);
            $tries       = 1;
            while ($paymentInfo === null) {
                $this->log('info', 'Payment request retry');
                sleep(2);
                $paymentInfo = $client->createPayment($request, $key);
                $tries++;
                if ($tries > 3) {
                    throw new Exception('Maximum tries reached');
                }
            }
            if ($paymentInfo->getError() !== null) {
                throw new Exception('Failed to create payment: '.$paymentInfo->getError()->getCode());
            }
            $this->insertPayment($orderInfo['order_id'], $paymentInfo, $amount);
        } catch (\Exception $e) {
            $this->log('error', 'API error: '.$e->getMessage());

            return null;
        }

        return $paymentInfo;
    }

    /**
     * @param int $orderId
     * @param \YaMoney\Request\Payments\CreatePaymentResponse $payment
     * @param string $orderAmount
     *
     * @return bool
     */
    private function insertPayment($orderId, $payment, $orderAmount)
    {
        if ($payment->getPaymentMethod() === null) {
            $paymentMethodId = '';
        } else {
            $paymentMethodId = $payment->getPaymentMethod()->getId();
        }
        $sql = 'INSERT INTO `'.DB_PREFIX.'ya_money_payment` (`order_id`, `payment_id`, `status`, `amount`, '
               .'`currency`, `payment_method_id`, `paid`, `created_at`) VALUES ('
               .(int)$orderId.','
               ."'".$this->db->escape($payment->getId())."',"
               ."'".$this->db->escape($payment->getStatus())."',"
               ."'".$this->db->escape($orderAmount)."',"
               ."'".$this->db->escape($payment->getAmount()->getCurrency())."',"
               ."'".$this->db->escape($paymentMethodId)."',"
               ."'".($payment->getPaid() ? 'Y' : 'N')."',"
               ."'".$payment->getCreatedAt()->format('Y-m-d H:i:s')."'"
               .') ON DUPLICATE KEY UPDATE '
               .'`payment_id` = VALUES(`payment_id`),'
               .'`status` = VALUES(`status`),'
               .'`amount` = VALUES(`amount`),'
               .'`currency` = VALUES(`currency`),'
               .'`payment_method_id` = VALUES(`payment_method_id`),'
               .'`paid` = VALUES(`paid`),'
               .'`created_at` = VALUES(`created_at`)';

        return $this->db->query($sql);
    }

    public function getPendedPayments()
    {
        $sql = 'SELECT * FROM `'.DB_PREFIX.'ya_money_payment` WHERE (`status`=\''
               .\YaMoney\Model\PaymentStatus::PENDING.'\' OR `status`=\''
               .\YaMoney\Model\PaymentStatus::WAITING_FOR_CAPTURE.'\')';
        $res = $this->db->query($sql);
        if ($res->num_rows) {
            return $res->rows;
        }

        return array();
    }

    /**
     * @param \YaMoney\Model\PaymentInterface $payment
     *
     * @return int
     */
    public function getOrderIdByPayment($payment)
    {
        $sql     = 'SELECT `order_id` FROM `'.DB_PREFIX.'ya_money_payment` WHERE `payment_id` = \''
                   .$this->db->escape($payment->getId()).'\'';
        $dataSet = $this->db->query($sql);
        if (empty($dataSet->num_rows)) {
            return -1;
        }

        return (int)$dataSet->row['order_id'];
    }

    public function getOrderPayment(YandexMoneyPaymentKassa $paymentMethod, $orderId)
    {
        $sql     = 'SELECT * FROM `'.DB_PREFIX.'ya_money_payment` WHERE `order_id` = '.(int)$orderId;
        $dataSet = $this->db->query($sql);
        if (empty($dataSet->num_rows)) {
            return null;
        }
        $paymentId = $dataSet->row['payment_id'];

        $client = $this->getClient($paymentMethod);
        try {
            $payment = $client->getPaymentInfo($paymentId);
        } catch (Exception $e) {
            $this->log('error', 'Payment '.$paymentId.' not fetched from API');
            $payment = null;
        }

        if ($payment === null) {
            return null;
        }
        if ($payment->getAmount()->getValue() != $dataSet->row['amount']) {
            return null;
        }

        $update = array();
        if ($dataSet->row['status'] != $payment->getStatus()) {
            $update[] = '`status` = \''.$this->db->escape($payment->getStatus()).'\'';
        }
        $val = $payment->getPaid() ? 'Y' : 'N';
        if ($dataSet->row['paid'] != $val) {
            $update[] = "`paid` = '".$val."'";
        }
        if ($payment->getCapturedAt() !== null) {
            $val = $payment->getCapturedAt()->format('Y-m-d H:i:s');
            if ($dataSet->row['captured_at'] != $val) {
                $update[] = "`captured_at` = '".$val."'";
            }
        }
        if (!empty($update)) {
            $sql = 'UPDATE `'.DB_PREFIX.'ya_money_payment` SET '.implode(',',
                    $update).' WHERE `order_id` = '.(int)$orderId;
            $this->db->query($sql);
        }

        return $payment;
    }

    public function confirmOrder(YandexMoneyPaymentMethod $paymentMethod, $orderId)
    {
        $pay_url = $this->url->link('payment/yamoney/repay', 'order_id='.$orderId, 'SSL');
        $this->load->model('checkout/order');
        $this->model_checkout_order->confirm(
            $orderId,
            1,
            '<a href="'.$pay_url.'" class="button">'.$this->language->get('text_repay').'</a>',
            true
        );
    }

    private function setReceiptItems(\YaMoney\Request\Payments\CreatePaymentRequestBuilder $builder, $orderInfo)
    {
        $this->load->model('account/order');
        $this->load->model('catalog/product');

        if (isset($orderInfo['email'])) {
            $builder->setReceiptEmail($orderInfo['email']);
        } elseif (isset($orderInfo['phone'])) {
            $builder->setReceiptPhone($orderInfo['phone']);
        }
        $taxRates = $this->config->get('ya_kassa_receipt_tax_id');

        $orderProducts = $this->model_account_order->getOrderProducts($orderInfo['order_id']);
        foreach ($orderProducts as $prod) {
            $productInfo = $this->model_catalog_product->getProduct($prod['product_id']);
            $price       = $this->currency->format($prod['price'], 'RUB', '', false);
            if (isset($productInfo['tax_class_id'])) {
                $taxId = $productInfo['tax_class_id'];
                if (isset($taxRates[$taxId])) {
                    $builder->addReceiptItem($prod['name'], $price, $prod['quantity'], $taxRates[$taxId]);
                } else {
                    $builder->addReceiptItem($prod['name'], $price, $prod['quantity'], $taxRates['default']);
                }
            } else {
                $builder->addReceiptItem($prod['name'], $price, $prod['quantity'], $taxRates['default']);
            }
        }

        $order_totals = $this->model_account_order->getOrderTotals($orderInfo['order_id']);
        foreach ($order_totals as $total) {
            if (isset($total['code']) && $total['code'] === 'shipping') {
                $price = $this->currency->format($total['value'], 'RUB', '', false);
                if (isset($total['tax_class_id'])) {
                    $taxId = $total['tax_class_id'];
                    if (isset($taxRates[$taxId])) {
                        $builder->addReceiptShipping($total['title'], $price, $taxRates[$taxId]);
                    } else {
                        $builder->addReceiptShipping($total['title'], $price, $taxRates['default']);
                    }
                } else {
                    $builder->addReceiptShipping($total['title'], $price, $taxRates['default']);
                }
            }
        }

        return $builder;
    }

    /**
     * @param YandexMoneyPaymentKassa $paymentMethod
     * @param \YaMoney\Model\PaymentInterface $payment
     * @param bool $fetchPayment
     *
     * @return bool
     */
    public function capturePayment(YandexMoneyPaymentKassa $paymentMethod, $payment, $fetchPayment = true)
    {
        if ($fetchPayment) {
            $client = $this->getClient($paymentMethod);
            try {
                $payment = $client->getPaymentInfo($payment->getId());
            } catch (Exception $e) {
                $this->log('error', 'Payment '.$payment->getId().' not fetched from API in capture method');

                return false;
            }
        }

        if ($payment->getStatus() !== \YaMoney\Model\PaymentStatus::WAITING_FOR_CAPTURE) {
            return $payment->getStatus() === \YaMoney\Model\PaymentStatus::SUCCEEDED;
        }

        $client = $this->getClient($paymentMethod);
        try {
            $builder = \YaMoney\Request\Payments\Payment\CreateCaptureRequest::builder();
            $builder->setAmount($payment->getAmount());
            $key     = uniqid('', true);
            $tries   = 0;
            $request = $builder->build();
            do {
                $result = $client->capturePayment($request, $payment->getId(), $key);
                if ($result === null) {
                    $tries++;
                    if ($tries > 3) {
                        break;
                    }
                    sleep(2);
                }
            } while ($result === null);
            if ($result === null) {
                throw new RuntimeException('Failed to capture payment after 3 retries');
            }
        } catch (Exception $e) {
            $this->log('error', 'Failed to capture payment: '.$e->getMessage());

            return false;
        }

        return true;
    }

    /**
     * @param int $orderId
     * @param \YaMoney\Model\PaymentInterface $payment
     * @param int $statusId
     */
    public function confirmOrderPayment($orderId, $payment, $statusId)
    {
        $sql     = 'UPDATE `'.DB_PREFIX.'order_history` SET `comment` = \'Платёж подтверждён\' WHERE `order_id` = '
                   .(int)$orderId.' AND `order_status_id` <= 1';
        $comment = 'Номер транзакции: '.$payment->getId().'. Сумма: '.$payment->getAmount()->getValue()
                   .' '.$payment->getAmount()->getCurrency();
        $this->load->model('checkout/order');
        $this->model_checkout_order->update($orderId, $statusId, $comment, true);
        $this->db->query($sql);
    }

    public function log($level, $message, $context = null)
    {
        if ($this->config->get('ya_kassa_debug_mode')) {
            $log     = new Log('yandex-money.log');
            $search  = array();
            $replace = array();
            if (!empty($context)) {
                foreach ($context as $key => $value) {
                    $search[]  = '{'.$key.'}';
                    $replace[] = $value;
                }
            }

            $userId = 0;
            if ($this->session) {
                $sessionId = $this->session->getId();
                if (isset($this->session->data['user_id'])) {
                    $userId = $this->session->data['user_id'];
                }
            } else {
                $sessionId = 'console';
            }

            if (empty($search)) {
                $log->write('['.$level.'] ['.$userId.'] ['.$sessionId.'] - '.$message);
            } else {
                $log->write(
                    '['.$level.'] ['.$userId.'] ['.$sessionId.'] - '
                    .str_replace($search, $replace, $message)
                );
            }
        }
    }

    /**
     * @param YandexMoneyPaymentKassa $paymentMethod
     *
     * @return \YaMoney\Client\YandexMoneyApi
     */
    private function getClient(YandexMoneyPaymentKassa $paymentMethod)
    {
        if ($this->client === null) {
            $this->client = new \YaMoney\Client\YandexMoneyApi();
            $this->client->setAuth($paymentMethod->getShopId(), $paymentMethod->getPassword());
            $this->client->setLogger($this);
        }

        return $this->client;
    }

    /**
     * @param string $paymentId
     * @param string $status
     * @param \DateTime|null $capturedAt
     */
    private function updatePaymentStatus($paymentId, $status, $capturedAt = null)
    {
        $sql = 'UPDATE `'.DB_PREFIX.'ya_money_payment` SET `status` = \''.$status.'\'';
        if ($capturedAt !== null) {
            $sql .= ', `captured_at`=\''.$capturedAt->format('Y-m-d H:i:s').'\'';
        }
        $sql .= ' WHERE `payment_id`=\''.$paymentId.'\'';
        $this->db->query($sql);
    }
}
