<?php

class YandexMoneyPaymentMoney extends YandexMoneyPaymentMethod
{
    public function __construct($config)
    {
        parent::__construct($config);

        $this->mode = self::MODE_MONEY;
        $this->password = $config->get('ya_money_password');
        $this->createOrderBeforeRedirect = $this->config->get('ya_money_create_order_before_redirect') == '1';
        $this->clearCartAfterOrderCreation = $this->config->get('ya_money_clear_cart_before_redirect') == '1';
    }

    public function getFormUrl()
    {
        return 'https://' . ($this->testMode ? 'demo' : '') . 'money.yandex.ru/quickpay/confirm.xml';
    }

    public function checkSign($callbackParams)
    {
        $params = array(
            'notification_type',
            'operation_id',
            'amount',
            'currency',
            'datetime',
            'sender',
            'codepro',
        );

        $string = '';
        foreach ($params as $paramName) {
            if (!array_key_exists($paramName, $callbackParams)) {
                return false;
            }
            $string .= $callbackParams[$paramName] . '&';
        }
        $string .= $this->password . '&';
        if (!array_key_exists('label', $callbackParams)) {
            return false;
        }
        $string .= $callbackParams['label'];
        $check = (strtoupper(sha1($string)) == strtoupper($callbackParams['sha1_hash']));
        if (!$check) {
            header('HTTP/1.0 401 Unauthorized');
            return false;
        }
        return true;
    }

    public function getRequiredFields()
    {
        return array(
            'ya_money_wallet',
            'ya_money_password'
        );
    }

    /**
     * @return array
     */
    public function getSettings()
    {
        return array(
            'ya_money_on',
            'ya_money_wallet',
            'ya_money_password',
            'ya_money_payment_options',
            'ya_money_new_order_status',
            'ya_money_debug_mode',
            'ya_money_sort_order',
            'ya_money_id_zone',
            'ya_money_create_order_before_redirect',
            'ya_money_clear_cart_before_redirect',
        );
    }

    public function getPaymentMethods()
    {
        return array(
            'PC' => 'Яндекс.Деньги',
            'AC' => 'Банковские карты',
        );
    }

    public function getEnabledMethods()
    {
        $opts = $this->config->get('ya_money_payment_options');
        $result = array();
        if (is_array($opts)) {
            foreach ($this->getPaymentMethods() as $k => $v) {
                if (in_array($k, $opts)) {
                    $result[$k] = $v;
                }
            }
        }
        return $result;
    }

    /**
     * @return int
     */
    public function getSortOrder()
    {
        return (int)$this->config->get('ya_money_sort_order');
    }
}
