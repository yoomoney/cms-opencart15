<?php

/**
 * @var YandexMoneyPaymentKassa $paymentMethod
 */

const YANDEX_MONEY_MIN_INSTALLMENTS_AMOUNT = 3000;

?>
<?php if (!$paymentMethod->getEPL()): ?>
    <h3><?php echo $method_label; ?></h3>
    <table class="radio">
        <tbody>
        <?php foreach ($allow_methods as $val => $methodName) :
            $isEnabled = true;
            if (($val === \YandexCheckout\Model\PaymentMethodType::INSTALLMENTS)
                && $sum < YANDEX_MONEY_MIN_INSTALLMENTS_AMOUNT) {
                continue;
            }
            if (empty($default_method)) {
                $default_method = $val;
            }
            $checked = ($default_method == $val) ? 'checked' : '';
            $additionalFields = '';
            if ($val == \YandexCheckout\Model\PaymentMethodType::QIWI) {
                $additionalFields = '<label for="qiwiPhone">' . $lang->get('kassa_qiwi_phone_label') . '</label> <input name="qiwiPhone" id="qiwiPhone" value="" />';
            }
            if ($val == \YandexCheckout\Model\PaymentMethodType::ALFABANK) {
                $additionalFields = '<label for="alphaLogin">' . $lang->get('kassa_alfa_login_label') . '</label> <input name="alphaLogin" id="alphaLogin" value="" />';
            }
        ?>
        <tr class="highlight">
            <td>
                <label for="ym_<?php echo $val; ?>">
                    <input type="radio" name="paymentType" value="<?php echo $val.'" '.$checked; ?> id="ym_<?php echo $val; ?>" style="vertical-align: middle;">
                    <img src="<?php echo $imageurl.'yamoney/'.strtolower ($val).'.png'; ?>" style="vertical-align:middle; padding: 1px 3px;" />
                    <?php echo $methodName; ?>
                </label>
            </td>
        </tr>
        <?php if (!empty($additionalFields)) : ?>
        <tr class="highlight additional" style="display: none;" id="payment-<?php echo $val ?>">
            <td>
                <?php echo $additionalFields; ?>
            </td>
        </tr>
        <?php endif; ?>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <input type="hidden" name="paymentType" value="">
<?php endif; ?>
