<?php

if (!defined('STDIN')) {
    exit();
}

echo 'Initialize...';

if (file_exists('config.php')) {
    require_once('config.php');
}

include 'system/startup.php';

require_once(DIR_SYSTEM . 'library/currency.php');

$registry = new Registry();

// Loader
$loader = new Loader($registry);
$registry->set('load', $loader);

// Config
$config = new Config();
$registry->set('config', $config);

// Database
$db = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
$registry->set('db', $db);

$config->set('config_store_id', 0);
$query = $db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE store_id = '0' ORDER BY store_id ASC");

foreach ($query->rows as $setting) {
    if (!$setting['serialized']) {
        $config->set($setting['key'], $setting['value']);
    } else {
        $config->set($setting['key'], unserialize($setting['value']));
    }
}

$config->set('config_url', HTTP_SERVER);
$config->set('config_ssl', HTTPS_SERVER);

// Url
$url = new Url($config->get('config_url'), $config->get('config_secure') ? $config->get('config_ssl') : $config->get('config_url'));
$registry->set('url', $url);

// Log
$log = new Log($config->get('config_error_filename'));
$registry->set('log', $log);

// Encryption
$registry->set('encryption', new Encryption($config->get('config_encryption')));

// Language Detection
$languages = array();
$query = $db->query("SELECT * FROM `" . DB_PREFIX . "language` WHERE status = '1'");
foreach ($query->rows as $result) {
    $languages[$result['code']] = $result;
}

$code = $config->get('config_language');

$config->set('config_language_id', $languages[$code]['language_id']);
$config->set('config_language', $languages[$code]['code']);

// Language
$language = new Language($languages[$code]['directory']);
$language->load($languages[$code]['filename']);
$registry->set('language', $language);

class SessionAndRequest
{
    public $get = array();
    public $post = array();
    public $cookie = array();
    public $data = array();
    public $server = array();

    public function __construct($config)
    {
        $this->cookie['currency'] = $config->get('config_currency');
        $this->cookie['language'] = $config->get('config_language');
        $this->data['currency'] = $config->get('config_currency');
        $this->data['language'] = $config->get('config_language');
        $this->server['HTTP_HOST'] = 'http://localhost';
    }

    public function getId()
    {
        return 'console';
    }
}

class ConsoleCurrency extends Currency
{
    public function set($currency)
    {
        $this->session->data['currency'] = $currency;
        $this->request->cookie['currency'] = $currency;
        parent::set($currency);
    }
}

$request = new SessionAndRequest($config);
$registry->set('request', $request);
$registry->set('session', $request);

$registry->set('cache', new Cache());
$registry->set('openbay', new Openbay($registry));
$registry->set('currency', new ConsoleCurrency($registry));

echo ' ok' . PHP_EOL;

echo 'Load yoomoney model...';
$loader->model('payment/yoomoney');
$loader->model('checkout/order');

/** @var ModelPaymentYoomoney $model */
$model = $loader->model_payment_yoomoney;
$model->getPaymentMethods();

/** @var ModelCheckoutOrder $orderModel */
$orderModel = $loader->model_checkout_order;

/** @var YooMoneyPaymentKassa $kassa */
$kassa = $model->getPaymentMethod(YooMoneyPaymentMethod::MODE_KASSA);
echo ' ok' . PHP_EOL;

echo 'Load pended payments...';
$payments = $model->getPendedPayments();
$orderIds = array();
foreach ($payments as $row) {
    $orderIds[$row['payment_id']] = $row['order_id'];
}
echo ' ok, ' . count($payments) . ' payments found' . PHP_EOL;

if (!empty($payments)) {

    echo 'Update payments statuses...' . PHP_EOL;
    $paymentObjects = $model->updatePaymentsStatuses($kassa, $payments);
    echo ' ok, ' . count($paymentObjects) . ' payments exists in YooKassa' . PHP_EOL;

    if (!empty($paymentObjects)) {
        echo 'Capturing...' . PHP_EOL;
        $count = 0;
        $total = 0;
        foreach ($paymentObjects as $payment) {
            echo '    Payment ' . $payment->getId();
            if ($payment['status'] === \YooKassa\Model\PaymentStatus::WAITING_FOR_CAPTURE) {
                $total++;
                echo ' capturing...';
                if ($model->capturePayment($kassa, $payment, false)) {
                    echo ' ok' . PHP_EOL;

                    $orderId = $orderIds[$payment->getId()];
                    echo '             ^--- order#' . $orderId . ' ';

                    $orderInfo = $orderModel->getOrder($orderId);
                    if (empty($orderInfo)) {
                        $model->log('warning', 'Empty order#' . $orderId . ' in notification');
                        echo 'failed to change status, order is empty!' . PHP_EOL;
                        continue;
                    } elseif ($orderInfo['order_status_id'] <= 0) {
                        $link = $registry->get('url')->link('payment/yoomoney/repay', 'order_id=' . $orderId, true);
                        $anchor = '<a href="' . $link . '" class="button">Оплатить</a>';
                        $orderModel->confirm($orderId, 1, $anchor);
                    }
                    $model->confirmOrderPayment($orderId, $payment, $kassa->getOrderStatusId());
                    $model->log('info', 'Платёж для заказа №' . $orderId . ' подтверждён');

                    echo 'status changed to status#' . $kassa->getOrderStatusId() . PHP_EOL;
                    $count++;
                } else {
                    echo ' need time' . PHP_EOL;
                }
            } else {
                echo ' in pending status, ignore' . PHP_EOL;
            }
        }
        echo $count . ' of ' . $total . ' payments captured' . PHP_EOL;
    }
}

echo 'Processing complete, exit' . PHP_EOL;
