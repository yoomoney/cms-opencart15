<?php

use YandexCheckout\Model\CurrencyCode;

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
 * @property ModelYamoneyOfferwall $model_yamoney_offerwall
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
    private $moduleVersion = '1.4.0';

    /**
     * @var integer
     */
    private $npsRetryAfterDays = 90;

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
        $currentAction = $this->url->link('payment/yamoney', 'token='.$this->session->data['token'], 'SSL');
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

        $url                               = new Url(HTTP_CATALOG,
            $this->config->get('config_secure') ? HTTP_CATALOG : HTTPS_CATALOG);
        $this->data['callback_url']        = str_replace("http:", "https:",
            $url->link('payment/yamoney/capture', '', 'SSL'));
        $this->data['wallet_redirect_url'] = str_replace("http:", "https:",
            $url->link('payment/yamoney/callback', '', 'SSL'));
        $this->data['shopSuccessURL']      = $url->link('checkout/success', '', 'SSL');
        $this->data['shopFailURL']         = $url->link('checkout/failure', '', 'SSL');

        $this->data['yamoney_version'] = $this->moduleVersion;

        $this->data['action']              = $currentAction;
        $this->data['cancel']              = $this->url->link('extension/payment',
            'token='.$this->session->data['token'], 'SSL');
        $this->data['kassa_logs_link']     = $this->url->link('payment/yamoney/logs',
            'token='.$this->session->data['token'], 'SSL');
        $this->data['kassa_payments_link'] = $this->url->link('payment/yamoney/payments',
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
            $this->data['update_action'] = $this->url->link('payment/yamoney/checkVersion',
                'token='.$this->session->data['token'], true);
            $this->data['backup_action'] = $this->url->link('payment/yamoney/backups',
                'token='.$this->session->data['token'], true);
        }

        $post = $this->request->post;
        foreach ($this->getModel()->getPaymentMethods() as $method) {
            foreach ($method->getSettings() as $param) {
                $this->data[$param] = isset($post[$param]) ? $post[$param] : $this->config->get($param);
            }
            if ($method instanceof YandexMoneyPaymentKassa) {
                $this->data['name_methods']       = $method->getPaymentMethods();
                $this->data['kassa_taxes']        = $method->getTaxRates();
                $this->data['b2bTaxRates']        = $method->getB2bTaxRatesList();
                $this->data['paymentModeEnum']    = $method->getPaymentModeEnum();
                $this->data['paymentSubjectEnum'] = $method->getPaymentSubjectEnum();
            } elseif ($method instanceof YandexMoneyPaymentMoney) {
                $this->data['wallet_name_methods'] = $method->getPaymentMethods();
            }
        }

        $this->data['lang']    = $this->language;
        $this->data['kassa']   = $this->getModel()->getPaymentMethod(YandexMoneyPaymentMethod::MODE_KASSA);
        $this->data['wallet']  = $this->getModel()->getPaymentMethod(YandexMoneyPaymentMethod::MODE_MONEY);
        $this->data['billing'] = $this->getModel()->getPaymentMethod(YandexMoneyPaymentMethod::MODE_BILLING);

        $this->data['breadcrumbs'] = array();

        $this->data['ya_nps_prev_vote_time']    = $this->config->get('ya_nps_prev_vote_time');
        $this->data['ya_nps_current_vote_time'] = time();
        $this->data['callback_off_nps']         = $this->url->link('payment/yamoney/off_nps',
            'token='.$this->session->data['token'], 'SSL');
        $isTimeForVote                          = $this->data['ya_nps_current_vote_time'] > (int)$this->data['ya_nps_prev_vote_time']
                                                                                            + $this->npsRetryAfterDays * 86400;
        $this->data['is_needed_show_nps']       = $isTimeForVote
                                                  && substr($this->data['ya_kassa_password'], 0, 5) === 'live_'
                                                  && $this->language->get('nps_text');

        $this->document->setTitle($this->language->get('heading_title'));
        $this->template = 'payment/yamoney.tpl';
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
        $this->model_setting_setting->editSettingValue('yamoney', 'ya_nps_prev_vote_time', time());
    }

    public function logs()
    {
        $this->language->load('payment/yamoney');
        $fileName = DIR_LOGS.'/yandex-money.log';

        if (isset($_POST['clear-logs']) && $_POST['clear-logs'] === '1') {
            if (file_exists($fileName)) {
                unlink($fileName);
            }
        }
        if (isset($_POST['download']) && $_POST['download'] === '1') {
            if (file_exists($fileName)) {
                $downloadFileName = 'yandex_money_log.'.uniqid(true).'.log';
                if (copy($fileName, DIR_DOWNLOAD.$downloadFileName)) {
                    $this->redirect(HTTP_CATALOG.'download/'.$downloadFileName);
                } else {
                    echo 'Directory "'.DIR_DOWNLOAD.'" now writable';
                }
            }
        } else {
            $files = glob(DIR_DOWNLOAD.'yandex_money_log.*.log');
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
                'link' => $this->url->link('payment/yamoney/logs', 'token='.$this->session->data['token'], true),
            ),
        );

        $this->template = 'payment/yamoney/logs.tpl';
        $this->children = array(
            'common/header',
            'common/footer',
        );
        $this->response->setOutput($this->render());
    }

    public function backups()
    {
        $link = $this->url->link('payment/yamoney', 'token='.$this->session->data['token'], 'SSL');

        if (!empty($this->request->post['action'])) {
            $logs = $this->url->link('payment/yamoney/logs', 'token='.$this->session->data['token'], 'SSL');
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

        $this->template = 'payment/yamoney/backups.tpl';
        $this->children = array(
            'common/header',
            'common/footer',
        );
        $this->response->setOutput($this->render());
    }

    public function checkVersion()
    {
        $this->language->load('payment/yamoney');

        $link = $this->url->link('payment/yamoney', 'token='.$this->session->data['token'], 'SSL');

        if (isset($this->request->post['force'])) {
            $this->applyVersionInfo(true);
            $this->redirect($link);
        }

        $versionInfo = $this->applyVersionInfo();

        if (isset($this->request->post['update']) && $this->request->post['update'] == '1') {
            $fileName = $this->getModel()->downloadLastVersion($versionInfo['tag']);
            $logs     = $this->url->link('payment/yamoney/logs', 'token='.$this->session->data['token'], 'SSL');
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

        $this->template = 'payment/yamoney/check_module_version.tpl';
        $this->children = array(
            'common/header',
            'common/footer',
        );
    }

    public function payments()
    {
        $this->language->load('payment/yamoney');

        $this->getModel()->init($this->config);
        $this->getModel()->getPaymentMethods();
        /** @var YandexMoneyPaymentKassa $kassa */
        $kassa = $this->getModel()->getPaymentMethod(YandexMoneyPaymentMethod::MODE_KASSA);
        if (!$kassa->isEnabled()) {
            $url = $this->url->link('payment/yamoney', 'token='.$this->session->data['token'], true);
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
                    if ($payment['status'] === \YandexCheckout\Model\PaymentStatus::WAITING_FOR_CAPTURE) {
                        $this->getModel()->log('info', 'Capture payment#'.$payment['payment_id']);
                        if ($this->getModel()->capturePayment($kassa, $payment, false)) {
                            $orderId   = $orderIds[$payment->getId()];
                            $orderInfo = $orderModel->getOrder($orderId);
                            if (empty($orderInfo)) {
                                $this->getModel()->log('warning', 'Empty order#'.$orderId.' in notification');
                                continue;
                            } elseif ($orderInfo['order_status_id'] <= 0) {
                                $link                         = $this->url->link('payment/yamoney/repay',
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
            $link = $this->url->link('payment/yamoney/payments', 'token='.$this->session->data['token'], 'SSL');
            $this->redirect($link);
        }

        $this->document->setTitle($this->language->get('payments_list_title'));
        $this->template = 'payment/yamoney/kassa_payments_list.tpl';
        $this->children = array(
            'common/header',
            'common/footer',
        );

        $pagination        = new Pagination();
        $pagination->total = $this->getModel()->countPayments();
        $pagination->page  = $page;
        $pagination->limit = $limit;
        $pagination->url   = $this->url->link(
            'payment/yamoney/payments',
            'token='.$this->session->data['token'].'&page={page}',
            true
        );

        $this->data['pagination'] = $pagination->render();

        $this->data['lang']         = $this->language;
        $this->data['payments']     = $payments;
        $this->data['breadcrumbs']  = array(
            array(
                'name' => $this->language->get('payments_list_breadcrumbs'),
                'link' => $this->url->link('payment/yamoney/payments', 'token='.$this->session->data['token'], true),
            ),
        );
        $this->data['update_link']  = $this->url->link(
            'payment/yamoney/payments',
            'token='.$this->session->data['token'].'&update_statuses=1',
            'SSL'
        );
        $this->data['capture_link'] = $this->url->link(
            'payment/yamoney/payments',
            'token='.$this->session->data['token'].'&update_statuses=2',
            'SSL'
        );
        $this->response->setOutput($this->render());
    }

    public function captureForm()
    {
        $this->language->load('sale/order');
        $this->language->load('payment/yamoney');
        $this->language->load('error/not_found');

        $this->getModel()->init($this->config);
        $this->getModel()->getPaymentMethods();
        /** @var YandexMoneyPaymentKassa $kassa */
        $kassa = $this->getModel()->getPaymentMethod(YandexMoneyPaymentMethod::MODE_KASSA);
        if (!$kassa->isEnabled()) {
            $url = $this->url->link('payment/yamoney', 'token='.$this->session->data['token'], true);
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
        if ($payment['status'] === \YandexCheckout\Model\PaymentStatus::WAITING_FOR_CAPTURE
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
        $this->template = 'payment/yamoney/kassa_capture_form.tpl';
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
                'link' => $this->url->link('payment/yamoney/captureForm',
                    'token='.$this->session->data['token'].'&order_id='.$orderId, true),
            ),
        );
        $this->data['capture_action']         = $this->url->link(
            'payment/yamoney/captureForm',
            'token='.$this->session->data['token'].'&order_id='.$orderId,
            'SSL'
        );
        $this->data['cancel_link']            = $this->url->link(
            'payment/yamoney/captureForm',
            'token='.$this->session->data['token'].'&order_id='.$orderId.'&cancel_payment=yes',
            'SSL'
        );
        $this->data['capture_form_route']     = 'payment/yamoney/captureForm';
        $this->data['capture_form_token']     = $this->session->data['token'];
        $this->data['capture_form_order_id']  = $orderId;
        $this->data['is_waiting_for_capture'] = $payment->getStatus() === \YandexCheckout\Model\PaymentStatus::WAITING_FOR_CAPTURE;

        $this->response->setOutput($this->render());
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
                    $data[$param]                = '';
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
                $this->error[] = $this->language->get('error_'.$field);
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
            if (empty($this->error)) {
                if (!$method->checkConnection($data, $this->getModel())) {
                    $this->error[] = $this->language->get('error_invalid_shop_id_or_password');
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
        $settings                   = array();
        $settings['yamoney_status'] = '1';

        if (isset($data['ya_kassa_enable']) && $data['ya_kassa_enable'] == '1') {
            $settings['ya_mode']           = YandexMoneyPaymentMethod::MODE_KASSA;
            $settings['ya_money_on']       = '0';
            $settings['ya_billing_enable'] = '0';
        } elseif (isset($data['ya_money_on']) && $data['ya_money_on'] == '1') {
            $settings['ya_mode']           = YandexMoneyPaymentMethod::MODE_MONEY;
            $settings['ya_kassa_enable']   = '0';
            $settings['ya_billing_enable'] = '0';
        } elseif (isset($data['ya_billing_enable']) && $data['ya_billing_enable'] == '1') {
            $settings['ya_mode']         = YandexMoneyPaymentMethod::MODE_BILLING;
            $settings['ya_kassa_enable'] = '0';
            $settings['ya_money_on']     = '0';
        } else {
            $settings['ya_mode']           = YandexMoneyPaymentMethod::MODE_NONE;
            $settings['yamoney_status']    = '0';
            $settings['ya_kassa_enable']   = '0';
            $settings['ya_money_on']       = '0';
            $settings['ya_billing_enable'] = '0';
        }
        $settings['ya_nps_prev_vote_time'] = $data['ya_nps_prev_vote_time'];

        foreach ($this->getModel()->getPaymentMethods() as $method) {
            foreach ($method->getSettings() as $param) {
                $settings[$param] = false;
                if (isset($data[$param])) {
                    $settings[$param] = $data[$param];
                }
            }
            if ($settings['ya_mode'] == $method->getMode()) {
                $settings['yamoney_sort_order'] = $method->getSortOrder();
            }
        }

        $settings['ym_kassa_currency']                     = isset($data['ym_kassa_currency']) ? $data['ym_kassa_currency'] : CurrencyCode::RUB;
        $settings['ym_kassa_currency_convert']             = isset($data['ym_kassa_currency_convert']) ? $data['ym_kassa_currency_convert'] : "";

        $settings['ya_kassa_b2b_sberbank_enabled']         = isset($data['ya_kassa_b2b_sberbank_enabled']) ? $data['ya_kassa_b2b_sberbank_enabled'] : "";
        $settings['ya_kassa_b2b_sberbank_payment_purpose'] = isset($data['ya_kassa_b2b_sberbank_payment_purpose']) ? $data['ya_kassa_b2b_sberbank_payment_purpose'] : "";
        $settings['ya_kassa_b2b_tax_rate_default']         = isset($data['ya_kassa_b2b_tax_rate_default']) ? $data['ya_kassa_b2b_tax_rate_default'] : "";
        $settings['ya_kassa_b2b_tax_rates']                = isset($data['ya_kassa_b2b_tax_rates']) ? $data['ya_kassa_b2b_tax_rates'] : "";
        $settings['ya_kassa_default_payment_mode']         = isset($data['ya_kassa_default_payment_mode']) ? $data['ya_kassa_default_payment_mode'] : "";
        $settings['ya_kassa_default_payment_subject']      = isset($data['ya_kassa_default_payment_subject']) ? $data['ya_kassa_default_payment_subject'] : "";
        $settings['yamoneyb2bsberbank_status']             = $settings['yamoney_status'];

        $this->model_setting_setting->editSetting('yamoney', $settings);

        $_SESSION['ya_module_flash_messages']['success'] = $this->language->get('text_success');
        $updater                                         = $this->sendStatistics();
        if ($updater) {
            $_SESSION['ya_module_flash_messages']['attention'] = $updater;
        }
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

    private function sendStatistics()
    {
        return false;
        $this->language->load('payment/yamoney');
        $this->load->model('setting/setting');
        $setting = $this->model_setting_setting->getSetting('yamoney');
        $array   = array(
            'url'      => $this->config->get('config_secure') ? HTTP_CATALOG : HTTPS_CATALOG,
            'cms'      => 'opencart',
            'version'  => VERSION,
            'ver_mod'  => $this->moduleVersion,
            'yacms'    => false,
            'email'    => $this->config->get('config_email'),
            'shopid'   => $setting['ya_kassa_shop_id'],
            'settings' => array(
                'kassa'     => (bool)($setting['ya_kassa_enable'] == '1') ? true : false,
                'kassa_epl' => (bool)($setting['ya_kassa_enable'] == '1' && $setting['ya_kassa_payment_mode'] == 'kassa') ? true : false,
                'p2p'       => (bool)($setting['ya_money_on'] == '1') ? true : false,
            ),
        );

        $array_crypt = base64_encode(serialize($array));

        $url     = 'https://statcms.yamoney.ru/v2/';
        $curlOpt = array(
            CURLOPT_HEADER         => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLINFO_HEADER_OUT    => true,
            CURLOPT_POST           => true,
            CURLOPT_FRESH_CONNECT  => true,
        );

        $curlOpt[CURLOPT_HTTPHEADER] = array('Content-Type: application/x-www-form-urlencoded');
        $curlOpt[CURLOPT_POSTFIELDS] = http_build_query(array('data' => $array_crypt, 'lbl' => 1));

        $curl = curl_init($url);
        curl_setopt_array($curl, $curlOpt);
        $rbody = curl_exec($curl);
        $errno = curl_errno($curl);
        $error = curl_error($curl);
        $rcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        $json = json_decode($rbody);
        if ($rcode == 200 && isset($json->new_version)) {
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
