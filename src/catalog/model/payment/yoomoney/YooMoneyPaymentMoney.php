<?php

class YooMoneyPaymentMoney extends YooMoneyPaymentMethod
{
    public function __construct($config)
    {
        parent::__construct($config);

        $this->mode = self::MODE_MONEY;
        $this->password = $config->get('yoomoney_password');
        $this->createOrderBeforeRedirect = $this->config->get('yoomoney_create_order_before_redirect') == '1';
        $this->clearCartAfterOrderCreation = $this->config->get('yoomoney_clear_cart_before_redirect') == '1';
    }

    public function getFormUrl()
    {
        return 'https://yoomoney.ru/quickpay/confirm.xml';
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
            'yoomoney_wallet',
            'yoomoney_password'
        );
    }

    /**
     * @return array
     */
    public function getSettings()
    {
        return array(
            'yoomoney_on',
            'yoomoney_wallet',
            'yoomoney_password',
            'yoomoney_payment_options',
            'yoomoney_new_order_status',
            'yoomoney_debug_mode',
            'yoomoney_sort_order',
            'yoomoney_id_zone',
            'yoomoney_create_order_before_redirect',
            'yoomoney_clear_cart_before_redirect',
        );
    }

    public function getPaymentMethods()
    {
        return array(
            'PC' => 'ЮMoney',
            'AC' => 'Банковские карты',
        );
    }

    public function getEnabledMethods()
    {
        $opts = $this->config->get('yoomoney_payment_options');
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
        return (int)$this->config->get('yoomoney_sort_order');
    }
}
