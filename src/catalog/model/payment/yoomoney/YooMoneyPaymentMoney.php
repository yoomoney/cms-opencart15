<?php

class YooMoneyPaymentMoney extends YooMoneyPaymentMethod
{
    public function __construct($config)
    {
        parent::__construct($config);

        $this->mode = self::MODE_MONEY;
        $this->password = $config->get('yoomoney_wallet_password');
        $this->createOrderBeforeRedirect = $this->config->get('yoomoney_wallet_create_order_before_redirect') == '1';
        $this->clearCartAfterOrderCreation = $this->config->get('yoomoney_wallet_clear_cart_before_redirect') == '1';
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
            'yoomoney_wallet_account_id',
            'yoomoney_wallet_password'
        );
    }

    /**
     * @return array
     */
    public function getSettings()
    {
        return array(
            'yoomoney_wallet_enable',
            'yoomoney_wallet_account_id',
            'yoomoney_wallet_password',
            'yoomoney_wallet_payment_options',
            'yoomoney_wallet_new_order_status',
            'yoomoney_wallet_debug_mode',
            'yoomoney_wallet_sort_order',
            'yoomoney_wallet_id_zone',
            'yoomoney_wallet_create_order_before_redirect',
            'yoomoney_wallet_clear_cart_before_redirect',
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
        $opts = $this->config->get('yoomoney_wallet_payment_options');
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
        return (int)$this->config->get('yoomoney_wallet_sort_order');
    }
}
