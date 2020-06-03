<?php

use YandexCheckout\Client;
use YandexCheckout\Model\ConfirmationType;
use YandexCheckout\Model\CurrencyCode;
use YandexCheckout\Model\Payment;
use YandexCheckout\Model\PaymentInterface;
use YandexCheckout\Model\PaymentMethodType;
use YandexCheckout\Model\Receipt;
use YandexCheckout\Request\Payments\CreatePaymentRequest;
use YandexCheckout\Request\Payments\CreatePaymentRequestBuilder;

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
            } elseif ($paymentMethod->isModeBilling()) {
                $title   = 'Яндекс.Платежка (банковские карты, кошелек)';
                $sortKey = 'ya_sortOrder';
            } else {
                $title   = 'Яндекс.Деньги (банковские карты, кошелек)';
            }
            $method_data = array(
                'code'       => 'yamoney',
                'title'      => $title,
                'sort_order' => $paymentMethod->getSortOrder(),
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
            require_once YANDEX_MONEY_MODULE_PATH . '/YandexMoneyPaymentMethod.php';
            require_once YANDEX_MONEY_MODULE_PATH . '/YandexMoneyPaymentKassa.php';
            require_once YANDEX_MONEY_MODULE_PATH . '/YandexMoneyPaymentMoney.php';
            require_once YANDEX_MONEY_MODULE_PATH . '/YandexMoneyPaymentBilling.php';
            $this->paymentMethods = array(
                YandexMoneyPaymentMethod::MODE_NONE    => new YandexMoneyPaymentMethod($this->config),
                YandexMoneyPaymentMethod::MODE_KASSA   => new YandexMoneyPaymentKassa($this->config, $this->language),
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
     * @return PaymentInterface[]
     */
    public function updatePaymentsStatuses(YandexMoneyPaymentKassa $paymentMethod, $payments)
    {
        $result = array();

        $this->getPaymentMethods();
        $client   = $this->getClient($paymentMethod);
        $statuses = array(
            \YandexCheckout\Model\PaymentStatus::PENDING,
            \YandexCheckout\Model\PaymentStatus::WAITING_FOR_CAPTURE,
        );
        foreach ($payments as $payment) {
            if (in_array($payment['status'], $statuses)) {
                try {
                    $paymentObject = $client->getPaymentInfo($payment['payment_id']);
                    if ($paymentObject === null) {
                        $this->updatePaymentStatus($payment['payment_id'],
                            \YandexCheckout\Model\PaymentStatus::CANCELED);
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
     *
     * @return PaymentInterface
     */
    public function createPayment(YandexMoneyPaymentKassa $paymentMethod, $orderInfo)
    {
        $client = $this->getClient($paymentMethod);

        $paymentType = !empty($_GET['paymentType']) ? $_GET['paymentType'] : '';

        $kassaCurrency = $paymentMethod->getCurrency();
        $this->log('info', "Amount calc \n{data}", array(
            'data' => json_encode(array(
                'order_total' => $orderInfo['total'],
                'kassa_currency' => $kassaCurrency,
                'has_currency' => $this->currency->has($kassaCurrency) ? 'true' : 'false',
            ), JSON_PRETTY_PRINT)));

        if ($this->currency->has($kassaCurrency)) {
            $amount = $this->currency->format($orderInfo['total'], $kassaCurrency, '', false);
        } else {
            if ($paymentMethod->getCurrencyConvert()) {
                $amount = $this->convertFromCbrf($orderInfo, $kassaCurrency);
            } else {
                $amount = $orderInfo['total'];
            }
        }

        try {
            $builder = CreatePaymentRequest::builder();
//            $amount  = $this->currency->format($orderInfo['total'], 'RUB', '', false);

            $builder->setAmount($amount)
                    ->setCurrency($kassaCurrency)
                    ->setCapture($paymentMethod->getCaptureValue($paymentType))
                    ->setDescription($this->createDescription($orderInfo))
                    ->setClientIp($_SERVER['REMOTE_ADDR'])
                    ->setSavePaymentMethod(false)
                    ->setMetadata(array(
                        'order_id'       => $orderInfo['order_id'],
                        'cms_name'       => 'ya_api_opencart',
                        'module_version' => '1.4.0',
                    ));
            if ($paymentMethod->getSendReceipt()) {
                $this->setReceiptItems($builder, $orderInfo);
            }
            $confirmation = array(
                'type'      => ConfirmationType::REDIRECT,
                'returnUrl' => str_replace(
                    array('&amp;'),
                    array('&'),
                    $this->url->link('payment/yamoney/confirm', 'order_id='.$orderInfo['order_id'], true)
                ),
            );

            if ($paymentType) {
                if ($paymentType === PaymentMethodType::QIWI) {
                    $paymentType = array(
                        'type'  => $paymentType,
                        'phone' => preg_replace('/[^\d]/', '', $_GET['qiwiPhone']),
                    );
                } elseif ($paymentType === PaymentMethodType::ALFABANK) {
                    $paymentType  = array(
                        'type'  => $paymentType,
                        'login' => $_GET['alphaLogin'],
                    );
                    $confirmation = ConfirmationType::EXTERNAL;
                } elseif ($paymentType === YandexMoneyPaymentKassa::CUSTOM_PAYMENT_METHOD_WIDGET) {
                    $confirmation = ConfirmationType::EMBEDDED;
                    $paymentType = null;
                }
                $builder->setPaymentMethodData($paymentType);
            }
            $builder->setConfirmation($confirmation);

            $request = $builder->build();
            $receipt = $request->getReceipt();
            if ($receipt instanceof Receipt) {
                $receipt->normalize($request->getAmount());
            }
        } catch (InvalidArgumentException $e) {
            $this->log('error', 'Failed to build API request object: '.$e->getMessage());

            return null;
        }

        try {
            $paymentInfo = $client->createPayment($request);
            $this->insertPayment($orderInfo['order_id'], $paymentInfo, $amount);
        } catch (\Exception $e) {
            $this->log('error', 'API error: '.$e->getMessage());

            return null;
        }

        return $paymentInfo;
    }

    /**
     * @param int $orderId
     * @param \YandexCheckout\Request\Payments\CreatePaymentResponse $payment
     * @param string $orderAmount
     * @param null $coupon
     *
     * @return bool
     */
    public function insertPayment($orderId, $payment, $orderAmount, $coupon = null)
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
               .\YandexCheckout\Model\PaymentStatus::PENDING.'\' OR `status`=\''
               .\YandexCheckout\Model\PaymentStatus::WAITING_FOR_CAPTURE.'\')';
        $res = $this->db->query($sql);
        if ($res->num_rows) {
            return $res->rows;
        }

        return array();
    }

    /**
     * @param PaymentInterface $payment
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

    /**
     * @param YandexMoneyPaymentKassa $paymentMethod
     * @param $orderId
     *
     * @return PaymentInterface|null
     */
    public function getPaymentByOrderId(YandexMoneyPaymentKassa $paymentMethod, $orderId)
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

    /**
     * @param $orderId
     * @param $status
     * @param $comment
     *
     * @return bool
     */
    public function updateOrderHistory($orderId, $status, $comment)
    {
        $sql = "INSERT INTO ".DB_PREFIX."order_history SET order_id = '".(int)$orderId
            ."', order_status_id = '".(int)$status."', notify = 0, comment = '"
            .$this->db->escape($comment)."', date_added = NOW()";

        try {
            return $this->db->query($sql);
        } catch (Exception $e) {
            $this->log('error', $e->getMessage());
        }

        return false;
    }

    public function confirmOrder(YandexMoneyPaymentMethod $paymentMethod, $orderId)
    {

        $pay_url = $this->url->link('payment/yamoney/repay', 'order_id='.$orderId, 'SSL');
        $this->load->model('checkout/order');
        $orderStatusId = $this->config->get('config_order_status_id');
        $this->model_checkout_order->confirm(
            $orderId,
            $orderStatusId,
            '<a href="'.$pay_url.'" class="button">'.$this->language->get('text_repay').'</a>',
            true
        );
    }

    /**
     * @param CreatePaymentRequestBuilder $builder
     * @param $orderInfo
     *
     * @return CreatePaymentRequestBuilder
     */
    private function setReceiptItems(CreatePaymentRequestBuilder $builder, $orderInfo)
    {
        $this->load->model('account/order');
        $this->load->model('catalog/product');

        if (!empty($orderInfo['email']) && filter_var($orderInfo['email'], FILTER_VALIDATE_EMAIL)) {
            $builder->setReceiptEmail($orderInfo['email']);
        } elseif (!empty($orderInfo['telephone'])) {
            $builder->setReceiptPhone($orderInfo['telephone']);
        }
        $taxRates              = $this->config->get('ya_kassa_receipt_tax_id');
        $defaultPaymentSubject = $this->config->get('ya_kassa_default_payment_subject');
        $defaultPaymentMode    = $this->config->get('ya_kassa_default_payment_mode');
        $orderProducts         = $this->model_account_order->getOrderProducts($orderInfo['order_id']);
        foreach ($orderProducts as $prod) {
            $productInfo = $this->model_catalog_product->getProduct($prod['product_id']);
            $properties  = $this->getPaymentProperties($prod['product_id']);
            if (!empty($properties)) {
                $paymentMode    = !empty($properties['payment_mode']) ? $properties['payment_mode'] : $defaultPaymentMode;
                $paymentSubject = !empty($properties['payment_subject']) ? $properties['payment_subject'] : $defaultPaymentSubject;
            } else {
                $paymentMode    = $defaultPaymentMode;
                $paymentSubject = $defaultPaymentSubject;
            }
            $price = $this->currency->format($prod['price'], 'RUB', '', false);
            if (isset($productInfo['tax_class_id'])) {
                $taxId = $productInfo['tax_class_id'];
                if (isset($taxRates[$taxId])) {
                    $builder->addReceiptItem($prod['name'], $price, $prod['quantity'], $taxRates[$taxId],
                        $paymentMode, $paymentSubject);
                } else {
                    $builder->addReceiptItem($prod['name'], $price, $prod['quantity'], $taxRates['default'],
                        $paymentMode, $paymentSubject);
                }
            } else {
                $builder->addReceiptItem($prod['name'], $price, $prod['quantity'], $taxRates['default'],
                    $paymentMode, $paymentSubject);
            }
        }

        $order_totals = $this->model_account_order->getOrderTotals($orderInfo['order_id']);
        foreach ($order_totals as $total) {
            if (isset($total['code']) && $total['code'] === 'shipping') {
                $price = $this->currency->format($total['value'], 'RUB', '', false);
                if (isset($total['tax_class_id'])) {
                    $taxId = $total['tax_class_id'];
                    if (isset($taxRates[$taxId])) {
                        $builder->addReceiptShipping($total['title'], $price, $taxRates[$taxId], $defaultPaymentMode,
                            $defaultPaymentSubject);
                    } else {
                        $builder->addReceiptShipping($total['title'], $price, $taxRates['default'], $defaultPaymentMode,
                            $defaultPaymentSubject);
                    }
                } else {
                    $builder->addReceiptShipping($total['title'], $price, $taxRates['default'], $defaultPaymentMode,
                        $defaultPaymentSubject);
                }
            }
        }

        return $builder;
    }

    /**
     * @param YandexMoneyPaymentKassa $paymentMethod
     * @param PaymentInterface $payment
     *
     * @return bool
     */
    public function capturePayment(YandexMoneyPaymentKassa $paymentMethod, $payment)
    {
        if ($payment->getStatus() !== \YandexCheckout\Model\PaymentStatus::WAITING_FOR_CAPTURE) {
            return $payment->getStatus() === \YandexCheckout\Model\PaymentStatus::SUCCEEDED;
        }

        $client = $this->getClient($paymentMethod);
        try {
            $builder = \YandexCheckout\Request\Payments\Payment\CreateCaptureRequest::builder();
            $builder->setAmount($payment->getAmount());
            $request = $builder->build();
            $client->capturePayment($request, $payment->getId());
        } catch (Exception $e) {
            $this->log('error', 'Failed to capture payment: '.$e->getMessage());

            return false;
        }

        return true;
    }

    /**
     * @param int $orderId
     * @param PaymentInterface $payment
     * @param int $statusId
     */
    public function confirmOrderPayment($orderId, $payment, $statusId)
    {
        $message = '';
        if ($payment->getPaymentMethod()->getType() == PaymentMethodType::B2B_SBERBANK) {
            $payerBankDetails = $payment->getPaymentMethod()->getPayerBankDetails();

            $fields = array(
                'fullName'   => 'Полное наименование организации',
                'shortName'  => 'Сокращенное наименование организации',
                'adress'     => 'Адрес организации',
                'inn'        => 'ИНН организации',
                'kpp'        => 'КПП организации',
                'bankName'   => 'Наименование банка организации',
                'bankBranch' => 'Отделение банка организации',
                'bankBik'    => 'БИК банка организации',
                'account'    => 'Номер счета организации',
            );


            foreach ($fields as $field => $caption) {
                if (isset($requestData[$field])) {
                    $message .= $caption.': '.$payerBankDetails->offsetGet($field).'\n';
                }
            }
        }


        $sql     = 'UPDATE `'.DB_PREFIX.'order_history` SET `comment` = \'Платёж подтверждён\' WHERE `order_id` = '
                   .(int)$orderId.' AND `order_status_id` <= 1';
        $comment = 'Номер транзакции: '.$payment->getId().'. Сумма: '.$payment->getAmount()->getValue()
                   .' '.$payment->getAmount()->getCurrency().$message;
        $this->load->model('checkout/order');
        $this->model_checkout_order->update($orderId, $statusId, $comment);
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
                    $replace[] = (is_array($value)||is_object($value)) ? json_encode($value, JSON_PRETTY_PRINT) : $value;
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
                foreach ($search as $object) {
                    if (stripos($message, $object) === false) {
                        $label = trim($object, "{}");
                        $message .= " \n{$label}: {$object}";
                    }
                }
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
     * @return Client
     */
    public function getClient(YandexMoneyPaymentKassa $paymentMethod)
    {
        if ($this->client === null) {
            $this->client = new Client();
            $this->client->setAuth($paymentMethod->getShopId(), $paymentMethod->getPassword());
            $this->client->setLogger($this);
            $userAgent = $this->client->getApiClient()->getUserAgent();
            $userAgent->setCms('OpenCart', VERSION);
            $userAgent->setModule('PaymentGateway', YandexMoneyPaymentMethod::MODULE_VERSION);
        }

        return $this->client;
    }

    /**
     * @param $orderId
     * @param $status
     */
    public function hookOrderStatusChange($orderId, $status)
    {
        require_once YANDEX_MONEY_MODULE_PATH . '/YandexMoneySecondReceipt.php';

        $this->load->model('account/order');
        $orderInfo = $this->model_account_order->getOrder($orderId);

        $secondReceipt = new YandexMoneySecondReceipt($this);
        $secondReceipt->sendSecondReceipt($orderInfo, $status);
    }

    /**
     * @param string $paymentId
     * @param string $status
     * @param DateTime|null $capturedAt
     */
    private function updatePaymentStatus($paymentId, $status, $capturedAt = null)
    {
        $sql = 'UPDATE `'.DB_PREFIX.'ya_money_payment` SET `status` = \''.$status.'\'';
        if ($capturedAt !== null) {
            $sql .= ', `captured_at`=\''.$capturedAt->format('Y-m-d H:i:s').'\'';
        }
        if ($status !== \YandexCheckout\Model\PaymentStatus::CANCELED && $status !== \YandexCheckout\Model\PaymentStatus::PENDING) {
            $sql .= ', `paid`=\'Y\'';
        }
        $sql .= ' WHERE `payment_id`=\''.$paymentId.'\'';
        $this->db->query($sql);
    }

    /**
     * @param $orderInfo
     *
     * @return bool|string
     */
    private function createDescription($orderInfo)
    {
        $descriptionTemplate = $this->config->get('ya_kassa_description_template') ?: $this->language->get('kassa_description_default_placeholder');

        $replace = array();
        foreach ($orderInfo as $key => $value) {
            if (is_scalar($value)) {
                $replace['%'.$key.'%'] = $value;
            }
        }
        $description = strtr($descriptionTemplate, $replace);

        return (string)mb_substr($description, 0, Payment::MAX_LENGTH_DESCRIPTION);
    }

    /**
     * @param $productId
     * @return mixed
     */
    public function getPaymentProperties($productId)
    {
        $res         = $this->db->query('SELECT * FROM `'.DB_PREFIX.'ya_money_product_properties` WHERE product_id='.$productId);
        $productProp = $res->row;

        return $productProp;
    }


    /**
     * @return array
     */
    public function getCbrfCourses()
    {
        $courses = $this->cache->get('cbrf_courses');
        if (!$courses) {
            require_once YANDEX_MONEY_MODULE_PATH . '/CBRAgent.php';
            $cbrf = new CBRAgent();
            $courses = $cbrf->getList();
            $this->cache->set('cbrf_courses', $courses);
            $this->log('info', "Get CBRF courses \n{courses}", array('courses' => $courses));
        }
        return $courses;
    }

    /**
     * @param array $order
     * @param string $currency
     * @return string
     */
    public function convertFromCbrf($order, $currency)
    {
        $config_currency = $this->config->get('config_currency');

        if ($config_currency == $currency) {
            return $order['total'];
        }

        $courses = $this->getCbrfCourses();
        if ((!empty($courses[$currency]) || $currency === CurrencyCode::RUB)
            && (!empty($courses[$config_currency]) || $config_currency === CurrencyCode::RUB)) {
            $input  = $config_currency != CurrencyCode::RUB ? $courses[$config_currency] : 1.0;
            $output = $currency != CurrencyCode::RUB ? $courses[$currency] : 1.0;

            return number_format($order['total'] * $input / $output, 2, '.', '');
        }

        return $order['total'];
    }
}
