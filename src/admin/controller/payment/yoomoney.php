<?php

use YooKassa\Model\CurrencyCode;

/**
 * Class ControllerPaymentYoomoney
 *
 * @property ModelPaymentYoomoney $model_payment_yoomoney
 *
 * @property ModelLocalisationOrderStatus $model_localisation_order_status
 * @property ModelLocalisationGeoZone $model_localisation_geo_zone
 * @property ModelLocalisationTaxClass $model_localisation_tax_class
 * @property ModelCatalogInformation $model_catalog_information
 * @property ModelSettingSetting $model_setting_setting
 */
class ControllerPaymentYoomoney extends Controller
{
    const WIDGET_INSTALL_STATUS_SUCCESS = true;
    const WIDGET_INSTALL_STATUS_FAIL    = false;

    /**
     * @var array
     */
    private $error = array();

    /**
     * @var string
     */
    private $moduleVersion = '2.2.1';

    /**
     * @var integer
     */
    private $npsRetryAfterDays = 90;

    /**
     * @var ModelPaymentYoomoney
     */
    private $_model;

    /**
     * @return ModelPaymentYoomoney
     */
    private function getModel()
    {
        if ($this->_model === null) {
            $this->load->model('payment/yoomoney');
            $this->_model = $this->model_payment_yoomoney;
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
        $this->language->load('payment/yoomoney');
        $this->load->model('setting/setting');

        $this->getModel()->init($this->config);
        $currentAction = $this->url->link('payment/yoomoney', 'token='.$this->session->data['token'], 'SSL');
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate($this->request->post)) {
            $this->saveSettings($this->request->post);
            $this->redirect($currentAction);
        }
        if (!empty($this->session->data['yoomoney_module_flash_messages'])) {
            foreach ($this->session->data['yoomoney_module_flash_messages'] as $type => $messages) {
                $this->data[$type] = $messages;
            }
            unset($this->session->data['yoomoney_module_flash_messages']);
        }
        $this->data['errors'] = $this->error;

        $url                               = new Url(HTTP_CATALOG,
            $this->config->get('config_secure') ? HTTP_CATALOG : HTTPS_CATALOG);
        $this->data['callback_url']        = str_replace("http:", "https:",
            $url->link('payment/yoomoney/capture', '', 'SSL'));
        $this->data['wallet_redirect_url'] = str_replace("http:", "https:",
            $url->link('payment/yoomoney/callback', '', 'SSL'));
        $this->data['shopSuccessURL']      = $url->link('checkout/success', '', 'SSL');
        $this->data['shopFailURL']         = $url->link('checkout/failure', '', 'SSL');

        $this->data['yoomoney_version'] = $this->moduleVersion;

        $this->data['action']              = $currentAction;
        $this->data['cancel']              = $this->url->link('extension/payment',
            'token='.$this->session->data['token'], 'SSL');
        $this->data['kassa_logs_link']     = $this->url->link('payment/yoomoney/logs',
            'token='.$this->session->data['token'], 'SSL');
        $this->data['kassa_payments_link'] = $this->url->link('payment/yoomoney/payments',
            'token='.$this->session->data['token'], 'SSL');
        $this->data['install_widget_link'] = $this->url->link('payment/yoomoney/installWidget',
            'token='.$this->session->data['token'], 'SSL');

        $this->data['kassa_currencies'] = $this->createKassaCurrencyList();

        $this->data['orderStatusList'] = $this->getValidOrderStatusList();
        $this->data['geoZoneList']     = $this->getValidGeoZoneList();
        $this->data['tax_classes']     = $this->getValidTaxRateList();
        $this->data['pages_mpos']      = $this->getCatalogPages();

        $this->data['zip_enabled']  = function_exists('zip_open');
        $this->data['curl_enabled'] = function_exists('curl_init');
        if ($this->data['zip_enabled'] && $this->data['curl_enabled']) {
            $this->applyVersionInfo();
            $this->applyBackups();
            $this->data['update_action'] = $this->url->link('payment/yoomoney/checkVersion',
                'token='.$this->session->data['token'], true);
            $this->data['backup_action'] = $this->url->link('payment/yoomoney/backups',
                'token='.$this->session->data['token'], true);
        }

        $post = $this->request->post;
        foreach ($this->getModel()->getPaymentMethods() as $method) {
            foreach ($method->getSettings() as $param) {
                $this->data[$param] = isset($post[$param]) ? $post[$param] : $this->config->get($param);
            }
            if ($method instanceof YooMoneyPaymentKassa) {
                $this->data['name_methods']       = $method->getPaymentMethods();
                $this->data['kassa_taxes']        = $method->getTaxRates();
                $this->data['kassa_tax_systems']  = $method->getTaxSystemCodes();
                $this->data['b2bTaxRates']        = $method->getB2bTaxRatesList();
                $this->data['paymentModeEnum']    = $method->getPaymentModeEnum();
                $this->data['paymentSubjectEnum'] = $method->getPaymentSubjectEnum();
            } elseif ($method instanceof YooMoneyPaymentMoney) {
                $this->data['wallet_name_methods'] = $method->getPaymentMethods();
            }
        }

        if ($this->data['yoomoney_kassa_enable']) {
            $tab = 'tab-kassa';
        } elseif ($this->data['yoomoney_wallet_enable']) {
            $tab = 'tab-money';
        } else {
            $tab = 'tab-kassa';
        }

        if (!empty($post['last_active_tab'])) {
            $this->session->data['last-active-tab'] = $post['last_active_tab'];
        } else {
            $this->session->data['last-active-tab'] = $tab;
        }

        $this->data['lastActiveTab'] = $this->session->data['last-active-tab'];

        $this->data['lang']    = $this->language;
        $this->data['kassa']   = $this->getModel()->getPaymentMethod(YooMoneyPaymentMethod::MODE_KASSA);
        $this->data['wallet']  = $this->getModel()->getPaymentMethod(YooMoneyPaymentMethod::MODE_MONEY);

        $this->data['breadcrumbs'] = array();

        $this->data['yoomoney_nps_prev_vote_time']    = $this->config->get('yoomoney_nps_prev_vote_time');
        $this->data['yoomoney_nps_current_vote_time'] = time();
        $this->data['callback_off_nps']         = $this->url->link('payment/yoomoney/off_nps',
            'token='.$this->session->data['token'], 'SSL');
        $isTimeForVote                          = $this->data['yoomoney_nps_current_vote_time'] > (int)$this->data['yoomoney_nps_prev_vote_time']
                                                                                            + $this->npsRetryAfterDays * 86400;
        $this->data['is_needed_show_nps']       = $isTimeForVote
                                                  && substr($this->data['yoomoney_kassa_password'], 0, 5) === 'live_'
                                                  && $this->language->get('nps_text');

        $this->document->setTitle($this->language->get('heading_title'));
        $this->template = 'payment/yoomoney.tpl';
        $this->children = array(
            'common/header',
            'common/footer',
        );

        $this->response->setOutput($this->render());
    }

    /**
     * Экшен для отмены показа  NPS-блока
     */
    public function off_nps()
    {
        $this->load->model('setting/setting');
        $this->model_setting_setting->editSettingValue('yoomoney', 'yoomoney_nps_prev_vote_time', time());
    }

    public function logs()
    {
        $this->language->load('payment/yoomoney');
        $fileName = DIR_LOGS.'/yoomoney.log';

        if (isset($_POST['clear-logs']) && $_POST['clear-logs'] === '1') {
            if (file_exists($fileName)) {
                unlink($fileName);
            }
        }
        if (isset($_POST['download']) && $_POST['download'] === '1') {
            if (file_exists($fileName)) {
                $downloadFileName = 'yoomoney_log.'.uniqid(true).'.log';
                if (copy($fileName, DIR_DOWNLOAD.$downloadFileName)) {
                    $this->redirect(HTTP_CATALOG.'download/'.$downloadFileName);
                } else {
                    echo 'Directory "'.DIR_DOWNLOAD.'" now writable';
                }
            }
        } else {
            $files = glob(DIR_DOWNLOAD.'yoomoney_log.*.log');
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
        $this->data['lang']        = $this->language;
        $this->data['logs']        = $logs;
        $this->data['breadcrumbs'] = array(
            array(
                'name' => 'Журнал сообщений',
                'link' => $this->url->link('payment/yoomoney/logs', 'token='.$this->session->data['token'], true),
            ),
        );

        $this->template = 'payment/yoomoney/logs.tpl';
        $this->children = array(
            'common/header',
            'common/footer',
        );
        $this->response->setOutput($this->render());
    }

    public function backups()
    {
        $link = $this->url->link('payment/yoomoney', 'token='.$this->session->data['token'], 'SSL');

        if (!empty($this->request->post['action'])) {
            $logs = $this->url->link('payment/yoomoney/logs', 'token='.$this->session->data['token'], 'SSL');
            switch ($this->request->post['action']) {
                case 'restore';
                    if (!empty($this->request->post['file_name'])) {
                        if ($this->getModel()->restoreBackup($this->request->post['file_name'])) {
                            $this->session->data['flash_message'] = 'Версия модуля '.$this->request->post['version'].' была успешно восстановлена из резервной копии '.$this->request->post['file_name'];
                            $this->redirect($link);
                        }
                        $this->data['errors'][] = sprintf($this->language->get('updater_error_text_restore'), $logs);
                    }
                    break;
                case 'remove':
                    if (!empty($this->request->post['file_name'])) {
                        if ($this->getModel()->removeBackup($this->request->post['file_name'])) {
                            $this->session->data['flash_message'] = sprintf($this->language->get('updater_restore_success_text'),
                                $this->request->post['file_name']);
                            $this->redirect($link);
                        }
                        $this->data['errors'][] = sprintf($this->language->get('updater_error_text_remove'),
                            $this->request->post['file_name'], $logs);
                    }
                    break;
            }
        }

        $this->applyBackups();

        $this->template = 'payment/yoomoney/backups.tpl';
        $this->children = array(
            'common/header',
            'common/footer',
        );
        $this->response->setOutput($this->render());
    }

    public function checkVersion()
    {
        $this->language->load('payment/yoomoney');

        $link = $this->url->link('payment/yoomoney', 'token='.$this->session->data['token'], 'SSL');

        if (isset($this->request->post['force'])) {
            $this->applyVersionInfo(true);
            $this->redirect($link);
        }

        $versionInfo = $this->applyVersionInfo();

        if (isset($this->request->post['update']) && $this->request->post['update'] == '1') {
            $fileName = $this->getModel()->downloadLastVersion($versionInfo['tag']);
            $logs     = $this->url->link('payment/yoomoney/logs', 'token='.$this->session->data['token'], 'SSL');
            if (!empty($fileName)) {
                if ($this->getModel()->createBackup($this->moduleVersion)) {
                    if ($this->getModel()->unpackLastVersion($fileName)) {
                        $this->session->data['flash_message'] = sprintf($this->language->get('updater_check_version_flash_message'),
                            $this->request->post['version']);
                        $this->getModel()->install();
                        $this->redirect($link);
                    } else {
                        $this->data['errors'][] = sprintf($this->language->get('updater_error_text_unpack_failed'),
                            $fileName, $logs);
                    }
                } else {
                    $this->data['errors'][] = sprintf($this->language->get('updater_error_text_create_backup_failed'),
                        $logs);
                }
            } else {
                $this->data['errors'][] = sprintf($this->language->get('updater_error_text_load_failed'),
                    $logs);
            }
        }

        $this->template = 'payment/yoomoney/check_module_version.tpl';
        $this->children = array(
            'common/header',
            'common/footer',
        );
    }

    public function payments()
    {
        $this->language->load('payment/yoomoney');

        $this->getModel()->init($this->config);
        $this->getModel()->getPaymentMethods();
        /** @var YooMoneyPaymentKassa $kassa */
        $kassa = $this->getModel()->getPaymentMethod(YooMoneyPaymentMethod::MODE_KASSA);
        if (!$kassa->isEnabled()) {
            $url = $this->url->link('payment/yoomoney', 'token='.$this->session->data['token'], true);
            $this->redirect($url);
        }
        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        }
        $limit    = 20;
        $payments = $this->getModel()->getPayments(($page - 1) * $limit, $limit);

        if (isset($this->request->get['update_statuses'])) {

            $orderIds = array();
            foreach ($payments as $row) {
                $orderIds[$row['payment_id']] = $row['order_id'];
            }

            /** @var ModelSaleOrder $orderModel */
            $this->load->model('sale/order');
            $orderModel = $this->model_sale_order;

            $paymentObjects = $this->getModel()->updatePaymentsStatuses($kassa, $payments);
            if ($this->request->get['update_statuses'] == 2) {
                foreach ($paymentObjects as $payment) {
                    $this->getModel()->log('info', 'Check payment#'.$payment['payment_id']);
                    if ($payment['status'] === \YooKassa\Model\PaymentStatus::WAITING_FOR_CAPTURE) {
                        $this->getModel()->log('info', 'Capture payment#'.$payment['payment_id']);
                        if ($this->getModel()->capturePayment($kassa, $payment, false)) {
                            $orderId   = $orderIds[$payment->getId()];
                            $orderInfo = $orderModel->getOrder($orderId);
                            if (empty($orderInfo)) {
                                $this->getModel()->log('warning', 'Empty order#'.$orderId.' in notification');
                                continue;
                            } elseif ($orderInfo['order_status_id'] <= 0) {
                                $link                         = $this->url->link('payment/yoomoney/repay',
                                    'order_id='.$orderId, true);
                                $anchor                       = '<a href="'.$link.'" class="button">Оплатить</a>';
                                $orderInfo['order_status_id'] = 1;
                                $orderModel->updateOrderStatus($orderId, $orderInfo, $anchor);
                            }
                            $this->getModel()->confirmOrderPayment($orderId, $orderInfo, $payment,
                                $kassa->getOrderStatusId());
                            $this->getModel()->log('info', sprintf($this->language->get('order_captured_text'),
                                $orderId));
                        }
                    }
                }
            }
            $link = $this->url->link('payment/yoomoney/payments', 'token='.$this->session->data['token'], 'SSL');
            $this->redirect($link);
        }

        $this->document->setTitle($this->language->get('payments_list_title'));
        $this->template = 'payment/yoomoney/kassa_payments_list.tpl';
        $this->children = array(
            'common/header',
            'common/footer',
        );

        $pagination        = new Pagination();
        $pagination->total = $this->getModel()->countPayments();
        $pagination->page  = $page;
        $pagination->limit = $limit;
        $pagination->url   = $this->url->link(
            'payment/yoomoney/payments',
            'token='.$this->session->data['token'].'&page={page}',
            true
        );

        $this->data['pagination'] = $pagination->render();

        $this->data['lang']         = $this->language;
        $this->data['payments']     = $payments;
        $this->data['breadcrumbs']  = array(
            array(
                'name' => $this->language->get('payments_list_breadcrumbs'),
                'link' => $this->url->link('payment/yoomoney/payments', 'token='.$this->session->data['token'], true),
            ),
        );
        $this->data['update_link']  = $this->url->link(
            'payment/yoomoney/payments',
            'token='.$this->session->data['token'].'&update_statuses=1',
            'SSL'
        );
        $this->data['capture_link'] = $this->url->link(
            'payment/yoomoney/payments',
            'token='.$this->session->data['token'].'&update_statuses=2',
            'SSL'
        );
        $this->response->setOutput($this->render());
    }

    public function captureForm()
    {
        $this->language->load('sale/order');
        $this->language->load('payment/yoomoney');
        $this->language->load('error/not_found');

        $this->getModel()->init($this->config);
        $this->getModel()->getPaymentMethods();
        /** @var YooMoneyPaymentKassa $kassa */
        $kassa = $this->getModel()->getPaymentMethod(YooMoneyPaymentMethod::MODE_KASSA);
        if (!$kassa->isEnabled()) {
            $url = $this->url->link('payment/yoomoney', 'token='.$this->session->data['token'], true);
            $this->redirect($url);
        }

        /** @var ModelSaleOrder $orderModel */
        $this->load->model('sale/order');
        $orderModel = $this->model_sale_order;

        $orderId = $this->request->get['order_id'];
        $order   = $orderModel->getOrder($orderId);

        try {
            $payment = $this->getModel()->getPaymentByOrderId($kassa, $orderId);
        } catch (Exception $exception) {
            $this->getModel()->log('error', $exception->getMessage());
        }

        if (!$payment) {
            $this->getModel()->log('error', $this->language->get('payment_not_found'));
            $this->children = array(
                'common/header',
                'common/footer',
            );
            $this->document->setTitle($this->language->get('captures_title'));
            $this->data['heading_title']  = $this->language->get('captures_title');
            $this->data['text_not_found'] = $this->language->get('text_not_found');
            $this->data['breadcrumbs']    = array();
            $this->template               = 'error/not_found.tpl';
            $this->response->setOutput($this->render());

            return;
        }

        $message = '';
        if ($payment->getStatus() === \YooKassa\Model\PaymentStatus::WAITING_FOR_CAPTURE
            && isset($this->request->post['action'])
        ) {
            $action = $this->request->post['action'];
            if ($action === 'capture') {
                $this->getModel()->log('info', 'Capture payment#'.$payment['payment_id']);
                $order = $this->updateOrder($orderModel, $order);
                if ($this->getModel()->capturePayment($kassa, $payment, $order)) {
                    $payment = $this->getModel()->getPaymentByOrderId($kassa, $orderId);
                    $message = $this->language->get('captures_capture_success');

                    $order['notify']          = 0;
                    $order['comment']         = $message;
                    $order['order_status_id'] = $kassa->getOrderStatusId();
                    $orderModel->addOrderHistory($orderId, $order);
                } else {
                    $message = $this->language->get('captures_capture_fail');
                }
            }
            if ($action === 'cancel') {
                $this->getModel()->log('info', 'Cancel payment#'.$payment['payment_id']);
                if ($this->getModel()->cancelPayment($kassa, $payment)) {
                    $payment = $this->getModel()->getPaymentByOrderId($kassa, $orderId);
                    $message = $this->language->get('captures_cancel_success');

                    $order['notify']          = 0;
                    $order['comment']         = $message;
                    $order['order_status_id'] = $kassa->getCancelOrderStatusId();
                    $orderModel->addOrderHistory($orderId, $order);
                } else {
                    $message = $this->language->get('captures_cancel_fail');
                }
            }
        }
        if ($message) {
            $this->getModel()->log('info', $message);
        }


        $this->document->setTitle($this->language->get('captures_title'));
        $this->template = 'payment/yoomoney/kassa_capture_form.tpl';
        $this->children = array(
            'common/header',
            'common/footer',
        );

        $this->data['language']               = $this->language;
        $this->data['order']                  = $order;
        $this->data['products']               = $orderModel->getOrderProducts($orderId);
        $this->data['vouchers']               = $orderModel->getOrderVouchers($orderId);
        $this->data['totals']                 = $orderModel->getOrderTotals($orderId);
        $this->data['payment']                = $payment;
        $this->data['message']                = $message;
        $this->data['breadcrumbs']            = array(
            array(
                'name' => $this->language->get('captures_title'),
                'link' => $this->url->link('payment/yoomoney/captureForm',
                    'token='.$this->session->data['token'].'&order_id='.$orderId, true),
            ),
        );
        $this->data['capture_action']         = $this->url->link(
            'payment/yoomoney/captureForm',
            'token='.$this->session->data['token'].'&order_id='.$orderId,
            'SSL'
        );
        $this->data['cancel_link']            = $this->url->link(
            'payment/yoomoney/captureForm',
            'token='.$this->session->data['token'].'&order_id='.$orderId.'&cancel_payment=yes',
            'SSL'
        );
        $this->data['product_link']            = $this->url->link(
            'catalog/product/update',
            'token='.$this->session->data['token'].'&product_id=',
            'SSL'
        );
        $this->data['capture_form_route']     = 'payment/yoomoney/captureForm';
        $this->data['capture_form_token']     = $this->session->data['token'];
        $this->data['capture_form_order_id']  = $orderId;
        $this->data['is_waiting_for_capture'] = $payment->getStatus() === \YooKassa\Model\PaymentStatus::WAITING_FOR_CAPTURE;

        $this->response->setOutput($this->render());
    }

    public function installWidget()
    {
        $this->language->load('payment/yoomoney');

        $answer = array(
            'ok' => self::WIDGET_INSTALL_STATUS_SUCCESS,
            'error' => '',
        );

        if (!$this->enableApplePayForWidget()) {
            $answer = array(
                'ok' => self::WIDGET_INSTALL_STATUS_FAIL,
                'error' => $this->language->get('error_install_widget'),
            );
        }

        $this->getModel()->log('info', 'Install apple-pay for widget result: ' . print_r($answer, true));

        echo json_encode($answer);
    }

    private function enableApplePayForWidget()
    {
        clearstatcache();
        $rootPath = dirname(realpath(DIR_CATALOG));
        $this->getModel()->log('info', 'Root dir: ' . $rootPath);
        if (file_exists($rootPath . '/.well-known/apple-developer-merchantid-domain-association')) {
            $this->getModel()->log('info', 'apple-developer-merchantid-domain-association already exist');
            return true;
        } else if (!file_exists($rootPath . '/.well-known')) {
            if (!@mkdir($rootPath . '/.well-known', 0755)) {
                $this->getModel()->log('error', 'Create .well-known dir fail');
                return false;
            }
        }

        $result = @file_put_contents(
            $rootPath . '/.well-known/apple-developer-merchantid-domain-association',
            '7B227073704964223A2236354545363242363931303142343742414637434132324336344232453843314531353341373238363339453042333731454543434341324237463345354535222C2276657273696F6E223A312C22637265617465644F6E223A313536363930343432383738392C227369676E6174757265223A2233303830303630393261383634383836663730643031303730326130383033303830303230313031333130663330306430363039363038363438303136353033303430323031303530303330383030363039326138363438383666373064303130373031303030306130383033303832303365333330383230333838613030333032303130323032303834633330343134393531396435343336333030613036303832613836343863653364303430333032333037613331326533303263303630333535303430333063323534313730373036633635323034313730373036633639363336313734363936663665323034393665373436353637373236313734363936663665323034333431323032643230343733333331323633303234303630333535303430623063316434313730373036633635323034333635373237343639363636393633363137343639366636653230343137353734363836663732363937343739333131333330313130363033353530343061306330613431373037303663363532303439366536333265333130623330303930363033353530343036313330323535353333303165313730643331333933303335333133383330333133333332333533373561313730643332333433303335333133363330333133333332333533373561333035663331323533303233303630333535303430333063316336353633363332643733366437303264363237323666366236353732326437333639363736653566353534333334326435303532346634343331313433303132303630333535303430623063306236393466353332303533373937333734363536643733333131333330313130363033353530343061306330613431373037303663363532303439366536333265333130623330303930363033353530343036313330323535353333303539333031333036303732613836343863653364303230313036303832613836343863653364303330313037303334323030303463323135373765646562643663376232323138663638646437303930613132313864633762306264366632633238336438343630393564393461663461353431316238333432306564383131663334303765383333333166316335346333663765623332323064366261643564346566663439323839383933653763306631336133383230323131333038323032306433303063303630333535316431333031303166663034303233303030333031663036303335353164323330343138333031363830313432336632343963343466393365346566323765366334663632383663336661326262666432653462333034353036303832623036303130353035303730313031303433393330333733303335303630383262303630313035303530373330303138363239363837343734373033613266326636663633373337303265363137303730366336353265363336663664326636663633373337303330333432643631373037303663363536313639363336313333333033323330383230313164303630333535316432303034383230313134333038323031313033303832303130633036303932613836343838366637363336343035303133303831666533303831633330363038326230363031303530353037303230323330383162363063383162333532363536633639363136653633363532303666366532303734363836393733323036333635373237343639363636393633363137343635323036323739323036313665373932303730363137323734373932303631373337333735366436353733323036313633363336353730373436313665363336353230366636363230373436383635323037343638363536653230363137303730366336393633363136323663363532303733373436313665363436313732363432303734363537323664373332303631366536343230363336663665363436393734363936663665373332303666363632303735373336353263323036333635373237343639363636393633363137343635323037303666366336393633373932303631366536343230363336353732373436393636363936333631373436393666366532303730373236313633373436393633363532303733373436313734363536643635366537343733326533303336303630383262303630313035303530373032303131363261363837343734373033613266326637373737373732653631373037303663363532653633366636643266363336353732373436393636363936333631373436353631373537343638366637323639373437393266333033343036303335353164316630343264333032623330323961303237613032353836323336383734373437303361326632663633373236633265363137303730366336353265363336663664326636313730373036633635363136393633363133333265363337323663333031643036303335353164306530343136303431343934353764623666643537343831383638393839373632663765353738353037653739623538323433303065303630333535316430663031303166663034303430333032303738303330306630363039326138363438383666373633363430363164303430323035303033303061303630383261383634386365336430343033303230333439303033303436303232313030626530393537316665373165316537333562353565356166616362346337326665623434356633303138353232326337323531303032623631656264366635353032323130306431386233353061356464366464366562313734363033356231316562326365383763666133653661663663626438333830383930646338326364646161363333303832303265653330383230323735613030333032303130323032303834393664326662663361393864613937333030613036303832613836343863653364303430333032333036373331316233303139303630333535303430333063313234313730373036633635323035323666366637343230343334313230326432303437333333313236333032343036303335353034306230633164343137303730366336353230343336353732373436393636363936333631373436393666366532303431373537343638366637323639373437393331313333303131303630333535303430613063306134313730373036633635323034393665363332653331306233303039303630333535303430363133303235353533333031653137306433313334333033353330333633323333333433363333333035613137306433323339333033353330333633323333333433363333333035613330376133313265333032633036303335353034303330633235343137303730366336353230343137303730366336393633363137343639366636653230343936653734363536373732363137343639366636653230343334313230326432303437333333313236333032343036303335353034306230633164343137303730366336353230343336353732373436393636363936333631373436393666366532303431373537343638366637323639373437393331313333303131303630333535303430613063306134313730373036633635323034393665363332653331306233303039303630333535303430363133303235353533333035393330313330363037326138363438636533643032303130363038326138363438636533643033303130373033343230303034663031373131383431396437363438356435316135653235383130373736653838306132656664653762616534646530386466633462393365313333353664353636356233356165323264303937373630643232346537626261303866643736313763653838636237366262363637306265633865383239383466663534343561333831663733303831663433303436303630383262303630313035303530373031303130343361333033383330333630363038326230363031303530353037333030313836326136383734373437303361326632663666363337333730326536313730373036633635326536333666366432663666363337333730333033343264363137303730366336353732366636663734363336313637333333303164303630333535316430653034313630343134323366323439633434663933653465663237653663346636323836633366613262626664326534623330306630363033353531643133303130316666303430353330303330313031666633303166303630333535316432333034313833303136383031346262623064656131353833333838396161343861393964656265626465626166646163623234616233303337303630333535316431663034333033303265333032636130326161303238383632363638373437343730336132663266363337323663326536313730373036633635326536333666366432663631373037303663363537323666366637343633363136373333326536333732366333303065303630333535316430663031303166663034303430333032303130363330313030363061326138363438383666373633363430363032306530343032303530303330306130363038326138363438636533643034303330323033363730303330363430323330336163663732383335313136393962313836666233356333353663613632626666343137656464393066373534646132386562656631396338313565343262373839663839386637396235393966393864353431306438663964653963326665303233303332326464353434323162306133303537373663356466333338336239303637666431373763326332313664393634666336373236393832313236663534663837613764316239396362396230393839323136313036393930663039393231643030303033313832303138643330383230313839303230313031333038313836333037613331326533303263303630333535303430333063323534313730373036633635323034313730373036633639363336313734363936663665323034393665373436353637373236313734363936663665323034333431323032643230343733333331323633303234303630333535303430623063316434313730373036633635323034333635373237343639363636393633363137343639366636653230343137353734363836663732363937343739333131333330313130363033353530343061306330613431373037303663363532303439366536333265333130623330303930363033353530343036313330323535353330323038346333303431343935313964353433363330306430363039363038363438303136353033303430323031303530306130383139353330313830363039326138363438383666373064303130393033333130623036303932613836343838366637306430313037303133303163303630393261383634383836663730643031303930353331306631373064333133393330333833323337333133313331333333343338356133303261303630393261383634383836663730643031303933343331316433303162333030643036303936303836343830313635303330343032303130353030613130613036303832613836343863653364303430333032333032663036303932613836343838366637306430313039303433313232303432306562656138383861366630653239356231613137383165363830633336626633376266663464356636346363643862373766336138346632393231663164306533303061303630383261383634386365336430343033303230343438333034363032323130306435336632383031396333366638373438643537623538666331636233633639653765663035636430323731313361353131323633306434653666323932343530323231303062326132616265613838333834393431363439653232313432323039663132366237336238383231386436386537333837303366613963623462656163653435303030303030303030303030227D
'
        );

        $this->getModel()->log('info', 'Result apple-pay file write ' . $result);

        return $result !== false;
    }
    /**
     * @param ModelSaleOrder $orderModel
     * @param array $order
     *
     * @return array
     */
    private function updateOrder($orderModel, $order)
    {
        $quantity = $this->request->post['quantity'];
        $totals   = $this->request->post['totals'];

        $products = $orderModel->getOrderProducts($order['order_id']);
        foreach ($products as $index => $product) {
            if ($quantity[$product['product_id']] == "0") {
                unset($products[$index]);
                continue;
            }
            $products[$index]['quantity']       = $quantity[$product['product_id']];
            $products[$index]['total']          = $products[$index]['price'] * $products[$index]['quantity'];
            $products[$index]['order_option']   = $orderModel->getOrderOptions(
                $order['order_id'],
                $product['order_product_id']
            );
            $products[$index]['order_download'] = $orderModel->getOrderDownloads(
                $order['order_id'],
                $product['order_product_id']
            );
        }
        $order['order_product'] = array_values($products);
        $order['order_voucher'] = $orderModel->getOrderVouchers($order['order_id']);
        $order['order_total']   = $orderModel->getOrderTotals($order['order_id']);

        foreach ($order['order_total'] as $index => $total) {
            if (!isset($totals[$total['code']])) {
                continue;
            }
            $order['order_total'][$index]['value'] = $totals[$total['code']];
            $order['order_total'][$index]['text']  = $this->currency->format($totals[$total['code']]);
        }
        $maxTotal       = end($order['order_total']);
        $order['total'] = $maxTotal['value'];

        $orderModel->editOrder($order['order_id'], $order);

        return $order;
    }

    /**
     * @param $data
     *
     * @return bool
     */
    private function validate($data)
    {
        $this->language->load('payment/yoomoney');
        $this->error = array();
        if (!$this->user->hasPermission('modify', 'payment/yoomoney')) {
            $this->error[] = $this->language->get('error_permission');

            return false;
        }
        foreach ($this->getModel()->getPaymentMethods() as $method) {
            foreach ($method->getSettings() as $param) {
                if (!isset($data[$param])) {
                    $this->request->post[$param] = '';
                    $data[$param]                = '';
                }
            }
        }

        if (isset($data['yoomoney_kassa_enable']) && $data['yoomoney_kassa_enable'] == '1') {
            $mode = YooMoneyPaymentMethod::MODE_KASSA;
        } elseif (isset($data['yoomoney_wallet_enable']) && $data['yoomoney_wallet_enable'] == '1') {
            $mode = YooMoneyPaymentMethod::MODE_MONEY;
        } else {
            $mode = YooMoneyPaymentMethod::MODE_NONE;
        }

        $method = $this->getModel()->getPaymentMethod($mode);
        foreach ($method->getRequiredFields() as $field) {
            if (!$this->request->post[$field]) {
                $this->error[] = $this->language->get('error_'.$field);
            }
        }

        if ($method->isModeKassa()) {
            if ($data['yoomoney_kassa_payment_mode'] == 'shop' && empty($data['yoomoney_kassa_payment_options'])) {
                $this->error[] = $this->language->get('error_empty_payment');
            }
            if (!empty($data['yoomoney_kassa_password'])) {
                $prefix = substr($data['yoomoney_kassa_password'], 0, 5);
                if ($prefix !== 'test_' && $prefix !== 'live_') {
                    $this->error[] = $this->language->get('error_invalid_shop_password');
                }
            }
            if (empty($this->error)) {
                if (!$method->checkConnection($data, $this->getModel())) {
                    $this->error[] = $this->language->get('error_invalid_shop_id_or_password');
                }
            }
        } elseif ($method->isModeMoney()) {
            if (count($data['yoomoney_wallet_payment_options']) == 0) {
                $this->error[] = $this->language->get('error_empty_payment');
            }
        }
        if (!empty($this->error)) {
            $_SESSION['yoomoney_module_flash_messages']['error'] = $this->error;
        }

        return empty($this->error);
    }

    private function saveSettings($data)
    {
        $settings                   = array();
        $settings['yoomoney_status'] = '1';

        if (isset($data['yoomoney_kassa_enable']) && $data['yoomoney_kassa_enable'] == '1') {
            $settings['yoomoney_mode']           = YooMoneyPaymentMethod::MODE_KASSA;
            $settings['yoomoney_wallet_enable']       = '0';
        } elseif (isset($data['yoomoney_wallet_enable']) && $data['yoomoney_wallet_enable'] == '1') {
            $settings['yoomoney_mode']           = YooMoneyPaymentMethod::MODE_MONEY;
            $settings['yoomoney_kassa_enable']   = '0';
        } else {
            $settings['yoomoney_mode']           = YooMoneyPaymentMethod::MODE_NONE;
            $settings['yoomoney_status']    = '0';
            $settings['yoomoney_kassa_enable']   = '0';
            $settings['yoomoney_wallet_enable']       = '0';
        }
        $settings['yoomoney_nps_prev_vote_time'] = $data['yoomoney_nps_prev_vote_time'];

        foreach ($this->getModel()->getPaymentMethods() as $method) {
            foreach ($method->getSettings() as $param) {
                $settings[$param] = false;
                if (isset($data[$param])) {
                    $settings[$param] = $data[$param];
                }
            }
            if ($settings['yoomoney_mode'] == $method->getMode()) {
                $settings['yoomoney_sort_order'] = $method->getSortOrder();
            }
        }

        $settings['yoomoney_kassa_currency']                     = isset($data['yoomoney_kassa_currency']) ? $data['yoomoney_kassa_currency'] : CurrencyCode::RUB;
        $settings['yoomoney_kassa_currency_convert']             = isset($data['yoomoney_kassa_currency_convert']) ? $data['yoomoney_kassa_currency_convert'] : "";

        $settings['yoomoney_kassa_b2b_sberbank_enabled']         = isset($data['yoomoney_kassa_b2b_sberbank_enabled']) ? $data['yoomoney_kassa_b2b_sberbank_enabled'] : "";
        $settings['yoomoney_kassa_b2b_sberbank_payment_purpose'] = isset($data['yoomoney_kassa_b2b_sberbank_payment_purpose']) ? $data['yoomoney_kassa_b2b_sberbank_payment_purpose'] : "";
        $settings['yoomoney_kassa_b2b_tax_rate_default']         = isset($data['yoomoney_kassa_b2b_tax_rate_default']) ? $data['yoomoney_kassa_b2b_tax_rate_default'] : "";
        $settings['yoomoney_kassa_b2b_tax_rates']                = isset($data['yoomoney_kassa_b2b_tax_rates']) ? $data['yoomoney_kassa_b2b_tax_rates'] : "";
        $settings['yoomoney_kassa_default_payment_mode']         = isset($data['yoomoney_kassa_default_payment_mode']) ? $data['yoomoney_kassa_default_payment_mode'] : "";
        $settings['yoomoney_kassa_default_payment_subject']      = isset($data['yoomoney_kassa_default_payment_subject']) ? $data['yoomoney_kassa_default_payment_subject'] : "";
        $settings['yoomoneyb2bsberbank_status']                  = $settings['yoomoney_status'];

        $this->model_setting_setting->editSetting('yoomoney', $settings);

        $_SESSION['yoomoney_module_flash_messages']['success'] = $this->language->get('text_success');

    }

    private function getValidOrderStatusList()
    {
        $this->load->model('localisation/order_status');
        $list    = array();
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
        $list    = array();
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
        $list    = array();
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
        $list    = array();
        $dataSet = $this->model_catalog_information->getInformations();
        if (!empty($dataSet)) {
            foreach ($dataSet as $row) {
                $list[$row['information_id']] = $row['title'];
            }
        }

        return $list;
    }

    private function applyVersionInfo($force = false)
    {
        $versionInfo = $this->getModel()->checkModuleVersion($force);
        if (isset($versionInfo['version']) && version_compare($versionInfo['version'], $this->moduleVersion) > 0) {
            $this->data['new_version_available'] = true;
            $this->data['changelog']             = $this->getModel()->getChangeLog($this->moduleVersion,
                $versionInfo['version']);
            $this->data['newVersion']            = $versionInfo['version'];
        } else {
            $this->data['new_version_available'] = false;
            $this->data['changelog']             = '';
            $this->data['newVersion']            = $this->moduleVersion;
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


    /**
     * @return array
     */
    private function createKassaCurrencyList()
    {
        $this->load->model('localisation/currency');
        $all_currencies = $this->model_localisation_currency->getCurrencies();
        $kassa_currencies = CurrencyCode::getEnabledValues();

        $available_currencies = array();
        foreach ($all_currencies as $key => $item) {
            if (in_array($key, $kassa_currencies) && $item['status'] == 1) {
                $available_currencies[$key] = $item;
            }
        }

        return array_merge(array(
            'RUB' => array(
                'title' => 'Российский рубль',
                'code' => CurrencyCode::RUB,
                'symbol_left' => '',
                'symbol_right' => '₽',
                'decimal_place' => '2',
                'status' => '1',
            )
        ), $available_currencies);
    }
}
