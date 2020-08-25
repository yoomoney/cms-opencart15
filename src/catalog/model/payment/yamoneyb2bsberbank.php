<?php

use YandexCheckout\Model\CurrencyCode;
use YandexCheckout\Model\PaymentData\B2b\Sberbank\VatData;
use YandexCheckout\Model\PaymentData\B2b\Sberbank\VatDataType;
use YandexCheckout\Model\PaymentData\PaymentDataB2bSberbank;

require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'yamoney.php';
require_once dirname(__FILE__).'/yamoney/autoload.php';


class ModelPaymentYaMoneyB2bSberbank extends ModelPaymentYaMoney
{
    const MAX_LENGTH_DESCRIPTION = 210;

    public function getMethod($address, $total)
    {
        $method_data = array();

        if ($this->config->get('ya_kassa_b2b_sberbank_enabled') == "on") {
            $this->language->load('payment/yamoney');
            $method_data = array(
                'code'       => 'yamoneyb2bsberbank',
                'title'      => $this->language->get('yandex_money_b2b_sberbank'),
                'sort_order' => (int)$this->config->get('yamoney_sort_order'),
            );
        }

        return $method_data;
    }

    /**
     * @param YandexMoneyPaymentKassa $paymentMethod
     * @param $orderInfo
     *
     * @return \YandexCheckout\Model\PaymentInterface
     */
    public function createPayment(YandexMoneyPaymentKassa $paymentMethod, $orderInfo)
    {
        $this->log('error', 'Payment create init');
        $client = $this->getClient($paymentMethod);

        $coupon = empty($orderInfo['coupon']) ? null : $orderInfo['coupon'];
        try {
            $builder = \YandexCheckout\Request\Payments\CreatePaymentRequest::builder();
            $amount  = $this->currency->format($orderInfo['total'], 'RUB', '', false);

            $builder->setAmount($amount)
                    ->setCurrency('RUB')
                    ->setCapture(true)
                    ->setClientIp($_SERVER['REMOTE_ADDR'])
                    ->setSavePaymentMethod(false)
                    ->setMetadata(array(
                        'order_id'       => $orderInfo['order_id'],
                        'cms_name'       => 'ya_api_opencart',
                        'module_version' => YandexMoneyPaymentMethod::MODULE_VERSION,
                    ));


            $confirmation = array(
                'type'      => \YandexCheckout\Model\ConfirmationType::REDIRECT,
                'returnUrl' => str_replace(
                    array('&amp;'),
                    array('&'),
                    $this->url->link('payment/yamoney/confirm', 'order_id='.$orderInfo['order_id'], true)
                ),
            );

            $builder->setConfirmation($confirmation);
            $paymentMethodData = new PaymentDataB2bSberbank();
            $paymentPurpose    = $this->createDescription($orderInfo);
            $paymentMethodData->setPaymentPurpose($paymentPurpose);
            $vatTypeAndRate = $this->calculateVatTypeAndRate($orderInfo);

            $vatData = new VatData();
            if ($vatTypeAndRate['vatType'] === VatDataType::CALCULATED) {
                $vatData->setType(VatDataType::CALCULATED);
                $rate = $vatTypeAndRate['vatRate'];
                $vatData->setRate($rate);
                $sum = $this->currency->format(
                    $orderInfo['total'] * $vatTypeAndRate['vatRate'] / 100, $orderInfo['currency_code'],
                    $orderInfo['currency_value'], false
                );

                $vatData->setAmount(array('value' => $sum, 'currency' => CurrencyCode::RUB));
            } else {
                $vatData->setType(VatDataType::UNTAXED);
            }
            $paymentMethodData->setVatData($vatData);
            $builder->setPaymentMethodData($paymentMethodData);
            $request = $builder->build();

        } catch (InvalidArgumentException $e) {
            $this->log('error', 'Failed to build API request object: '.$e->getMessage());

            return null;
        }

        try {
            $paymentInfo = $client->createPayment($request);
            $this->insertPayment($orderInfo['order_id'], $paymentInfo, $amount, $coupon);
        } catch (\Exception $e) {
            $this->log('error', 'API error: '.$e->getMessage());

            return null;
        }

        return $paymentInfo;
    }

    /**
     * @param $order
     *
     * @return bool|string
     */
    private function createDescription($order)
    {
        $descriptionTemplate = $this->config->get('ya_kassa_b2b_sberbank_payment_purpose')
            ? $this->config->get('ya_kassa_b2b_sberbank_payment_purpose')
            : $this->language->get('kassa_description_default_placeholder');

        $replace = array();
        foreach ($order as $key => $value) {
            if (is_scalar($value)) {
                $replace['%'.$key.'%'] = $value;
            }
        }
        $description = strtr($descriptionTemplate, $replace);

        return (string)mb_substr($description, 0, self::MAX_LENGTH_DESCRIPTION);
    }

    /**
     * @param $order
     *
     * @return array
     * @throws YandexMoneySbbolException
     */
    private function calculateVatTypeAndRate($order)
    {
        $this->load->model('account/order');
        $this->load->model('catalog/product');

        $taxRates = $this->config->get('ya_kassa_b2b_tax_rates');

        $usedTaxes = array();

        $products = $this->model_account_order->getOrderProducts($order['order_id']);
        foreach ($products as $product) {
            $product_info = $this->model_catalog_product->getProduct($product["product_id"]);
            $usedTax      = isset($product_info['tax_class_id']) && isset($taxRates[$product_info['tax_class_id']])
                ? $taxRates[$product_info['tax_class_id']]
                : $taxRates['default'];
            $usedTaxes[]  = $usedTax;
        }
        $usedTaxes = array_unique($usedTaxes);

        if (count($usedTaxes) !== 1) {
            throw new YandexMoneySbbolException();
        }

        $vatType = reset($usedTaxes);
        if ($vatType === VatDataType::UNTAXED) {
            return array(
                'vatType' => $vatType,
            );
        }

        return array(
            'vatType' => VatDataType::CALCULATED,
            'vatRate' => $vatType,
        );
    }
}
