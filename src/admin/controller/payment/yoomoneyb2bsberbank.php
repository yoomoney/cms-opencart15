<?php

require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'yoomoney.php';


class ControllerPaymentYoomoneyB2BSberbank extends ControllerPaymentYoomoney
{
    public function install()
    {
        $this->log->write("install YooMoneyB2bSberbank");
    }

    public function uninstall()
    {
        $this->log->write("uninstall YooMoneyB2bSberbank");
    }
}