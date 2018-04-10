<?php

/**
 * @var YandexMoneyPaymentKassa $paymentMethod
 */

?>
<?php if (!$paymentMethod->getEPL()): ?>
    <h3><?php echo $method_label; ?></h3>
    <table class="radio">
        <tbody>
        <?php foreach ($allow_methods as $val => $methodName) :
            if (empty($default_method)) :
                $default_method = $val;
            endif;
            $checked = ($default_method == $val) ? 'checked' : '';
            $additionalFields = '';
            if ($val == \YandexCheckout\Model\PaymentMethodType::QIWI) :
                $additionalFields = '<label for="qiwiPhone">' . $lang->get('kassa_qiwi_phone_label') . '</label> <input name="qiwiPhone" id="qiwiPhone" value="" />';
            endif;
            if ($val == \YandexCheckout\Model\PaymentMethodType::ALFABANK) :
                $additionalFields = '<label for="alphaLogin">' . $lang->get('kassa_alfa_login_label') . '</label> <input name="alphaLogin" id="alphaLogin" value="" />';
            endif;
        ?>
        <tr class="highlight">
            <td>
                <label for="ym_<?php echo $val; ?>">
                    <input type="radio" name="paymentType" value="<?php echo $val.'" '.$checked; ?> id="ym_<?php echo $val; ?>">
                    <img src="<?php echo $imageurl.'yamoney/'.strtolower($val).'.png'; ?>"/>
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
