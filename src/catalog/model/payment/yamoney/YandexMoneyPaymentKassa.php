<?php

require_once dirname(__FILE__) . '/lib/Common/AbstractEnum.php';
require_once dirname(__FILE__) . '/lib/Model/PaymentMethodType.php';

class YandexMoneyPaymentKassa extends YandexMoneyPaymentMethod
{
    /** @var bool */
    protected $epl;

    /** @var array */
    private $enabledMethods;

    /** @var int */
    private $successPageId;

    /** @var int */
    private $failurePageId;

    /** @var bool */
    private $sendReceipt;

    /** @var int */
    private $status;

    /** @var bool */
    private $yandexButton;

    public function __construct($config)
    {
        parent::__construct($config);

        $this->mode = self::MODE_KASSA;
        $this->enabled = $config->get('ya_kassa_enable');
        $this->shopId = $config->get('ya_kassa_shop_id');
        $this->password = $config->get('ya_kassa_password');
        $this->epl = $this->config->get('ya_kassa_payment_mode') == 'kassa';
        $this->status = (int)$this->config->get('ya_kassa_new_order_status');
        $this->yandexButton = $this->config->get('ya_kassa_force_button_name') == '1';
        $this->createOrderBeforeRedirect = $this->config->get('ya_kassa_create_order_before_redirect') == '1';
        $this->clearCartAfterOrderCreation = $this->config->get('ya_kassa_clear_cart_before_redirect') == '1';

        $this->enabledMethods = array();
        $options = $config->get('ya_kassa_payment_options');
        if (!empty($options) && is_array($options)) {
            foreach ($options as $method) {
                $this->enabledMethods[$method] = true;
            }
        }

        $this->successPageId = (int)$config->get('ya_kassa_page_success');
        $this->failurePageId = (int)$config->get('ya_kassa_page_failure');

        $this->sendReceipt = (bool)($config->get('ya_kassa_send_receipt') != '0');
        if (!empty($this->password)) {
            $this->testMode = strncmp('test_', $this->password, 5) === 0;
        } else {
            $this->testMode = false;
        }
    }

    /**
     * @return string
     */
    public function getFormUrl()
    {
        $url = new Url(HTTP_SERVER, $this->config->get('config_secure') ? HTTP_SERVER : HTTPS_SERVER);
        return $url->link('payment/yamoney/createPayment');
    }

    /**
     * @return bool
     */
    public function getEPL()
    {
        return !$this->testMode && $this->epl;
    }

    /**
     * @param array $callbackParams
     * @return bool
     */
    public function checkSign($callbackParams)
    {
        $params = array(
            'action',
            'orderSumAmount',
            'orderSumCurrencyPaycash',
            'orderSumBankPaycash',
            'shopId',
            'invoiceId',
            'customerNumber',
        );

        $string = '';
        foreach ($params as $paramName) {
            if (!array_key_exists($paramName, $callbackParams)) {
                return false;
            }
            $string .= $callbackParams[$paramName] . ';';
        }
        $string .= $this->password;
        return (strtoupper($callbackParams['md5']) == strtoupper(md5($string)));
    }

    /**
     * @param array $callbackParams
     * @param int $code
     */
    public function sendCode($callbackParams, $code)
    {
        header("Content-type: text/xml; charset=utf-8");
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL
            . '<' . $callbackParams['action'] . 'Response performedDatetime="' . date("c") . '" code="'
            . $code . '" invoiceId="' . $callbackParams['invoiceId'] . '" shopId="' . $this->shopId . '"/>';
        echo $xml;
    }

    /**
     * @return array
     */
    public function getRequiredFields()
    {
        return array(
            'ya_kassa_shop_id',
            'ya_kassa_password',
        );
    }

    /**
     * @return array
     */
    public function getSettings()
    {
        return array(
            'ya_kassa_enable',
            'ya_kassa_shop_id',
            'ya_kassa_password',
            'ya_kassa_payment_mode',
            'ya_kassa_payment_options',
            'ya_kassa_payment_default',
            'ya_kassa_page_success',
            'ya_kassa_page_failure',
            'ya_kassa_page_success_mp',
            'ya_kassa_payment_method_name',
            'ya_kassa_send_receipt',
            'ya_kassa_receipt_tax_id',
            'ya_kassa_new_order_status',
            'ya_kassa_debug_mode',
            'ya_kassa_sort_order',
            'ya_kassa_id_zone',
            'ya_kassa_force_button_name',
            'ya_kassa_create_order_before_redirect',
            'ya_kassa_clear_cart_before_redirect',
        );
    }

    /**
     * @return string[]
     */
    public function getPaymentMethods()
    {
        static $titles = array(
            YaMoney\Model\PaymentMethodType::BANK_CARD      => 'Банковские карты',
            YaMoney\Model\PaymentMethodType::YANDEX_MONEY   => 'Яндекс.Деньги',
            YaMoney\Model\PaymentMethodType::SBERBANK       => 'Сбербанк Онлайн',
            YaMoney\Model\PaymentMethodType::QIWI           => 'QIWI Wallet',
            YaMoney\Model\PaymentMethodType::WEBMONEY       => 'Webmoney',
            YaMoney\Model\PaymentMethodType::CASH           => 'Наличные через терминалы',
            YaMoney\Model\PaymentMethodType::MOBILE_BALANCE => 'Баланс мобильного',
            YaMoney\Model\PaymentMethodType::ALFABANK       => 'Альфа-Клик',
        );
        $result = array();
        foreach (YaMoney\Model\PaymentMethodType::getEnabledValues() as $value) {
            $result[$value] = $titles[$value];
        }
        return $result;
    }

    /**
     * @param string $value
     * @return bool
     */
    public function isPaymentMethodEnabled($value)
    {
        return array_key_exists($value, $this->enabledMethods);
    }

    /**
     * @return string[]
     */
    public function getTaxRates()
    {
        return array(
            1 => 'без НДС',
            2 => '0%',
            3 => '10%',
            4 => '18%',
            5 => 'Расчетная ставка 10/110',
            6 => 'Расчетная ставка 18/118',
        );
    }

    /**
     * @return int
     */
    public function getSuccessPageId()
    {
        return $this->successPageId;
    }

    /**
     * @return int
     */
    public function getFailurePageId()
    {
        return $this->failurePageId;
    }

    /**
     * @return mixed
     */
    public function getSendReceipt()
    {
        return $this->sendReceipt;
    }

    public function getOrderStatusId()
    {
        return $this->status;
    }

    public function useYandexButton()
    {
        return $this->getEPL() && $this->yandexButton;
    }
}
