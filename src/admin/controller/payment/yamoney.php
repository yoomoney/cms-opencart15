<?php

/**
 * Class ControllerPaymentYaMoney
 *
 * @property ModelPaymentYaMoney $model_payment_ya_money
 *
 * @property ModelLocalisationOrderStatus $model_localisation_order_status
 * @property ModelLocalisationGeoZone $model_localisation_geo_zone
 * @property ModelLocalisationTaxClass $model_localisation_tax_class
 * @property ModelCatalogInformation $model_catalog_information
 * @property ModelSettingSetting $model_setting_setting
 */
class ControllerPaymentYaMoney extends Controller
{
    /**
     * @var array
     */
    private $error = array();

    /**
     * @var string
     */
    private $moduleVersion = '1.0.4';

    /**
     * @var ModelPaymentYaMoney
     */
    private $_model;

    /**
     * @return ModelPaymentYaMoney
     */
    private function getModel()
    {
        if ($this->_model === null) {
            $this->load->model('payment/ya_money');
            $this->_model = $this->model_payment_ya_money;
        }
        return $this->_model;
    }

    public function install()
    {
        $this->getModel()->install();
    }

    public function uninstall()
    {
        $this->getModel()->uninstall();
    }

    public function index()
    {
        $this->language->load('payment/yamoney');
        $this->load->model('setting/setting');

        $this->getModel()->init($this->config);
        $currentAction = $this->url->link('payment/yamoney', 'token=' . $this->session->data['token'], 'SSL');
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate($this->request->post)) {
            $this->saveSettings($this->request->post);
            $this->redirect($currentAction);
        }
        if (!empty($this->session->data['ya_module_flash_messages'])) {
            foreach ($this->session->data['ya_module_flash_messages'] as $type => $messages) {
                $this->data[$type] = $messages;
            }
            unset($this->session->data['ya_module_flash_messages']);
        }
        $this->data['errors'] = $this->error;

        $url = new Url(HTTP_CATALOG, $this->config->get('config_secure') ? HTTP_CATALOG : HTTPS_CATALOG);
        $this->data['callback_url'] = str_replace("http:", "https:", $url->link('payment/yamoney/capture', '', 'SSL'));
        $this->data['wallet_redirect_url'] = str_replace("http:", "https:", $url->link('payment/yamoney/callback', '', 'SSL'));
        $this->data['shopSuccessURL'] = $url->link('checkout/success', '', 'SSL');
        $this->data['shopFailURL'] = $url->link('checkout/failure', '', 'SSL');

        $this->data['yamoney_version'] = $this->moduleVersion;

        $this->data['action'] = $currentAction;
        $this->data['cancel'] = $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL');
        $this->data['kassa_logs_link'] = $this->url->link('payment/yamoney/logs', 'token=' . $this->session->data['token'], 'SSL');

        $this->data['orderStatusList'] = $this->getValidOrderStatusList();
        $this->data['geoZoneList'] = $this->getValidGeoZoneList();
        $this->data['tax_classes'] = $this->getValidTaxRateList();
        $this->data['pages_mpos'] = $this->getCatalogPages();

        $this->data['zip_enabled'] = function_exists('zip_open');
        $this->data['curl_enabled'] = function_exists('curl_init');
        if ($this->data['zip_enabled'] && $this->data['curl_enabled']) {
            $this->applyVersionInfo();
            $this->applyBackups();
            $this->data['update_action'] = $this->url->link('payment/yamoney/checkVersion', 'token=' . $this->session->data['token'], true);
            $this->data['backup_action'] = $this->url->link('payment/yamoney/backups', 'token=' . $this->session->data['token'], true);
        }

        $post = $this->request->post;
        foreach ($this->getModel()->getPaymentMethods() as $method) {
            foreach ($method->getSettings() as $param) {
                $this->data[$param] = isset($post[$param]) ? $post[$param] : $this->config->get($param);
            }
            if ($method instanceof YandexMoneyPaymentKassa) {
                $this->data['name_methods'] = $method->getPaymentMethods();
                $this->data['kassa_taxes'] = $method->getTaxRates();
            } elseif ($method instanceof YandexMoneyPaymentMoney) {
                $this->data['wallet_name_methods'] = $method->getPaymentMethods();
            }
        }

        $this->data['lang'] = $this->language;
        $this->data['kassa'] = $this->getModel()->getPaymentMethod(YandexMoneyPaymentMethod::MODE_KASSA);
        $this->data['wallet'] = $this->getModel()->getPaymentMethod(YandexMoneyPaymentMethod::MODE_MONEY);
        $this->data['billing'] = $this->getModel()->getPaymentMethod(YandexMoneyPaymentMethod::MODE_BILLING);

        $this->data['breadcrumbs'] = array();

        $this->document->setTitle($this->language->get('heading_title'));
        $this->template = 'payment/yamoney.tpl';
        $this->children = array(
            'common/header',
            'common/footer'
        );

        $this->response->setOutput($this->render());
    }

    public function logs()
    {
        $this->language->load('payment/yamoney');
        $fileName = DIR_LOGS . '/yandex-money.log';

        if (isset($_POST['clear-logs']) && $_POST['clear-logs'] === '1') {
            if (file_exists($fileName)) {
                unlink($fileName);
            }
        }
        if (isset($_POST['download']) && $_POST['download'] === '1') {
            if (file_exists($fileName)) {
                $downloadFileName = 'yandex_money_log.' . uniqid(true) . '.log';
                if (copy($fileName, DIR_DOWNLOAD . $downloadFileName)) {
                    $this->redirect(HTTP_CATALOG . 'download/' . $downloadFileName);
                } else {
                    echo 'Directory "' . DIR_DOWNLOAD . '" now writable';
                }
            }
        } else {
            $files = glob(DIR_DOWNLOAD . 'yandex_money_log.*.log');
            if (!empty($files)) {
                foreach ($files as $tmpFileName) {
                    $time = filemtime($tmpFileName);
                    if (time() - $time > 600) {
                        unlink($tmpFileName);
                    }
                }
            }
        }

        $logs = '';
        if (file_exists($fileName)) {
            $logs = file_get_contents($fileName);
        }
        $this->data['lang'] = $this->language;
        $this->data['logs'] = $logs;
        $this->data['breadcrumbs'] = array(
            array(
                'name' => 'Журнал сообщений',
                'link' => $this->url->link('payment/yamoney/logs', 'token=' . $this->session->data['token'], true),
            ),
        );

        $this->template = 'payment/yamoney/logs.tpl';
        $this->children = array(
            'common/header',
            'common/footer'
        );
        $this->response->setOutput($this->render());
    }

    public function backups()
    {
        $link = $this->url->link('payment/yamoney', 'token=' . $this->session->data['token'], 'SSL');

        if (!empty($this->request->post['action'])) {
            $logs = $this->url->link('payment/yamoney/logs', 'token=' . $this->session->data['token'], 'SSL');
            switch ($this->request->post['action']) {
                case 'restore';
                    if (!empty($this->request->post['file_name'])) {
                        if ($this->getModel()->restoreBackup($this->request->post['file_name'])) {
                            $this->session->data['flash_message'] = 'Версия модуля ' . $this->request->post['version'] . ' была успешно восстановлена из резервной копии ' . $this->request->post['file_name'];
                            $this->redirect($link);
                        }
                        $this->data['errors'][] = 'Не удалось восстановить данные из резервной копии, подробную информацию о произошедшей ошибке можно найти в <a href="' . $logs . '">логах модуля</a>';
                    }
                    break;
                case 'remove':
                    if (!empty($this->request->post['file_name'])) {
                        if ($this->getModel()->removeBackup($this->request->post['file_name'])) {
                            $this->session->data['flash_message'] = 'Резервная копия ' . $this->request->post['file_name'] . ' был успешно удалён';
                            $this->redirect($link);
                        }
                        $this->data['errors'][] = 'Не удалось удалить резервную копию ' . $this->request->post['file_name'] . ', подробную информацию о произошедшей ошибке можно найти в <a href="' . $logs . '">логах модуля</a>';
                    }
                    break;
            }
        }

        $this->applyBackups();

        $this->template = 'payment/yamoney/backups.tpl';
        $this->children = array(
            'common/header',
            'common/footer'
        );
        $this->response->setOutput($this->render());
    }

    public function checkVersion()
    {
        $link = $this->url->link('payment/yamoney', 'token=' . $this->session->data['token'], 'SSL');

        if (isset($this->request->post['force'])) {
            $this->applyVersionInfo(true);
            $this->redirect($link);
        }

        $versionInfo = $this->applyVersionInfo();

        if (isset($this->request->post['update']) && $this->request->post['update'] == '1') {
            $fileName = $this->getModel()->downloadLastVersion($versionInfo['tag']);
            $logs = $this->url->link('payment/yamoney/logs', 'token=' . $this->session->data['token'], 'SSL');
            if (!empty($fileName)) {
                if ($this->getModel()->createBackup($this->moduleVersion)) {
                    if ($this->getModel()->unpackLastVersion($fileName)) {
                        $this->session->data['flash_message'] = 'Версия модуля ' . $this->request->post['version'] . ' была успешно загружена и установлена';
                        $this->redirect($link);
                    } else {
                        $this->data['errors'][] = 'Не удалось распаковать загруженный архив ' . $fileName . '. Подробная информация об ошибке — в <a href="' . $logs . '">логах модуля</a>';
                    }
                } else {
                    $this->data['errors'][] = 'Не удалось создать резервную копию установленной версии модуля. Подробная информация об ошибке — в <a href="' . $logs . '">логах модуля</a>';
                }
            } else {
                $this->data['errors'][] = 'Не удалось загрузить архив, попробуйте еще раз. Подробная информация об ошибке — в <a href="' . $logs . '">логах модуля</a>';
            }
        }

        $this->template = 'payment/yamoney/check_module_version.tpl';
        $this->children = array(
            'common/header',
            'common/footer'
        );
    }

    public function payments()
    {
        $this->language->load('payment/yamoney');

        $this->getModel()->init($this->config);
        $kassa = $this->getModel()->getPaymentMethod(YandexMoneyPaymentMethod::MODE_KASSA);
        if (!$kassa->isEnabled()) {
            $url = $this->url->link('payment/yamoney', 'token=' . $this->session->data['token'], true);
            $this->redirect($url);
        }
        $payments = $this->getModel()->getPayments();

        if (isset($this->request->get['update_statuses'])) {
            $this->getModel()->updatePaymentsStatuses($payments);
        }

        $this->document->setTitle('Список платежей');
        $this->template = 'payment/yamoney/kassa_payments_list.tpl';
        $this->children = array(
            'common/header',
            'common/footer',
        );

        $this->data['lang'] = $this->language;
        $this->data['payments'] = $payments;
        $this->data['breadcrumbs'] = array(
            array(
                'name' => 'Платежи',
                'link' => $this->url->link('payment/yamoney/payments', 'token=' . $this->session->data['token'], true),
            ),
        );
        $this->response->setOutput($this->render());
    }

    private function validate($data)
    {
        $this->language->load('payment/yamoney');
        $this->error = array();
        if (!$this->user->hasPermission('modify', 'payment/yamoney')) {
            $this->error[] = $this->language->get('error_permission');
            return false;
        }
        foreach ($this->getModel()->getPaymentMethods() as $method) {
            foreach ($method->getSettings() as $param) {
                if (!isset($data[$param])) {
                    $this->request->post[$param] = '';
                    $data[$param] = '';
                }
            }
        }

        if (isset($data['ya_kassa_enable']) && $data['ya_kassa_enable'] == '1') {
            $mode = YandexMoneyPaymentMethod::MODE_KASSA;
        } elseif (isset($data['ya_money_on']) && $data['ya_money_on'] == '1') {
            $mode = YandexMoneyPaymentMethod::MODE_MONEY;
        } elseif (isset($data['ya_billing_enable']) && $data['ya_billing_enable'] == '1') {
            $mode = YandexMoneyPaymentMethod::MODE_BILLING;
        } else {
            $mode = YandexMoneyPaymentMethod::MODE_NONE;
        }

        $method = $this->getModel()->getPaymentMethod($mode);
        foreach ($method->getRequiredFields() as $field) {
            if (!$this->request->post[$field]) {
                $this->error[] = $this->language->get('error_' . $field);
            }
        }

        if ($method->isModeKassa()) {
            if ($data['ya_kassa_payment_mode'] == 'shop' && empty($data['ya_kassa_payment_options'])) {
                $this->error[] = $this->language->get('error_empty_payment');
            }
            if (!empty($data['ya_kassa_password'])) {
                $prefix = substr($data['ya_kassa_password'], 0, 5);
                if ($prefix !== 'test_' && $prefix !== 'live_') {
                    $this->error[] = $this->language->get('error_invalid_shop_password');
                }
            }
        } elseif ($method->isModeMoney()) {
            if (count($data['ya_money_payment_options']) == 0) {
                $this->error[] = $this->language->get('error_empty_payment');
            }
        }
        if (!empty($this->error)) {
            $_SESSION['ya_module_flash_messages']['error'] = $this->error;
        }
        return empty($this->error);
    }

    private function saveSettings($data)
    {
        $settings = array();
        $settings['yamoney_status'] = '1';

        if (isset($data['ya_kassa_enable']) && $data['ya_kassa_enable'] == '1') {
            $settings['ya_mode'] = YandexMoneyPaymentMethod::MODE_KASSA;
            $settings['ya_money_on'] = '0';
            $settings['ya_billing_enable'] = '0';
        } elseif (isset($data['ya_money_on']) && $data['ya_money_on'] == '1') {
            $settings['ya_mode'] = YandexMoneyPaymentMethod::MODE_MONEY;
            $settings['ya_kassa_enable'] = '0';
            $settings['ya_billing_enable'] = '0';
        } elseif (isset($data['ya_billing_enable']) && $data['ya_billing_enable'] == '1') {
            $settings['ya_mode'] = YandexMoneyPaymentMethod::MODE_BILLING;
            $settings['ya_kassa_enable'] = '0';
            $settings['ya_money_on'] = '0';
        } else {
            $settings['ya_mode'] = YandexMoneyPaymentMethod::MODE_NONE;
            $settings['yamoney_status'] = '0';
            $settings['ya_kassa_enable'] = '0';
            $settings['ya_money_on'] = '0';
            $settings['ya_billing_enable'] = '0';
        }

        foreach ($this->getModel()->getPaymentMethods() as $method) {
            foreach ($method->getSettings() as $param) {
                $settings[$param] = false;
                if (isset($data[$param])) {
                    $settings[$param] = $data[$param];
                }
            }
        }

        $this->model_setting_setting->editSetting('yamoney', $settings);

        $_SESSION['ya_module_flash_messages']['success'] = $this->language->get('text_success');
        $updater = $this->sendStatistics();
        if ($updater) {
            $_SESSION['ya_module_flash_messages']['attention'] = $updater;
        }
    }

    private function getValidOrderStatusList()
    {
        $this->load->model('localisation/order_status');
        $list = array();
        $dataSet = $this->model_localisation_order_status->getOrderStatuses();
        if (!empty($dataSet)) {
            foreach ($dataSet as $row) {
                $list[$row['order_status_id']] = $row['name'];
            }
        }
        return $list;
    }

    private function getValidGeoZoneList()
    {
        $this->load->model('localisation/geo_zone');
        $list = array();
        $dataSet = $this->model_localisation_geo_zone->getGeoZones();
        if (!empty($dataSet)) {
            foreach ($dataSet as $row) {
                $list[$row['geo_zone_id']] = $row['name'];
            }
        }
        return $list;
    }

    private function getValidTaxRateList()
    {
        $this->load->model('localisation/tax_class');
        $list = array();
        $dataSet = $this->model_localisation_tax_class->getTaxClasses();
        if (!empty($dataSet)) {
            foreach ($dataSet as $row) {
                $list[$row['tax_class_id']] = $row['title'];
            }
        }
        return $list;
    }

    private function getCatalogPages()
    {
        $this->load->model('catalog/information');
        $list = array();
        $dataSet = $this->model_catalog_information->getInformations();
        if (!empty($dataSet)) {
            foreach ($dataSet as $row) {
                $list[$row['information_id']] = $row['title'];
            }
        }
        return $list;
    }

    private function sendStatistics()
    {
        return false;
        $this->language->load('payment/yamoney');
        $this->load->model('setting/setting');
        $setting = $this->model_setting_setting->getSetting('yamoney');
        $array = array(
            'url' => $this->config->get('config_secure') ? HTTP_CATALOG : HTTPS_CATALOG,
            'cms' => 'opencart',
            'version' => VERSION,
            'ver_mod' => $this->moduleVersion,
            'yacms' => false,
            'email' => $this->config->get('config_email'),
            'shopid' => $setting['ya_kassa_shop_id'],
            'settings' => array(
                'kassa' => (bool) ($setting['ya_kassa_enable']=='1')?true:false,
                'kassa_epl' => (bool) ($setting['ya_kassa_enable']=='1' && $setting['ya_kassa_payment_mode']=='kassa')?true:false,
                'p2p' => (bool) ($setting['ya_money_on']=='1')?true:false
            )
        );

        $array_crypt = base64_encode(serialize($array));

        $url = 'https://statcms.yamoney.ru/v2/';
        $curlOpt = array(
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLINFO_HEADER_OUT => true,
            CURLOPT_POST => true,
            CURLOPT_FRESH_CONNECT => TRUE,
        );

        $curlOpt[CURLOPT_HTTPHEADER] = array('Content-Type: application/x-www-form-urlencoded');
        $curlOpt[CURLOPT_POSTFIELDS] = http_build_query(array('data' => $array_crypt, 'lbl'=>1));

        $curl = curl_init($url);
        curl_setopt_array($curl, $curlOpt);
        $rbody = curl_exec($curl);
        $errno = curl_errno($curl);
        $error = curl_error($curl);
        $rcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        $json=json_decode($rbody);
        if ($rcode==200 && isset($json->new_version)){
            return sprintf($this->language->get('text_need_update'), $json->new_version);
        } else {
            return false;
        }
    }

    private function applyVersionInfo($force = false)
    {
        $versionInfo = $this->getModel()->checkModuleVersion($force);
        if (version_compare($versionInfo['version'], $this->moduleVersion) > 0) {
            $this->data['new_version_available'] = true;
            $this->data['changelog'] = $this->getModel()->getChangeLog($this->moduleVersion, $versionInfo['version']);
            $this->data['newVersion'] = $versionInfo['version'];
        } else {
            $this->data['new_version_available'] = false;
            $this->data['changelog'] = '';
            $this->data['newVersion'] = $this->moduleVersion;
        }
        $this->data['currentVersion'] = $this->moduleVersion;
        $this->data['newVersionInfo'] = $versionInfo;

        return $versionInfo;
    }

    private function applyBackups()
    {
        if (!empty($this->session->data['flash_message'])) {
            $this->data['success'] = $this->session->data['flash_message'];
            unset($this->session->data['flash_message']);
        }

        $this->data['backups'] = $this->getModel()->getBackupList();
    }
}
