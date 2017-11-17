<?php

class YandexMoneyPaymentBilling extends YandexMoneyPaymentMethod
{
    /** @var string */
    private $formId;

    /** @var string */
    private $purpose;

    /** @var string */
    private $status;

    public function __construct($config)
    {
        parent::__construct($config);
        $this->mode = self::MODE_BILLING;

        $this->enabled = $config->get('ya_billing_enable');
        $this->formId = $config->get('ya_billing_form_id');
        $this->purpose = $config->get('ya_billing_purpose');
        $this->status = $config->get('ya_billing_status');
    }

    public function getFormUrl()
    {
        return 'https://money.yandex.ru/fastpay/confirm';
    }

    public function getFormId()
    {
        return $this->formId;
    }

    public function getPurpose()
    {
        return $this->purpose;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function checkSign($callbackParams)
    {
        return true;
    }

    public function getRequiredFields()
    {
        return array(
            'ya_billing_form_id',
            'ya_billing_purpose',
            'ya_billing_status',
        );
    }

    /**
     * @return array
     */
    public function getSettings()
    {
        return array(
            'ya_billing_enable',
            'ya_billing_form_id',
            'ya_billing_purpose',
            'ya_billing_status',
            'ya_billing_debug_mode',
            'ya_billing_sort_order',
            'ya_billing_id_zone',
        );
    }
}