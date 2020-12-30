<?php

use YooKassa\Client;
use YooKassa\Model\Confirmation\AbstractConfirmation;
use YooKassa\Model\ConfirmationType;
use YooKassa\Model\Notification\NotificationSucceeded;
use YooKassa\Model\Notification\NotificationWaitingForCapture;
use YooKassa\Model\NotificationEventType;
use YooKassa\Model\PaymentMethodType;
use YooKassa\Model\PaymentStatus;

/**
 * Class ControllerPaymentYoomoney
 *
 * @property-read Language $language
 * @property-read Currency $currency
 *
 * @property ModelCheckoutOrder $model_checkout_order
 * @property Cart $cart
 */
class ControllerPaymentYoomoney extends Controller
{
    /**
     * @var ModelPaymentYoomoney Модель работы с платежами
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
        if (isset($this->session->data['confirmation_token'])) {
            $this->session->data['confirmation_token'] = null;
        }
        $this->payment($this->getOrderInfo());
    }

    /**
     * Экшен создания платежа на стороне ЮKassa
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
        $this->language->load('payment/yoomoney');
        $this->getModel()->log('info', 'Создание платежа для заказа №'.$orderInfo['order_id']);
        /** @var YooMoneyPaymentKassa $paymentMethod */
        $paymentMethod = $this->getModel()->getPaymentMethod($this->config->get('yoomoney_mode'));
        if (!$paymentMethod->isModeKassa()) {
            $this->jsonError('Ошибка настройки модуля');
        }

        $paymentType = !empty($_GET['paymentType']) ? $_GET['paymentType'] : '';

        $successUrl = str_replace(
            array('&amp;'),
            array('&'),
            $this->url->link('payment/yoomoney/confirm', 'order_id='.$orderInfo['order_id'], true)
        );

        if ($paymentType === YooMoneyPaymentKassa::CUSTOM_PAYMENT_METHOD_WIDGET
            && !empty($this->session->data['confirmation_token'])) {
            echo json_encode(array(
                'success' => true,
                'redirect' => $successUrl,
                'token' => $this->session->data['confirmation_token'],
            ));
            exit();
        }

        if ($paymentMethod->getEPL()) {
            if (!empty($paymentType) && $paymentType !== PaymentMethodType::INSTALLMENTS) {
                $this->jsonError('Invalid payment method');
            }
        } else {
            if (empty($paymentType)) {
                $this->jsonError('Не указан способ оплаты');
            } elseif (!$paymentMethod->isPaymentMethodEnabled($paymentType)) {
                $this->jsonError('Указан неверный способ оплаты');
            } elseif ($paymentType === PaymentMethodType::QIWI) {
                $phone = isset($_GET['qiwiPhone']) ? preg_replace('/[^\d]/', '', $_GET['qiwiPhone']) : '';
                if (empty($phone)) {
                    $this->jsonError('Не был указан номер телефона');
                }
            } elseif ($paymentType === PaymentMethodType::ALFABANK) {
                $login = isset($_GET['alphaLogin']) ? trim($_GET['alphaLogin']) : '';
                if (empty($login)) {
                    $this->jsonError('Не был указан логин в Альфа-клике');
                }
            }
        }
        $payment = $this->getModel()->createPayment($paymentMethod, $orderInfo);
        if ($payment === null) {
            $this->jsonError('Платеж не прошел. Попробуйте еще или выберите другой способ оплаты');
        };

        $result = array(
            'success'  => true,
            'redirect' => $successUrl,
        );

        /** @var AbstractConfirmation $confirmation */
        $confirmation = $payment->getConfirmation();
        if ($confirmation === null) {
            $this->getModel()->log('warning', 'Confirmation in created payment equals null');
        } elseif ($confirmation->getType() === ConfirmationType::REDIRECT) {
            $result['redirect'] = $confirmation->getConfirmationUrl();
        } elseif ($confirmation->getType() === ConfirmationType::EMBEDDED) {
            $result['token'] = $confirmation->getConfirmationToken();
            $this->session->data['confirmation_token'] = $result['token'];
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
        $paymentMethod = $this->getModel()->getPaymentMethod($this->config->get('yoomoney_mode'));
        if ($paymentMethod instanceof YooMoneyPaymentKassa) {
            if (isset($this->session->data['confirmation_token'])) {
                $this->session->data['confirmation_token'] = null;
            }

            if (!isset($_GET['order_id'])) {
                $this->errorRedirect('Order id not specified in return link');
            }

            $this->language->load('payment/yoomoney');
            $orderId = (int)$_GET['order_id'];
            if ($orderId <= 0) {
                $this->errorRedirect('Invalid order id in return link: '.json_encode($_GET['order_id']));
            }

            $this->getModel()->log('info', 'Возврат пользователя из кассы для заказа №'.$orderId);
            $payment = $this->getModel()->getPaymentByOrderId($paymentMethod, $orderId);
            if ($payment === null) {
                $this->redirect($this->url->link('checkout/checkout', '', true));
            } elseif ($payment->getStatus() === \YooKassa\Model\PaymentStatus::CANCELED) {
                $pageId      = $this->config->get('yoomoney_kassa_page_failure');
                $redirectUrl = (empty($pageId) || $pageId < 0)
                    ? $this->url->link('checkout/checkout', '', true)
                    : $this->url->link('information/information', 'information_id='.$pageId, 'SSL');
                $this->redirect($redirectUrl);
            } elseif (!$payment->getPaid()) {
                $this->redirect($this->url->link('checkout/checkout', '', true));
            }

            $pageId = $this->config->get('yoomoney_kassa_page_success');

            if (isset($this->session->data['order_id']) && $orderId === $this->session->data['order_id']) {
                $this->cart->clear();
            }
            $redirectUrl = (empty($pageId) || $pageId < 0)
                ? $this->url->link('checkout/success', 'order_id='.$orderId, 'SSL')
                : $this->url->link('information/information', 'information_id='.$pageId, 'SSL');

            $this->redirect($redirectUrl);

        } elseif ($paymentMethod instanceof YooMoneyPaymentMoney) {
            $this->getModel()->log('info', 'Wallet payment');
            if (isset($this->session->data['order_id'])) {
                if ($paymentMethod->getCreateOrderBeforeRedirect()) {
                    $orderId = $this->session->data['order_id'];
                    $this->load->model('checkout/order');
                    $orderInfo = $this->model_checkout_order->getOrder($orderId);
                    if ($orderInfo['order_status_id'] <= 0) {
                        $this->getModel()->log('info', 'Wallet create payment');
                        $this->getModel()->confirmOrder($paymentMethod, $orderId);
                    }
                }
                if ($paymentMethod->getClearCartBeforeRedirect()) {
                    $this->getModel()->log('info', 'Wallet clear cart');
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
        $this->language->load('payment/yoomoney');
        $data = file_get_contents('php://input');
        if (empty($data)) {
            $log = 'Empty body in capture notification, get: '.json_encode($_GET).', post: '.json_encode($_POST);
            $this->getModel()->log('error', $log);
            header('HTTP/1.1 400 Empty body in notification request');
            exit();
        }
        $json = @json_decode($data, true);
        if (empty($json)) {
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->getModel()->log('error', 'Empty object in body in capture notification');
            } else {
                $this->getModel()->log('error',
                    'Invalid body in capture notification '.json_last_error_msg().' '.$data);
            }
            header('HTTP/1.1 400 Failed to parse body');
            exit();
        }

        $this->getModel()->log('info', 'Notification: '.$data);

        /** @var YooMoneyPaymentKassa $paymentMethod */
        $paymentMethod = $this->getModel()->getPaymentMethod($this->config->get('yoomoney_mode'));
        if (!$paymentMethod->isModeKassa()) {
            $this->getModel()->log('warning', 'Invalid body in capture notification '.json_last_error_msg().' '.$data);
            header('HTTP/1.1 405 Invalid order payment method');
            exit();
        }

        try {
            $notification = ($json['event'] === NotificationEventType::PAYMENT_SUCCEEDED)
                ? new NotificationSucceeded($json)
                : new NotificationWaitingForCapture($json);
        } catch (\Exception $e) {
            $this->getModel()->log('error', 'Invalid notification object - '.$e->getMessage());
            header('HTTP/1.1 400 Invalid object in body');

            return;
        }

        $orderId = $this->getModel()->getOrderIdByPayment($notification->getObject());
        if ($orderId <= 0) {
            $this->getModel()->log('warning', 'Order not exists in capture notification'.$orderId);
            header('HTTP/1.1 404 Order not exists');
            exit();
        }
        $this->load->model('checkout/order');
        $orderInfo = $this->model_checkout_order->getOrder($orderId);
        if (empty($orderInfo)) {
            $this->getModel()->log('warning', 'Empty order#'.$orderId.' in notification');
            header('HTTP/1.1 405 Invalid order payment method');
            exit();
        } elseif ($orderInfo['order_status_id'] <= 0) {
            $this->getModel()->confirmOrder($paymentMethod, $orderId);
        }

        $this->getModel()->log('info',
            'Пришла нотификация для платежа '.$notification->getObject()->getId().' для заказа №'.$orderId);

        $client = $this->getModel()->getClient($paymentMethod);

        try {
            $payment = $client->getPaymentInfo($notification->getObject()->getId());
        } catch (Exception $e) {
            $this->getModel()->log('error',
                'Payment '.$notification->getObject()->getId().' not fetched from API in capture method');

            return false;
        }

        if ($notification->getEvent() === NotificationEventType::PAYMENT_WAITING_FOR_CAPTURE
            && $payment->getStatus() === PaymentStatus::WAITING_FOR_CAPTURE
        ) {
            $capturePaymentMethods = array(
                PaymentMethodType::BANK_CARD,
                PaymentMethodType::YOO_MONEY,
                PaymentMethodType::GOOGLE_PAY,
                PaymentMethodType::APPLE_PAY,
            );
            if (in_array($payment->getPaymentMethod()->getType(), $capturePaymentMethods)) {
                $comment = sprintf($this->language->get('captures_new_hold_payment'),
                    $payment->getExpiresAt()->format('d.m.Y H:i'));
                $this->getModel()->log('info', $comment);
                $this->model_checkout_order->update($orderId, $paymentMethod->getHoldOrderStatusId(), $comment);

                exit();
            } elseif ($this->getModel()->capturePayment($paymentMethod, $payment)) {
                exit();
            }
        }
        if ($notification->getEvent() === NotificationEventType::PAYMENT_SUCCEEDED) {
            if ($payment->getStatus() === PaymentStatus::SUCCEEDED) {
                $this->getModel()->hookOrderStatusChange($orderId, $paymentMethod->getOrderStatusId());
                $this->getModel()->confirmOrderPayment($orderId, $payment, $paymentMethod->getOrderStatusId());
                $this->getModel()->log('info', 'Платёж для заказа №'.$orderId.' подтверждён');
                exit();
            }
        }
        header('HTTP/1.1 500 Internal server error');
        exit();
    }

    /**
     * Экшен каллбэка при оплате на кошелёк
     */
    public function callback()
    {
        if ($_SERVER['REQUEST_METHOD'] == "GET") {
            echo "You aren't YooMoney. We use module for Opencart 1.5.x";

            return;
        }
        $callbackParams = $_POST;
        if (isset($callbackParams["label"])) {
            $orderId = $callbackParams["label"];
        } else {
            $this->errorRedirect('Invalid callback parameters, label not specified');
        }
        /** @var YooMoneyPaymentMoney $paymentMethod */
        $paymentMethod = $this->getModel()->getPaymentMethod($this->config->get('yoomoney_mode'));
        if (!$paymentMethod->isModeMoney()) {
            $this->errorRedirect('Invalid payment mode in callback');
        }
        if ($paymentMethod->checkSign($callbackParams)) {
            $this->load->model('checkout/order');
            $orderInfo = $this->model_checkout_order->getOrder($orderId);
            $this->getModel()->log('info', 'Check signature success');

            if (empty($orderInfo)) {
                $this->errorRedirect('Order#'.$orderId.' not exists in database (callback)');
            } else {
                $this->getModel()->log('info', 'Prepare change order status');

                $amount = number_format($callbackParams['withdraw_amount'], 2, '.', '');
                if ($callbackParams['paymentType'] == "MP" || $amount == number_format($orderInfo['total'], 2, '.',
                        '')
                ) {
                    $this->getModel()->log('info', 'Order status changed');
                    $sender                       = ($callbackParams['sender'] != '') ? "Номер кошелька ЮMoney: ".$callbackParams['sender']."." : '';
                    $this->model_checkout_order->update($orderId, $this->config->get('yoomoney_wallet_new_order_status'),$sender." Сумма: ".$callbackParams['withdraw_amount']);
                }
            }
        } else {
            $this->getModel()->log('error', 'Check signature failed callback params'.$callbackParams);
        }
    }

    /**
     * Метод отображения способов оплаты пользователю
     *
     * @param $orderInfo
     * @param bool $child
     */
    private function payment($orderInfo, $child = false)
    {
        $this->language->load('payment/yoomoney');
        $paymentMethod = $this->getModel()->getPaymentMethod($this->config->get('yoomoney_mode'));

        if (isset($orderInfo['email'])) {
            $this->data['email'] = $orderInfo['email'];
        }
        if (isset($orderInfo['telephone'])) {
            $this->data['phone'] = $orderInfo['telephone'];
        }
        if ($this->currency->has('RUB')) {
            $this->data['sum'] = sprintf('%.2f', $this->currency->format($orderInfo['total'], 'RUB', '', false));
        } else {
            $this->data['sum'] = sprintf('%.2f', $this->getModel()->convertFromCbrf($orderInfo, 'RUB'));
        }
        $this->data['cmsname']        = ($child) ? 'opencart-extracall' : 'opencart';
        $this->data['button_confirm'] = $this->language->get('button_confirm');
        $this->data['order_id']       = $orderInfo['order_id'];
        $this->data['paymentMethod']  = $paymentMethod;
        $this->data['lang']           = $this->language;
        if ($paymentMethod->isModeKassa()) {
            $this->assignKassa($paymentMethod);
        } elseif ($paymentMethod->isModeMoney()) {
            $this->assignMoney($paymentMethod, $orderInfo);
        }

        if (file_exists(DIR_TEMPLATE.$this->config->get('config_template').'/template/payment/yoomoney.tpl')) {
            $this->template = $this->config->get('config_template').'/template/payment/yoomoney.tpl';
        } else {
            $this->template = 'default/template/payment/yoomoney.tpl';
        }
        if ($child) {
            $this->children = array(
                'common/column_left',
                'common/column_right',
                'common/footer',
                'common/header',
            );
        }

        $this->response->addHeader('Content-Type: text/html; charset=utf-8');
        $this->response->setOutput($this->render());
    }

    public function jsonError($message)
    {
        $this->getModel()->log('warning', 'Error in json: '.$message);
        echo json_encode(array(
            'success' => false,
            'error'   => $message,
        ));
        exit();
    }

    public function assignKassa(YooMoneyPaymentKassa $paymentMethod)
    {
        $this->data['tpl'] = 'kassa';

        $this->data['allow_methods']  = array();
        $this->data['default_method'] = $this->config->get('yoomoney_kassa_payment_default');
        foreach ($paymentMethod->getPaymentMethods() as $method => $name) {
            if ($paymentMethod->isPaymentMethodEnabled($method)) {
                if ($paymentMethod->isTestMode()) {
                    if ($method === PaymentMethodType::BANK_CARD || $method === PaymentMethodType::YOO_MONEY) {
                        $this->data['allow_methods'][$method] = $name;
                    }
                } else {
                    $this->data['allow_methods'][$method] = $name;
                }
            }
        }
        $this->data['validate_url'] = $this->url->link('payment/yoomoney/create', '', 'SSL');
        $this->data['reset_token_url'] = $this->url->link('payment/yoomoney/resetToken', '', 'SSL');

        if (isset($this->request->server['HTTPS']) && (($this->request->server['HTTPS'] == 'on') || ($this->request->server['HTTPS'] == '1'))) {
            $this->data['imageurl'] = $this->config->get('config_ssl').'image/';
        } else {
            $this->data['imageurl'] = $this->config->get('config_url').'image/';
        }

        $title = $this->config->get('yoomoney_kassa_payment_method_name');
        if (empty($title)) {
            $title = $this->language->get('kassa_page_title_default');
        }
        $this->data['method_label'] = $title;
    }

    private function assignMoney(YooMoneyPaymentMoney $paymentMethod, $order_info)
    {
        $this->data['tpl'] = 'wallet';

        $this->data['account'] = $this->config->get('yoomoney_wallet_account_id');

        $this->data['shop_id'] = $paymentMethod->getShopId();
        $this->data['scid']    = $paymentMethod->getScId();
        $this->data['comment'] = $order_info['comment'];

        $this->data['customerNumber'] = trim($order_info['order_id'].' '.$order_info['email']);
        $this->data['shopSuccessURL'] = (!$this->config->get('yoomoney_pageSuccess')) ? $this->url->link(
            'checkout/success', '', 'SSL'
        ) : $this->url->link('information/information', 'information_id='.$this->config->get('yoomoney_pageSuccess'));
        $this->data['shopFailURL']    = (!$this->config->get('yoomoney_pageFail')) ? $this->url->link(
            'checkout/failure', '', 'SSL'
        ) : $this->url->link('information/information', 'information_id='.$this->config->get('yoomoney_pageFail'));

        $this->data['formcomment'] = $this->config->get('config_name');
        $this->data['short_dest']  = $this->config->get('config_name');

        $this->data['allow_methods']  = array();
        $this->data['default_method'] = $this->config->get('yoomoney_paymentDfl');

        $this->data['mpos_page_url'] = $this->url->link('payment/yoomoney/success', '', 'SSL');
        $this->data['method_label']  = $this->language->get('text_method');
        $this->data['order_text']    = $this->language->get('text_order');

        if (isset($this->request->server['HTTPS']) && (($this->request->server['HTTPS'] == 'on') || ($this->request->server['HTTPS'] == '1'))) {
            $this->data['imageurl'] = $this->config->get('config_ssl').'image/';
        } else {
            $this->data['imageurl'] = $this->config->get('config_url').'image/';
        }
    }

    public function repay()
    {
        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->url->link('payment/yoomoney/repay',
                'order_id='.$this->request->get['order_id'], 'SSL');
            $this->redirect($this->url->link('account/login', '', 'SSL'));
        }
        $this->load->model('account/order');
        $order_info = $this->model_account_order->getOrder((int)$this->request->get['order_id']);
        if ($order_info) {
            $this->payment($order_info, true);
        } else {
            $this->redirect($this->url->link('account/order/info', 'order_id='.$this->request->get['order_id'], 'SSL'));
        }
    }

    public function success()
    {
        if (isset($_GET['order_id'])) {
            $this->session->data['tmp_order_id'] = (int)$_GET['order_id'];
            $orderInfo                           = $this->getOrderInfo('tmp_order_id');
            $this->data['order']                 = $orderInfo;
        }
        $this->renderPage('success', true);
    }

    public function failure()
    {
        if (isset($_GET['order_id'])) {
            $this->session->data['tmp_order_id'] = (int)$_GET['order_id'];
            $orderInfo                           = $this->getOrderInfo('tmp_order_id');
            $this->data['order']                 = $orderInfo;
        }
        $this->renderPage('failure', true);
    }

    public function resetToken()
    {
        $success = false;
        if (isset($this->session->data['confirmation_token'])) {
            $this->session->data['confirmation_token'] = null;
            $success = true;
        }

        echo json_encode(array(
            'success' => $success,
        ));
    }

    public function renderPage($template, $child = false)
    {
        $templatePath = '/template/payment/yoomoney/'.$template.'.tpl';
        if (file_exists(DIR_TEMPLATE.$this->config->get('config_template').$templatePath)) {
            $this->template = $this->config->get('config_template').$templatePath;
        } else {
            $this->template = 'default'.$templatePath;
        }
        if ($child) {
            $this->children = array(
                'common/column_left',
                'common/column_right',
                'common/footer',
                'common/header',
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
                $replace['%'.$key.'%'] = $value;
            }
        }

        return strtr($template, $replace);
    }

    /**
     * Возвращает модель работы с платежами, если модель ещё не инстацирована, создаёт её
     * @return ModelPaymentYoomoney Модель работы с платежами
     */
    public function getModel()
    {
        if ($this->_model === null) {
            $this->load->model('payment/yoomoney');
            $this->_model = $this->model_payment_yoomoney;
        }

        return $this->_model;
    }

    /**
     * Возвращает информаицю о текущем заказе в корзине, если заказа нет, редиректит на страницу корзины
     *
     * @param string $sessionKey Ключ в сессии, по которому лежит айди заказа
     * @param bool $redirectOnError Требуется ли перенаправить пользователя, если произошла ошибка
     *
     * @return array|null Массив с информацией о платеже или null если произошла ошибка и флаг редиректа равен false
     */
    public function getOrderInfo($sessionKey = 'order_id', $redirectOnError = true)
    {
        if ($this->_orderInfo === null) {
            if (!isset($this->session->data[$sessionKey])) {
                if ($redirectOnError) {
                    $this->errorRedirect('Order id ('.$sessionKey.') not exists in session');
                } else {
                    return null;
                }
            }
            $this->load->model('checkout/order');
            $this->_orderInfo = $this->model_checkout_order->getOrder($this->session->data[$sessionKey]);
            if (empty($this->_orderInfo)) {
                if ($redirectOnError) {
                    $this->errorRedirect('Order#'.$this->session->data[$sessionKey].' not exists in database');
                } else {
                    return null;
                }
            }
        }

        return $this->_orderInfo;
    }

    /**
     * Осуществляет редирект на страницу
     *
     * @param string $message Почему пользователя редиректит
     * @param string $redirectLink Ссылка на страницу редиректа
     */
    public function errorRedirect($message, $redirectLink = 'checkout/cart')
    {
        $this->getModel()->log('warning', 'Redirect user: '.$message);
        $this->redirect($this->url->link($redirectLink));
    }
}
