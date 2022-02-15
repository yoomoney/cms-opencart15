<?php

use YooKassa\Client;
use YooKassa\Model\CurrencyCode;
use YooKassa\Model\PaymentData\B2b\Sberbank\VatDataRate;
use YooKassa\Model\PaymentData\B2b\Sberbank\VatDataType;
use YooKassa\Model\PaymentMethodType;
use YooKassa\Model\Receipt\PaymentMode;
use YooKassa\Model\Receipt\PaymentSubject;

class YooMoneyPaymentKassa extends YooMoneyPaymentMethod
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
    private $addInstallmentsButton;

    /**
     * @var bool
     */
    private $addInstallmentsBlock;

    protected $b2bSberbankEnabled;
    protected $b2bSberbankPaymentPurpose;
    protected $b2bSberbankDefaultTaxRate;
    protected $b2bTaxRates;
    protected $defaultPaymentMode;
    protected $defaultPaymentSubject;

    /** @var string */
    protected $currency;

    /** @var string|bool */
    protected $currency_convert;

    public $language;

    /**
     * YooMoneyPaymentKassa constructor.
     *
     * @param Config $config
     * @param null $language
     */
    public function __construct($config, $language = null)
    {
        parent::__construct($config);
        $this->mode                        = self::MODE_KASSA;
        $this->enabled                     = $config->get('yoomoney_kassa_enable');
        $this->shopId                      = $config->get('yoomoney_kassa_shop_id');
        $this->password                    = $config->get('yoomoney_kassa_password');
        $this->epl                         = $this->config->get('yoomoney_kassa_payment_mode') == 'kassa';
        $this->status                      = (int)$this->config->get('yoomoney_kassa_new_order_status');
        $this->addInstallmentsButton       = $this->config->get('yoomoney_kassa_add_installments_button') == '1';
        $this->addInstallmentsBlock        = $this->config->get('yoomoney_kassa_add_installments_block') == '1';
        $this->createOrderBeforeRedirect   = $this->config->get('yoomoney_kassa_create_order_before_redirect') == '1';
        $this->clearCartAfterOrderCreation = $this->config->get('yoomoney_kassa_clear_cart_before_redirect') == '1';
        $this->language                    = $language;
        $this->enabledMethods              = array();
        $options                           = $config->get('yoomoney_kassa_payment_options');
        if (!empty($options) && is_array($options)) {
            foreach ($options as $method) {
                $this->enabledMethods[$method] = true;
            }
        }

        $this->successPageId = (int)$config->get('yoomoney_kassa_page_success');
        $this->failurePageId = (int)$config->get('yoomoney_kassa_page_failure');

        $this->sendReceipt = (bool)($config->get('yoomoney_kassa_send_receipt') != '0');
        if (!empty($this->password)) {
            $this->testMode = strncmp('test_', $this->password, 5) === 0;
        } else {
            $this->testMode = false;
        }

        $this->b2bSberbankEnabled        = $config->get('yoomoney_kassa_b2b_sberbank_enabled');
        $this->b2bSberbankPaymentPurpose = $config->get('yoomoney_kassa_b2b_sberbank_payment_purpose');
        if (!$this->b2bSberbankPaymentPurpose) {
            $this->b2bSberbankPaymentPurpose = $this->language->get('kassa_description_default_placeholder');
        }
        $this->b2bSberbankDefaultTaxRate = $config->get('yoomoney_kassa_b2b_tax_rate_default');
        $this->b2bTaxRates               = $config->get('yoomoney_kassa_b2b_tax_rates');

        $this->defaultPaymentMode    = $config->get('yoomoney_kassa_default_payment_mode');
        $this->defaultPaymentSubject = $config->get('yoomoney_kassa_default_payment_subject');

        $this->currency              = $config->get('yoomoney_kassa_currency');
        $this->currency_convert      = $config->get('yoomoney_kassa_currency_convert');
    }

    /**
     * @return string
     */
    public function getFormUrl()
    {
        $url = new Url(HTTP_SERVER, $this->config->get('config_secure') ? HTTP_SERVER : HTTPS_SERVER);

        return $url->link('payment/yoomoney/createPayment');
    }

    /**
     * @return bool
     */
    public function getEPL()
    {
        return $this->epl;
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
            'yoomoney_kassa_shop_id',
            'yoomoney_kassa_password',
        );
    }

    /**
     * @return array
     */
    public function getSettings()
    {
        return array(
            'yoomoney_kassa_enable',
            'yoomoney_kassa_shop_id',
            'yoomoney_kassa_password',
            'yoomoney_kassa_payment_mode',
            'yoomoney_kassa_payment_options',
            'yoomoney_kassa_payment_default',
            'yoomoney_kassa_page_success',
            'yoomoney_kassa_page_failure',
            'yoomoney_kassa_page_success_mp',
            'yoomoney_kassa_payment_method_name',
            'yoomoney_kassa_description_template',
            'yoomoney_kassa_enable_hold_mode',
            'yoomoney_kassa_hold_order_status',
            'yoomoney_kassa_cancel_order_status',
            'yoomoney_kassa_send_receipt',
            'yoomoney_kassa_second_receipt_enable',
            'yoomoney_kassa_second_receipt_status',
            'yoomoney_kassa_receipt_tax_id',
            'yoomoney_kassa_default_tax_system',
            'yoomoney_kassa_new_order_status',
            'yoomoney_kassa_debug_mode',
            'yoomoney_kassa_sort_order',
            'yoomoney_kassa_id_zone',
            'yoomoney_kassa_add_installments_button',
            'yoomoney_kassa_add_installments_block',
            'yoomoney_kassa_create_order_before_redirect',
            'yoomoney_kassa_clear_cart_before_redirect',
            'yoomoney_kassa_b2b_sberbank_enabled',
            'yoomoney_kassa_b2b_sberbank_payment_purpose',
            'yoomoney_kassa_b2b_tax_rate_default',
            'yoomoney_kassa_b2b_tax_rates',
        );
    }

    /**
     * @return string[]
     */
    public function getPaymentMethods()
    {
        $titles = array(
            PaymentMethodType::BANK_CARD      => $this->language->get('bank_cards_title'),
            PaymentMethodType::YOO_MONEY      => $this->language->get('text_method_yoomoney'),
            PaymentMethodType::SBERBANK       => $this->language->get('text_method_sberbank'),
            PaymentMethodType::QIWI           => $this->language->get('text_method_qiwi'),
            PaymentMethodType::WEBMONEY       => $this->language->get('text_method_webmoney'),
            PaymentMethodType::CASH           => $this->language->get('cash_title'),
            PaymentMethodType::MOBILE_BALANCE => $this->language->get('mobile_balance_title'),
            PaymentMethodType::ALFABANK       => $this->language->get('text_method_alfabank'),
            PaymentMethodType::TINKOFF_BANK   => $this->language->get('text_method_tinkoff_bank'),
            PaymentMethodType::INSTALLMENTS   => $this->language->get('text_method_installments'),
        );

        $disabledMethods = array(
            PaymentMethodType::B2B_SBERBANK,
            PaymentMethodType::WECHAT,
            PaymentMethodType::WEBMONEY,
        );

        $result = array(
            self::CUSTOM_PAYMENT_METHOD_WIDGET => $this->language->get('text_method_widget'),
        );

        foreach (PaymentMethodType::getEnabledValues() as $value) {
            if (!in_array($value, $disabledMethods)) {
                $result[$value] = $titles[$value];
            }
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
            4 => '20%',
            5 => $this->language->get('text_vat_10'),
            6 => $this->language->get('text_vat_20'),
        );
    }

    /**
     * @return string[]
     */
    public function getTaxSystemCodes()
    {
        return array(
            1 => $this->language->get('kassa_tax_system_1_label'),
            2 => $this->language->get('kassa_tax_system_2_label'),
            3 => $this->language->get('kassa_tax_system_3_label'),
            4 => $this->language->get('kassa_tax_system_4_label'),
            5 => $this->language->get('kassa_tax_system_5_label'),
            6 => $this->language->get('kassa_tax_system_6_label'),
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

    public function checkConnection(array $options = null, $logger = null)
    {
        if (!empty($options)) {
            $shopId   = $options['yoomoney_kassa_shop_id'];
            $password = $options['yoomoney_kassa_password'];
        } else {
            $shopId   = $this->shopId;
            $password = $this->password;
        }

        $client = new Client();
        $client->setAuth($shopId, $password);
        if (!empty($logger)) {
            $client->setLogger($logger);
        }

        try {
            $client->getPaymentInfo('00000000-0000-0000-0000-000000000001');
        } catch (\YooKassa\Common\Exceptions\NotFoundException $e) {
            return true;
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function isAddInstallmentsButton()
    {
        return $this->addInstallmentsButton;
    }

    /**
     * @return bool
     */
    public function getAddInstallmentsBlock()
    {
        return $this->addInstallmentsBlock;
    }

    /**
     * @return bool
     */
    public function showInstallmentsBlock()
    {
        return $this->isAddInstallmentsButton() && $this->getAddInstallmentsBlock();
    }

    /**
     * @return mixed
     */
    public function getB2bSberbankEnabled()
    {
        return $this->b2bSberbankEnabled == 'on';
    }

    /**
     * @return mixed
     */
    public function getB2bSberbankPaymentPurpose()
    {
        return $this->b2bSberbankPaymentPurpose;
    }

    /**
     * @return mixed
     */
    public function getB2bSberbankDefaultTaxRate()
    {
        return $this->b2bSberbankDefaultTaxRate;
    }

    /**
     * @return mixed
     */
    public function getB2bTaxRates()
    {
        return $this->b2bTaxRates;
    }

    /**
     * @param string $key
     *
     * @return string
     */
    public function i18n($key)
    {
        return $this->language->get($key);
    }

    public function getB2bTaxRateId($shopTaxRateId)
    {
        if (isset($this->b2bTaxRates[$shopTaxRateId])) {
            return $this->b2bTaxRates[$shopTaxRateId];
        }

        return $this->b2bSberbankDefaultTaxRate;
    }

    public function getB2bTaxRatesList()
    {
        return array(
            VatDataType::UNTAXED => $this->language->get('b2b_tax_rate_untaxed_label'),
            VatDataRate::RATE_7  => $this->language->get('b2b_tax_rate_7_label'),
            VatDataRate::RATE_10 => $this->language->get('b2b_tax_rate_10_label'),
            VatDataRate::RATE_18 => $this->language->get('b2b_tax_rate_18_label'),
        );
    }

    public function getPaymentModeEnum()
    {
        return array(
            PaymentMode::FULL_PREPAYMENT    => 'Полная предоплата ('.PaymentMode::FULL_PREPAYMENT.')',
            PaymentMode::PARTIAL_PREPAYMENT => 'Частичная предоплата ('.PaymentMode::PARTIAL_PREPAYMENT.')',
            PaymentMode::ADVANCE            => 'Аванс ('.PaymentMode::ADVANCE.')',
            PaymentMode::FULL_PAYMENT       => 'Полный расчет ('.PaymentMode::FULL_PAYMENT.')',
            PaymentMode::PARTIAL_PAYMENT    => 'Частичный расчет и кредит ('.PaymentMode::PARTIAL_PAYMENT.')',
            PaymentMode::CREDIT             => 'Кредит ('.PaymentMode::CREDIT.')',
            PaymentMode::CREDIT_PAYMENT     => 'Выплата по кредиту ('.PaymentMode::CREDIT_PAYMENT.')',
        );
    }

    public function getPaymentSubjectEnum()
    {
        return array(
            PaymentSubject::COMMODITY             => 'Товар ('.PaymentSubject::COMMODITY.')',
            PaymentSubject::EXCISE                => 'Подакцизный товар ('.PaymentSubject::EXCISE.')',
            PaymentSubject::JOB                   => 'Работа ('.PaymentSubject::JOB.')',
            PaymentSubject::SERVICE               => 'Услуга ('.PaymentSubject::SERVICE.')',
            PaymentSubject::GAMBLING_BET          => 'Ставка в азартной игре ('.PaymentSubject::GAMBLING_BET.')',
            PaymentSubject::GAMBLING_PRIZE        => 'Выигрыш в азартной игре ('.PaymentSubject::GAMBLING_PRIZE.')',
            PaymentSubject::LOTTERY               => 'Лотерейный билет ('.PaymentSubject::LOTTERY.')',
            PaymentSubject::LOTTERY_PRIZE         => 'Выигрыш в лотерею ('.PaymentSubject::LOTTERY_PRIZE.')',
            PaymentSubject::INTELLECTUAL_ACTIVITY => 'Результаты интеллектуальной деятельности ('.PaymentSubject::INTELLECTUAL_ACTIVITY.')',
            PaymentSubject::PAYMENT               => 'Платеж ('.PaymentSubject::PAYMENT.')',
            PaymentSubject::AGENT_COMMISSION      => 'Агентское вознаграждение ('.PaymentSubject::AGENT_COMMISSION.')',
            PaymentSubject::COMPOSITE             => 'Несколько вариантов ('.PaymentSubject::COMPOSITE.')',
            PaymentSubject::ANOTHER               => 'Другое ('.PaymentSubject::ANOTHER.')',
        );
    }

    /**
     * @return mixed
     */
    public function getDefaultPaymentMode()
    {
        return $this->defaultPaymentMode;
    }

    /**
     * @return mixed
     */
    public function getDefaultPaymentSubject()
    {
        return $this->defaultPaymentSubject;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency ?: CurrencyCode::RUB;
    }

    /**
     * @return bool
     */
    public function getCurrencyConvert()
    {
        return $this->currency_convert == 'on';
    }

    /**
     * @return int
     */
    public function getSortOrder()
    {
        return (int)$this->config->get('yoomoney_kassa_sort_order');
    }
}
