<?php

/**
 * Class ControllerPaymentYandexMoney
 *
 * @property-read Language $language
 * @property-read Currency $currency
 *
 * @property ModelCheckoutOrder $model_checkout_order
 * @property Cart $cart
 */
class ControllerPaymentYaMoney extends Controller
{
    /**
     * @var ModelPaymentYaMoney Модель работы с платежами
     */
    private $_model;

    /**
     * @var array Массив с информацией о текущем заказе
     */
    private $_orderInfo;

    /**
     * Экшен отображения страницы выбора способа оплаты
     */
    protected function index()
    {
        $this->payment($this->getOrderInfo());
    }

    /**
     * Экшен создания платежа на стороне Яндекс.Кассы
     *
     * После создания осуществляет редирект на страницу оплаты на стороне кассы. Доступен только для способа оплаты
     * через кассу, для платежей в кошелёк и с помощью платёжки просто редиректит на страницу корзины.
     */
    public function create()
    {
        $orderInfo = $this->getOrderInfo('order_id', false);
        if ($orderInfo === null) {
            $this->jsonError('Корзина пуста');
        }
        $this->language->load('payment/yamoney');
        $this->getModel()->log('info', 'Создание платежа для заказа №' . $orderInfo['order_id']);
        /** @var YandexMoneyPaymentKassa $paymentMethod */
        $paymentMethod = $this->getModel()->getPaymentMethod($this->config->get('ya_mode'));
        if (!$paymentMethod->isModeKassa()) {
            if ($paymentMethod->isModeBilling()) {
                $narrative = $this->parsePlaceholders($this->config->get('ya_billing_purpose'), $orderInfo);
                $this->updateOrderStatus($orderInfo['order_id'], $this->config->get('ya_billing_status'), $narrative);
                echo json_encode(array('success' => true));
                exit();
            }
            $this->jsonError('Ошибка настройки модуля');
        }
        if (!isset($_GET['paymentType'])) {
            $this->jsonError('Не указан способ оплаты');
        }
        $paymentType = $_GET['paymentType'];
        if (!$paymentMethod->getEPL()) {
            if (empty($paymentType)) {
                $this->jsonError('Не указан способ оплаты');
            } elseif (!$paymentMethod->isPaymentMethodEnabled($paymentType)) {
                $this->jsonError('Указан неверный способ оплаты');
            } elseif ($paymentType === \YaMoney\Model\PaymentMethodType::QIWI) {
                $phone = isset($_GET['qiwiPhone']) ? preg_replace('/[^\d]/', '', $_GET['qiwiPhone']) : '';
                if (empty($phone)) {
                    $this->jsonError('Не был указан номер телефона');
                }
            } elseif ($paymentType === \YaMoney\Model\PaymentMethodType::ALFABANK) {
                $login = isset($_GET['alphaLogin']) ? trim($_GET['alphaLogin']) : '';
                if (empty($login)) {
                    $this->jsonError('Не был указан логин в Альфа-клике');
                }
            }
        }
        $payment = $this->getModel()->createPayment($paymentMethod, $orderInfo);
        if ($payment === null) {
            $this->jsonError('Платеж не прошел. Попробуйте еще или выберите другой способ оплаты');
        }
        $result = array(
            'success' => true,
            'redirect' => $this->url->link('payment/yamoney/confirm', 'order_id=' . $orderInfo['order_id'], 'SSL'),
        );
        /** @var \YaMoney\Model\Confirmation\ConfirmationRedirect $confirmation */
        $confirmation = $payment->getConfirmation();
        if ($confirmation === null) {
            $this->getModel()->log('warning', 'Confirmation in created payment equals null');
        } elseif ($confirmation->getType() === \YaMoney\Model\ConfirmationType::REDIRECT) {
            $result['redirect'] = $confirmation->getConfirmationUrl();
        }
        if ($paymentMethod->getCreateOrderBeforeRedirect()) {
            $this->getModel()->confirmOrder($paymentMethod, $orderInfo['order_id']);
        }
        if ($paymentMethod->getClearCartBeforeRedirect()) {
            $this->cart->clear();
        }

        echo json_encode($result);
        exit();
    }

    /**
     * Экшен подтверждения платежа, вызывается при возврате пользователя из кассы
     *
     * Очищает корзину, устанавливает ножный статус заказа, если нужно, осуществляет подтверждение платежа на стороне
     * кассы. Если платёж в статусе кансэллед, то редиректит на страницу ошибки.
     */
    public function confirm()
    {
        $paymentMethod = $this->getModel()->getPaymentMethod($this->config->get('ya_mode'));
        if ($paymentMethod instanceof YandexMoneyPaymentKassa) {
            if (!isset($_GET['order_id'])) {
                $this->errorRedirect('Order id not specified in return link');
            }

            $this->language->load('payment/yamoney');
            $orderId = (int)$_GET['order_id'];
            if ($orderId <= 0) {
                $this->errorRedirect('Invalid order id in return link: ' . json_encode($_GET['order_id']));
            }

            $this->getModel()->log('info', 'Подтверждение платежа (возврат из кассы) для заказа №' . $orderId);
            $payment = $this->getModel()->getOrderPayment($paymentMethod, $orderId);
            if ($payment === null) {
                $this->redirect($this->url->link('checkout/checkout', '', true));
            } elseif ($payment->getStatus() === \YaMoney\Model\PaymentStatus::CANCELED) {
                $pageId = $this->config->get('ya_kassa_page_failure');
                if (empty($pageId) || $pageId < 0) {
                    $redirectUrl = $this->url->link('checkout/checkout', '', true);
                } else {
                    $redirectUrl = $this->url->link('information/information', 'information_id=' . $pageId, 'SSL');
                }
                $this->redirect($redirectUrl);
            } elseif (!$payment->getPaid()) {
                $this->redirect($this->url->link('checkout/checkout', '', true));
            }

            $this->load->model('checkout/order');
            $orderInfo = $this->model_checkout_order->getOrder($orderId);
            if ($orderInfo['order_status_id'] <= 0) {
                $this->getModel()->confirmOrder($paymentMethod, $orderId);
            }
            if ($payment->getStatus() === \YaMoney\Model\PaymentStatus::WAITING_FOR_CAPTURE) {
                if ($this->getModel()->capturePayment($paymentMethod, $payment, false)) {
                    $this->getModel()->confirmOrderPayment($orderId, $payment, $paymentMethod->getOrderStatusId());
                    $this->getModel()->log('info', 'Платёж для заказа №' . $orderId . ' подтверждён');
                }
            }

            $pageId = $this->config->get('ya_kassa_page_success');

            if (isset($this->session->data['order_id']) && $orderId === $this->session->data['order_id']) {
                $this->cart->clear();
            }
            if (empty($pageId) || $pageId < 0) {
                $redirectUrl = $this->url->link('checkout/success', 'order_id=' . $orderId, 'SSL');
            } else {
                $redirectUrl = $this->url->link('information/information', 'information_id=' . $pageId, 'SSL');
            }
            $this->redirect($redirectUrl);

        } elseif ($paymentMethod instanceof YandexMoneyPaymentMoney) {
            $this->getModel()->log('debug', 'Wallet payment');
            if (isset($this->session->data['order_id'])) {
                if ($paymentMethod->getCreateOrderBeforeRedirect()) {
                    $orderId = $this->session->data['order_id'];
                    $this->load->model('checkout/order');
                    $orderInfo = $this->model_checkout_order->getOrder($orderId);
                    if ($orderInfo['order_status_id'] <= 0) {
                        $this->getModel()->log('debug', 'Wallet create payment');
                        $this->getModel()->confirmOrder($paymentMethod, $orderId);
                    }
                }
                if ($paymentMethod->getClearCartBeforeRedirect()) {
                    $this->getModel()->log('debug', 'Wallet clear cart');
                    $this->cart->clear();
                }
            }
        }
    }

    /**
     * Экшен подтверждения платежа, дергается API после холдирования
     */
    public function capture()
    {
        $data = file_get_contents('php://input');
        if (empty($data)) {
            $log = 'Empty body in capture notification, get: ' . json_encode($_GET) . ', post: ' . json_encode($_POST);
            $this->getModel()->log('error', $log);
            header('HTTP/1.1 400 Empty body in notification request');
            exit();
        }
        $json = @json_decode($data, true);
        if (empty($json)) {
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->getModel()->log('error', 'Empty object in body in capture notification');
            } else {
                $this->getModel()->log('error', 'Invalid body in capture notification ' . json_last_error_msg() . ' ' . $data);
            }
            header('HTTP/1.1 400 Failed to parse body');
            exit();
        }

        /** @var YandexMoneyPaymentKassa $paymentMethod */
        $paymentMethod = $this->getModel()->getPaymentMethod($this->config->get('ya_mode'));
        if (!$paymentMethod->isModeKassa()) {
            $this->getModel()->log('warning', 'Invalid body in capture notification ' . json_last_error_msg() . ' ' . $data);
            header('HTTP/1.1 405 Invalid order payment method');
            exit();
        }

        $notification = new \YaMoney\Model\Notification\NotificationWaitingForCapture($json);
        $orderId = $this->getModel()->getOrderIdByPayment($notification->getObject());
        if ($orderId <= 0) {
            $this->getModel()->log('warning', 'Order not exists in capture notification' . $orderId);
            header('HTTP/1.1 404 Order not exists');
            exit();
        }
        $this->load->model('checkout/order');
        $orderInfo = $this->model_checkout_order->getOrder($orderId);
        if (empty($orderInfo)) {
            $this->getModel()->log('warning', 'Empty order#' . $orderId . ' in notification');
            header('HTTP/1.1 405 Invalid order payment method');
            exit();
        } elseif ($orderInfo['order_status_id'] <= 0) {
            $this->getModel()->confirmOrder($paymentMethod, $orderId);
        }

        $this->getModel()->log('info', 'Пришла нотификация для платежа ' . $notification->getObject()->getId() . ' для заказа №' . $orderId);
        if ($orderId > 0) {
            if ($this->getModel()->capturePayment($paymentMethod, $notification->getObject())) {
                $this->getModel()->confirmOrderPayment($orderId, $notification->getObject(), $paymentMethod->getOrderStatusId());
                $this->getModel()->log('info', 'Платёж для заказа №' . $orderId . ' подтверждён');
            } else {
                $this->getModel()->log('error', 'Failed to capture payment: ' . $notification->getObject()->getId());
                header('HTTP/1.1 500 Internal server error');
                exit();
            }
        } else {
            $this->getModel()->log('error', 'Order for payment ' . $notification->getObject()->getId() . ' not exists');
            header('HTTP/1.1 404 Order not exists');
            exit();
        }
    }

    /**
     * Экшен каллбэка при оплате на кошелёк
     */
    public function callback()
    {
        if ($_SERVER['REQUEST_METHOD'] == "GET") {
            echo "You aren't Yandex.Money. We use module for Opencart 1.5.x";
            return;
        }
        $callbackParams = $_POST;
        $notify = false;
        if (isset($callbackParams["label"])) {
            $orderId = $callbackParams["label"];
        } else {
            $this->errorRedirect('Invalid callback parameters, label not specified');
        }
        /** @var YandexMoneyPaymentMoney $paymentMethod */
        $paymentMethod = $this->getModel()->getPaymentMethod($this->config->get('ya_mode'));
        if (!$paymentMethod->isModeMoney()) {
            $this->errorRedirect('Invalid payment mode in callback');
        }
        if ($paymentMethod->checkSign($callbackParams)) {
            $this->load->model('checkout/order');
            $orderInfo = $this->model_checkout_order->getOrder($orderId);
            if (empty($orderInfo)) {
                $this->errorRedirect('Order#' . $orderId . ' not exists in database (callback)');
            } else {
                $amount = number_format($callbackParams['withdraw_amount'], 2, '.', '');
                if ($callbackParams['paymentType'] == "MP" || $amount == number_format($orderInfo['total'], 2, '.', '')) {
                    $sender = ($callbackParams['sender'] != '') ? "Номер кошелька Яндекс.Денег: " . $callbackParams['sender'] . "." : '';
                    $this->model_checkout_order->confirm(
                        $orderId,
                        $this->config->get('ya_newStatus'),
                        $sender . " Сумма: " . $callbackParams['withdraw_amount'],
                        $notify
                    );
                }
            }
        }
    }

    /**
     * Метод отображения способов оплаты пользователю
     * @param $orderInfo
     * @param bool $child
     */
    private function payment($orderInfo, $child = false)
    {
        $this->language->load('payment/yamoney');
        $paymentMethod = $this->getModel()->getPaymentMethod($this->config->get('ya_mode'));

        if (isset($orderInfo['email'])) {
            $this->data['email'] = $orderInfo['email'];
        }
        if (isset($orderInfo['telephone'])) {
            $this->data['phone'] = $orderInfo['telephone'];
        }

        $this->data['cmsname'] = ($child) ? 'opencart-extracall' : 'opencart';
        $this->data['sum'] = $this->currency->format(
            $orderInfo['total'], $orderInfo['currency_code'], $orderInfo['currency_value'], false
        );
        $this->data['button_confirm'] = $this->language->get('button_confirm');
        $this->data['order_id'] = $orderInfo['order_id'];
        $this->data['paymentMethod'] = $paymentMethod;
        $this->data['lang'] = $this->language;
        if ($paymentMethod->isModeKassa()) {
            $this->assignKassa($paymentMethod);
        } elseif ($paymentMethod->isModeMoney()) {
            $this->assignMoney($paymentMethod, $orderInfo);
        } else {
            $this->data['tpl'] = 'billing';
            $fio = array();
            if (!empty($orderInfo['lastname'])) {
                $fio[] = $orderInfo['lastname'];
            }
            if (!empty($orderInfo['firstname'])) {
                $fio[] = $orderInfo['firstname'];
            }
            $narrative = $this->parsePlaceholders($this->config->get('ya_billing_purpose'), $orderInfo);
            $this->data['formId'] = $this->config->get('ya_billing_form_id');
            $this->data['narrative'] = $narrative;
            $this->data['fio'] = implode(' ', $fio);
            $this->data['validate_url'] = $this->url->link('payment/yamoney/create', '', 'SSL');
        }

        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/yamoney.tpl')) {
            $this->template = $this->config->get('config_template') . '/template/payment/yamoney.tpl';
        } else {
            $this->template = 'default/template/payment/yamoney.tpl';
        }
        if ($child) {
            $this->children = array(
                'common/column_left',
                'common/column_right',
                'common/footer',
                'common/header'
            );
        }

        $this->response->addHeader('Content-Type: text/html; charset=utf-8');
        $this->response->setOutput($this->render());
    }

    private function jsonError($message)
    {
        $this->getModel()->log('warning', 'Error in json: ' . $message);
        echo json_encode(array(
            'success' => false,
            'error' => $message,
        ));
        exit();
    }

    private function assignKassa(YandexMoneyPaymentKassa $paymentMethod)
    {
        $this->data['tpl'] = 'kassa';

        $this->data['allow_methods'] = array();
        $this->data['default_method'] = $this->config->get('ya_kassa_payment_default');
        foreach ($paymentMethod->getPaymentMethods() as $method => $name) {
            if ($paymentMethod->isPaymentMethodEnabled($method)) {
                if ($paymentMethod->isTestMode()) {
                    if ($method === \YaMoney\Model\PaymentMethodType::BANK_CARD || $method === \YaMoney\Model\PaymentMethodType::YANDEX_MONEY) {
                        $this->data['allow_methods'][$method] = $this->language->get('text_method_' . $method);
                    }
                } else {
                    $this->data['allow_methods'][$method] = $this->language->get('text_method_' . $method);
                }
            }
        }
        $this->data['validate_url'] = $this->url->link('payment/yamoney/create', '', 'SSL');

        if (isset($this->request->server['HTTPS']) && (($this->request->server['HTTPS'] == 'on') || ($this->request->server['HTTPS'] == '1'))) {
            $this->data['imageurl'] = $this->config->get('config_ssl') . 'image/';
        } else {
            $this->data['imageurl'] = $this->config->get('config_url') . 'image/';
        }

        $title = $this->config->get('ya_kassa_payment_method_name');
        if (empty($title)) {
            $title = $this->language->get('kassa_page_title_default');
        }
        $this->data['method_label'] = $title;
    }

    private function assignMoney(YandexMoneyPaymentMoney $paymentMethod, $order_info)
    {
        $this->data['tpl'] = 'wallet';

        $this->data['account'] = $this->config->get('ya_wallet');
        $this->data['shop_id'] = $paymentMethod->getShopId();
        $this->data['scid'] = $paymentMethod->getScId();
        $this->data['comment'] = $order_info['comment'];

        $this->data['customerNumber'] = trim($order_info['order_id'] . ' ' . $order_info['email']);
        $this->data['shopSuccessURL'] = (!$this->config->get('ya_pageSuccess')) ? $this->url->link(
            'checkout/success', '', 'SSL'
        ) : $this->url->link('information/information', 'information_id=' . $this->config->get('ya_pageSuccess'));
        $this->data['shopFailURL'] = (!$this->config->get('ya_pageFail')) ? $this->url->link(
            'checkout/failure', '', 'SSL'
        ) : $this->url->link('information/information', 'information_id=' . $this->config->get('ya_pageFail'));

        $this->data['formcomment'] = $this->config->get('config_name');
        $this->data['short_dest'] = $this->config->get('config_name');

        $this->data['allow_methods'] = array();
        $this->data['default_method'] = $this->config->get('ya_paymentDfl');

        $this->data['mpos_page_url'] = $this->url->link('payment/yamoney/success', '', 'SSL');
        $this->data['method_label'] = $this->language->get('text_method');
        $this->data['order_text'] = $this->language->get('text_order');

        if (isset($this->request->server['HTTPS']) && (($this->request->server['HTTPS'] == 'on') || ($this->request->server['HTTPS'] == '1'))) {
            $this->data['imageurl'] = $this->config->get('config_ssl') . 'image/';
        } else {
            $this->data['imageurl'] = $this->config->get('config_url') . 'image/';
        }
    }

    public function repay()
    {
        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->url->link('payment/yamoney/repay', 'order_id=' . $this->request->get['order_id'], 'SSL');
            $this->redirect($this->url->link('account/login', '', 'SSL'));
        }
        $this->load->model('account/order');
        $order_info = $this->model_account_order->getOrder((int)$this->request->get['order_id']);
        if ($order_info) {
            $this->payment($order_info, true);
        } else {
            $this->redirect($this->url->link('account/order/info', 'order_id=' . $this->request->get['order_id'], 'SSL'));
        }
    }

    public function success()
    {
        if (isset($_GET['order_id'])) {
            $this->session->data['tmp_order_id'] = (int)$_GET['order_id'];
            $orderInfo = $this->getOrderInfo('tmp_order_id');
            $this->data['order'] = $orderInfo;
        }
        $this->renderPage('success', true);
    }

    public function failure()
    {
        if (isset($_GET['order_id'])) {
            $this->session->data['tmp_order_id'] = (int)$_GET['order_id'];
            $orderInfo = $this->getOrderInfo('tmp_order_id');
            $this->data['order'] = $orderInfo;
        }
        $this->renderPage('failure', true);
    }

    public function renderPage($template, $child = false)
    {
        $templatePath = '/template/payment/yamoney/'.$template.'.tpl';
        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . $templatePath)) {
            $this->template = $this->config->get('config_template') . $templatePath;
        } else {
            $this->template = 'default' . $templatePath;
        }
        if ($child) {
            $this->children = array(
                'common/column_left',
                'common/column_right',
                'common/footer',
                'common/header'
            );
        }
        $this->response->addHeader('Content-Type: text/html; charset=utf-8');
        $this->response->setOutput($this->render());
    }

    private function updateOrderStatus($orderId, $status, $text)
    {
        $this->model_checkout_order->confirm($orderId, $status, $text);

        $this->cart->clear();
        if (isset($this->session->data['order_id'])) {
            unset($this->session->data['shipping_method']);
            unset($this->session->data['shipping_methods']);
            unset($this->session->data['payment_method']);
            unset($this->session->data['payment_methods']);
            unset($this->session->data['guest']);
            unset($this->session->data['comment']);
            unset($this->session->data['order_id']);
            unset($this->session->data['coupon']);
            unset($this->session->data['reward']);
            unset($this->session->data['voucher']);
            unset($this->session->data['vouchers']);
            unset($this->session->data['totals']);
        }
    }

    private function parsePlaceholders($template, $order)
    {
        $replace = array();
        foreach ($order as $key => $value) {
            if (is_scalar($value)) {
                $replace['%' . $key . '%'] = $value;
            }
        }
        return strtr($template, $replace);
    }

    /**
     * Возвращает модель работы с платежами, если модель ещё не инстацирована, создаёт её
     * @return ModelPaymentYaMoney Модель работы с платежами
     */
    private function getModel()
    {
        if ($this->_model === null) {
            $this->load->model('payment/yamoney');
            $this->_model = $this->model_payment_yamoney;
        }
        return $this->_model;
    }

    /**
     * Возвращает информаицю о текущем заказе в корзине, если заказа нет, редиректит на страницу корзины
     * @param string $sessionKey Ключ в сессии, по которому лежит айди заказа
     * @param bool $redirectOnError Требуется ли перенаправить пользователя, если произошла ошибка
     * @return array|null Массив с информацией о платеже или null если произошла ошибка и флаг редиректа равен false
     */
    private function getOrderInfo($sessionKey = 'order_id', $redirectOnError = true)
    {
        if ($this->_orderInfo === null) {
            if (!isset($this->session->data[$sessionKey])) {
                if ($redirectOnError) {
                    $this->errorRedirect('Order id (' . $sessionKey . ') not exists in session');
                } else {
                    return null;
                }
            }
            $this->load->model('checkout/order');
            $this->_orderInfo = $this->model_checkout_order->getOrder($this->session->data[$sessionKey]);
            if (empty($this->_orderInfo)) {
                if ($redirectOnError) {
                    $this->errorRedirect('Order#' . $this->session->data[$sessionKey] . ' not exists in database');
                } else {
                    return null;
                }
            }
        }
        return $this->_orderInfo;
    }

    /**
     * Осуществляет редирект на страницу
     * @param string $message Почему пользователя редиректит
     * @param string $redirectLink Ссылка на страницу редиректа
     */
    private function errorRedirect($message, $redirectLink = 'checkout/cart')
    {
        $this->getModel()->log('warning', 'Redirect user: ' . $message);
        $this->redirect($this->url->link($redirectLink));
    }
}
