<?php

require_once dirname(__FILE__).'/lib/Common/AbstractEnum.php';
require_once dirname(__FILE__).'/lib/Model/PaymentMethodType.php';

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

    public $language;

    public function __construct($config, $language = null)
    {
        parent::__construct($config);
        $this->mode                        = self::MODE_KASSA;
        $this->enabled                     = $config->get('ya_kassa_enable');
        $this->shopId                      = $config->get('ya_kassa_shop_id');
        $this->password                    = $config->get('ya_kassa_password');
        $this->epl                         = $this->config->get('ya_kassa_payment_mode') == 'kassa';
        $this->status                      = (int)$this->config->get('ya_kassa_new_order_status');
        $this->yandexButton                = $this->config->get('ya_kassa_force_button_name') == '1';
        $this->createOrderBeforeRedirect   = $this->config->get('ya_kassa_create_order_before_redirect') == '1';
        $this->clearCartAfterOrderCreation = $this->config->get('ya_kassa_clear_cart_before_redirect') == '1';
        $this->language                    = $language;
        $this->enabledMethods              = array();
        $options                           = $config->get('ya_kassa_payment_options');
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
     *
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
            $string .= $callbackParams[$paramName].';';
        }
        $string .= $this->password;

        return (strtoupper($callbackParams['md5']) == strtoupper(md5($string)));
    }

    /**
     * @param array $callbackParams
     * @param int $code
     *
     * @return bool|void
     */
    public function sendCode($callbackParams, $code)
    {
        header("Content-type: text/xml; charset=utf-8");
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL
               .'<'.$callbackParams['action'].'Response performedDatetime="'.date("c").'" code="'
               .$code.'" invoiceId="'.$callbackParams['invoiceId'].'" shopId="'.$this->shopId.'"/>';
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
        $titles = array(
            YaMoney\Model\PaymentMethodType::BANK_CARD      => $this->language->get('bank_cards_title'),
            YaMoney\Model\PaymentMethodType::YANDEX_MONEY   => $this->language->get('text_method_yandex_money'),
            YaMoney\Model\PaymentMethodType::SBERBANK       => $this->language->get('text_method_sberbank'),
            YaMoney\Model\PaymentMethodType::QIWI           => $this->language->get('text_method_qiwi'),
            YaMoney\Model\PaymentMethodType::WEBMONEY       => $this->language->get('text_method_webmoney'),
            YaMoney\Model\PaymentMethodType::CASH           => $this->language->get('cash_title'),
            YaMoney\Model\PaymentMethodType::MOBILE_BALANCE => $this->language->get('mobile_balance_title'),
            YaMoney\Model\PaymentMethodType::ALFABANK       => $this->language->get('text_method_alfabank'),
        );

        $result = array();
        foreach (YaMoney\Model\PaymentMethodType::getEnabledValues() as $value) {
            $result[$value] = $titles[$value];
        }

        return $result;
    }

    /**
     * @param string $value
     *
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
            1 => $this->language->get('text_vat_none'),
            2 => '0%',
            3 => '10%',
            4 => '18%',
            5 => $this->language->get('text_vat_10'),
            6 => $this->language->get('text_vat_18'),
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

    public function checkConnection(array $options = null, $logger = null)
    {
        if (!empty($options)) {
            $shopId   = $options['ya_kassa_shop_id'];
            $password = $options['ya_kassa_password'];
        } else {
            $shopId   = $this->shopId;
            $password = $this->password;
        }

        $client = new \YaMoney\Client\YandexMoneyApi();
        $client->setAuth($shopId, $password);
        if (!empty($logger)) {
            $client->setLogger($logger);
        }

        try {
            $payment = $client->getPaymentInfo('00000000-0000-0000-0000-000000000001');
        } catch (\YaMoney\Common\Exceptions\NotFoundException $e) {
            return true;
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }
}
