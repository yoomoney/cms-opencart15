<?php

use YandexCheckout\Model\PaymentMethodType;

class YandexMoneyPaymentMethod
{
    /** @const string */
    const MODULE_VERSION = '1.4.0';

    /**
     * @const string
     */
    const CUSTOM_PAYMENT_METHOD_WIDGET = 'widget';

    /** @var int */
    const MODE_NONE = 0;

    /** @var int */
    const MODE_KASSA = 1;

    /** @var int */
    const MODE_MONEY = 2;

    /** @var int */
    const MODE_BILLING = 3;

    /** @var Config */
    protected $config;

    /** @var int */
    protected $mode;

    /** @var bool */
    protected $enabled;

    /** @var bool */
    protected $testMode = false;

    /** @var string */
    protected $shopId;

    /** @var string */
    protected $scId;

    /** @var string */
    protected $password;

    /** @var bool */
    protected $createOrderBeforeRedirect;

    /** @var bool */
    protected $clearCartAfterOrderCreation;

    /**
     * YandexMoneyPaymentMethod constructor.
     * @param Config $config
     */
    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * @return bool
     */
    public function isModeKassa()
    {
        return $this->mode === self::MODE_KASSA;
    }

    /**
     * @return bool
     */
    public function isModeMoney()
    {
        return $this->mode === self::MODE_MONEY;
    }

    /**
     * @return bool
     */
    public function isModeBilling()
    {
        return $this->mode === self::MODE_BILLING;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @return bool
     */
    public function isTestMode()
    {
        return $this->testMode;
    }

    /**
     * @return string
     */
    public function getShopId()
    {
        return $this->shopId;
    }

    /**
     * @return string
     */
    public function getScId()
    {
        return $this->scId;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return int
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getFormUrl()
    {
        throw new Exception();
    }

    /**
     * @return bool
     */
    public function getEPL()
    {
        return false;
    }

    /**
     * @param array $callbackParams
     * @return bool
     */
    public function checkSign($callbackParams)
    {
        return false;
    }

    /**
     * @param array $callbackParams
     * @param string $code
     * @return bool
     */
    public function sendCode($callbackParams, $code)
    {
        return false;
    }

    /**
     * @return array
     */
    public function getRequiredFields()
    {
        return array();
    }

    /**
     * @return array
     */
    public function getSettings()
    {
        return array();
    }

    /**
     * @return bool
     */
    public function getCreateOrderBeforeRedirect()
    {
        return $this->createOrderBeforeRedirect;
    }

    /**
     * @return bool
     */
    public function getClearCartBeforeRedirect()
    {
        return $this->clearCartAfterOrderCreation;
    }
    public function checkConnection(array $options = null, $logger = null)
    {
        return true;
    }

    /**
     * @return bool
     */
    public function isHoldModeEnable()
    {
        return (bool)$this->config->get('ya_kassa_enable_hold_mode');
    }

    /**
     * @return bool
     */
    public function isSecondReceiptEnable()
    {
        return (bool)$this->config->get('ya_kassa_second_receipt_enable');
    }

    /**
     * @return int
     */
    public function getSecondReceiptStatus()
    {
        return (int)$this->config->get('ya_kassa_second_receipt_status');
    }


    /**
     * @return int
     */
    public function getHoldOrderStatusId()
    {
        return (int)$this->config->get('ya_kassa_hold_order_status');
    }

    /**
     * @return int
     */
    public function getCancelOrderStatusId()
    {
        return (int)$this->config->get('ya_kassa_cancel_order_status');
    }

    /**
     * @param string $paymentMethod
     * @return bool
     */
    public function getCaptureValue($paymentMethod)
    {
        if (!$this->isHoldModeEnable()) {
            return true;
        }

        return !in_array($paymentMethod, array('', PaymentMethodType::BANK_CARD));
    }

    public function getSortOrder()
    {
        return 0;
    }
}
