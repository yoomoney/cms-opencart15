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
    private $moduleVersion = '2.1.2';

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
            '7B227073704964223A2236354545363242363931303142343742414637434132324336344232453843314531353341373238363339453042333731454543434341324237463345354535222C2276657273696F6E223A312C22637265617465644F6E223A313536353731323134383430382C227369676E6174757265223A223330383030363039326138363438383666373064303130373032613038303330383030323031303133313066333030643036303936303836343830313635303330343032303130353030333038303036303932613836343838366637306430313037303130303030613038303330383230336536333038323033386261303033303230313032303230383638363066363939643963636137306633303061303630383261383634386365336430343033303233303761333132653330326330363033353530343033306332353431373037303663363532303431373037303663363936333631373436393666366532303439366537343635363737323631373436393666366532303433343132303264323034373333333132363330323430363033353530343062306331643431373037303663363532303433363537323734363936363639363336313734363936663665323034313735373436383666373236393734373933313133333031313036303335353034306130633061343137303730366336353230343936653633326533313062333030393036303335353034303631333032353535333330316531373064333133363330333633303333333133383331333633343330356131373064333233313330333633303332333133383331333633343330356133303632333132383330323630363033353530343033306331663635363336333264373336643730326436323732366636623635373232643733363936373665356635353433333432643533343134653434343234663538333131343330313230363033353530343062306330623639346635333230353337393733373436353664373333313133333031313036303335353034306130633061343137303730366336353230343936653633326533313062333030393036303335353034303631333032353535333330353933303133303630373261383634386365336430323031303630383261383634386365336430333031303730333432303030343832333066646162633339636637356532303263353064393962343531326536333765326139303164643663623365306231636434623532363739386638636634656264653831613235613863323165346333336464636538653261393663326636616661313933303334356334653837613434323663653935316231323935613338323032313133303832303230643330343530363038326230363031303530353037303130313034333933303337333033353036303832623036303130353035303733303031383632393638373437343730336132663266366636333733373032653631373037303663363532653633366636643266366636333733373033303334326436313730373036633635363136393633363133333330333233303164303630333535316430653034313630343134303232343330306239616565656434363331393761346136356132393965343237313832316334353330306330363033353531643133303130316666303430323330303033303166303630333535316432333034313833303136383031343233663234396334346639336534656632376536633466363238366333666132626266643265346233303832303131643036303335353164323030343832303131343330383230313130333038323031306330363039326138363438383666373633363430353031333038316665333038316333303630383262303630313035303530373032303233303831623630633831623335323635366336393631366536333635323036663665323037343638363937333230363336353732373436393636363936333631373436353230363237393230363136653739323037303631373237343739323036313733373337353664363537333230363136333633363537303734363136653633363532303666363632303734363836353230373436383635366532303631373037303663363936333631363236633635323037333734363136653634363137323634323037343635373236643733323036313665363432303633366636653634363937343639366636653733323036663636323037353733363532633230363336353732373436393636363936333631373436353230373036663663363936333739323036313665363432303633363537323734363936363639363336313734363936663665323037303732363136333734363936333635323037333734363137343635366436353665373437333265333033363036303832623036303130353035303730323031313632613638373437343730336132663266373737373737326536313730373036633635326536333666366432663633363537323734363936363639363336313734363536313735373436383666373236393734373932663330333430363033353531643166303432643330326233303239613032376130323538363233363837343734373033613266326636333732366332653631373037303663363532653633366636643266363137303730366336353631363936333631333332653633373236633330306530363033353531643066303130316666303430343033303230373830333030663036303932613836343838366637363336343036316430343032303530303330306130363038326138363438636533643034303330323033343930303330343630323231303064613163363361653862653566363466386531316538363536393337623962363963343732626539336561633332333361313637393336653461386435653833303232313030626435616662663836396633633063613237346232666464653466373137313539636233626437313939623263613066663430396465363539613832623234643330383230326565333038323032373561303033303230313032303230383439366432666266336139386461393733303061303630383261383634386365336430343033303233303637333131623330313930363033353530343033306331323431373037303663363532303532366636663734323034333431323032643230343733333331323633303234303630333535303430623063316434313730373036633635323034333635373237343639363636393633363137343639366636653230343137353734363836663732363937343739333131333330313130363033353530343061306330613431373037303663363532303439366536333265333130623330303930363033353530343036313330323535353333303165313730643331333433303335333033363332333333343336333333303561313730643332333933303335333033363332333333343336333333303561333037613331326533303263303630333535303430333063323534313730373036633635323034313730373036633639363336313734363936663665323034393665373436353637373236313734363936663665323034333431323032643230343733333331323633303234303630333535303430623063316434313730373036633635323034333635373237343639363636393633363137343639366636653230343137353734363836663732363937343739333131333330313130363033353530343061306330613431373037303663363532303439366536333265333130623330303930363033353530343036313330323535353333303539333031333036303732613836343863653364303230313036303832613836343863653364303330313037303334323030303466303137313138343139643736343835643531613565323538313037373665383830613265666465376261653464653038646663346239336531333335366435363635623335616532326430393737363064323234653762626130386664373631376365383863623736626236363730626563386538323938346666353434356133383166373330383166343330343630363038326230363031303530353037303130313034336133303338333033363036303832623036303130353035303733303031383632613638373437343730336132663266366636333733373032653631373037303663363532653633366636643266366636333733373033303334326436313730373036633635373236663666373436333631363733333330316430363033353531643065303431363034313432336632343963343466393365346566323765366334663632383663336661326262666432653462333030663036303335353164313330313031666630343035333030333031303166663330316630363033353531643233303431383330313638303134626262306465613135383333383839616134386139396465626562646562616664616362323461623330333730363033353531643166303433303330326533303263613032616130323838363236363837343734373033613266326636333732366332653631373037303663363532653633366636643266363137303730366336353732366636663734363336313637333332653633373236633330306530363033353531643066303130316666303430343033303230313036333031303036306132613836343838366637363336343036303230653034303230353030333030613036303832613836343863653364303430333032303336373030333036343032333033616366373238333531313639396231383666623335633335366361363262666634313765646439306637353464613238656265663139633831356534326237383966383938663739623539396639386435343130643866396465396332666530323330333232646435343432316230613330353737366335646633333833623930363766643137376332633231366439363466633637323639383231323666353466383761376431623939636239623039383932313631303639393066303939323164303030303331383230313863333038323031383830323031303133303831383633303761333132653330326330363033353530343033306332353431373037303663363532303431373037303663363936333631373436393666366532303439366537343635363737323631373436393666366532303433343132303264323034373333333132363330323430363033353530343062306331643431373037303663363532303433363537323734363936363639363336313734363936663665323034313735373436383666373236393734373933313133333031313036303335353034306130633061343137303730366336353230343936653633326533313062333030393036303335353034303631333032353535333032303836383630663639396439636361373066333030643036303936303836343830313635303330343032303130353030613038313935333031383036303932613836343838366637306430313039303333313062303630393261383634383836663730643031303730313330316330363039326138363438383666373064303130393035333130663137306433313339333033383331333333313336333033323332333835613330326130363039326138363438383666373064303130393334333131643330316233303064303630393630383634383031363530333034303230313035303061313061303630383261383634386365336430343033303233303266303630393261383634383836663730643031303930343331323230343230306463316331626362653237356662363066663361663437363239636464353866396263323138333034653866323738613463313830316237353466653839363330306130363038326138363438636533643034303330323034343733303435303232313030396563323139666431396663326661326536373232393730393538333831343338366265343264353864323634303262643665383265633833323636336539333032323033363863323238616362313731393261653434626538366535386235313461636235386337396438663839373936323735653837363730373435363735333432303030303030303030303030227D'
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
