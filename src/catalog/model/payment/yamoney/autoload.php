<?php

define('DS', DIRECTORY_SEPARATOR);

require_once dirname(__FILE__).'/lib/autoload.php';


define('YANDEX_MONEY_MODULE_PATH', dirname(__FILE__));

function yandexMoneyLoadClass($className)
{
    if (strncmp('YandexMoneyModule', $className, 17) === 0) {
        $path = YANDEX_MONEY_MODULE_PATH;
        $length = 17;
    } else {
        return;
    }
    if (DIRECTORY_SEPARATOR === '/') {
        $path .= str_replace('\\', '/', substr($className, $length));
    } else {
        $path .= substr($className, $length);
    }
    $path .= '.php';
    if (file_exists($path)) {
        include_once $path;
    }
}

spl_autoload_register('yandexMoneyLoadClass');
