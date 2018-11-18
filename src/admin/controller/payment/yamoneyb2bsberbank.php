<?php

require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'yamoney.php';


class ControllerPaymentYaMoneyB2bSberbank extends ControllerPaymentYaMoney
{
    public function install()
    {
        $this->log->write("install YaMoneyB2bSberbank");
    }

    public function uninstall()
    {
        $this->log->write("uninstall YaMoneyB2bSberbank");
    }
}