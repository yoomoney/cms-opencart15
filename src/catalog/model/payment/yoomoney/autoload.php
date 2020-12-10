<?php

define('DS', DIRECTORY_SEPARATOR);

require_once dirname(__FILE__).'/vendor/autoload.php';


define('YOOMONEY_MODULE_PATH', dirname(__FILE__));

function yooKassaLoadClass($className)
{
    if (strncmp('YooMoneyModule', $className, 17) === 0) {
        $path = YOOMONEY_MODULE_PATH;
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

spl_autoload_register('yooKassaLoadClass');
