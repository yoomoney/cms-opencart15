<?php

class ModelPaymentYaMoney extends Model
{
    private $paymentMethods;

    /**
     * @var Config
     */
    private $config;

    public function init($config)
    {
        $this->config = $config;
        return $this;
    }

    public function install()
    {
        $this->log('info', 'install ya_money module');
        $this->db->query('
            CREATE TABLE IF NOT EXISTS `' . DB_PREFIX . 'ya_money_payment` (
                `order_id`          INTEGER  NOT NULL,
                `payment_id`        CHAR(36) NOT NULL,
                `status`            ENUM(\'pending\', \'waiting_for_capture\', \'succeeded\', \'canceled\') NOT NULL,
                `amount`            DECIMAL(11, 2) NOT NULL,
                `currency`          CHAR(3) NOT NULL,
                `payment_method_id` CHAR(36) NOT NULL,
                `paid`              ENUM(\'Y\', \'N\') NOT NULL,
                `created_at`        DATETIME NOT NULL,
                `captured_at`       DATETIME NOT NULL DEFAULT \'0000-00-00 00:00:00\',

                CONSTRAINT `' . DB_PREFIX . 'ya_money_payment_pk` PRIMARY KEY (`order_id`),
                CONSTRAINT `' . DB_PREFIX . 'ya_money_payment_unq_payment_id` UNIQUE (`payment_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=UTF8 COLLATE=utf8_general_ci;
        ');
    }

    public function uninstall()
    {
        $this->log('info', 'uninstall ya_money module');
        $this->db->query('DROP TABLE IF EXISTS `' . DB_PREFIX . 'ya_money_payment`;');
    }

    public function log($level, $message, $context = null)
    {
        if ($this->config === null || $this->config->get('ya_kassa_debug_mode')) {
            $log = new Log('yandex-money.log');
            $search = array();
            $replace = array();
            if (!empty($context)) {
                foreach ($context as $key => $value) {
                    $search[] = '{' . $key . '}';
                    $replace[] = $value;
                }
            }
            if (empty($search)) {
                $log->write('[' . $level . '] - ' . $message);
            } else {
                $log->write('[' . $level . '] - ' . str_replace($search, $replace, $message));
            }
        }
    }

    /**
     * @return YandexMoneyPaymentMethod[]
     */
    public function getPaymentMethods()
    {
        if ($this->paymentMethods === null) {
            $path = dirname(__FILE__) . '/../../../catalog/model/payment/yamoney/';
            require_once $path . 'autoload.php';
            require_once $path . 'YandexMoneyPaymentMethod.php';
            require_once $path . 'YandexMoneyPaymentKassa.php';
            require_once $path . 'YandexMoneyPaymentMoney.php';
            require_once $path . 'YandexMoneyPaymentBilling.php';
            $this->paymentMethods = array(
                YandexMoneyPaymentMethod::MODE_NONE    => new YandexMoneyPaymentMethod($this->config),
                YandexMoneyPaymentMethod::MODE_KASSA   => new YandexMoneyPaymentKassa($this->config),
                YandexMoneyPaymentMethod::MODE_MONEY   => new YandexMoneyPaymentMoney($this->config),
                YandexMoneyPaymentMethod::MODE_BILLING => new YandexMoneyPaymentBilling($this->config),
            );
        }
        return $this->paymentMethods;
    }

    /**
     * @param int $type
     * @return YandexMoneyPaymentMethod
     */
    public function getPaymentMethod($type)
    {
        $methods = $this->getPaymentMethods();
        if (array_key_exists($type, $methods)) {
            return $methods[$type];
        }
        echo 'Get mayment method#' . $type . PHP_EOL;
        return $methods[0];
    }
}
