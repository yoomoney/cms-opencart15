<?php

/**
 * @var YooMoneyPaymentMoney $paymentMethod
 */

?>
<?php if (!$paymentMethod->getEPL()): ?>
    <h3><?php echo $method_label; ?></h3>
    <table class="radio">
        <tbody>
        <?php foreach ($paymentMethod->getEnabledMethods() as $val => $methodName):
            if (empty($default_method)) $default_method = $val;
            $checked = ($default_method == $val) ? 'checked' : '';
            ?>
            <tr class="highlight">
                <td>
                    <label for="yoomoney_<?php echo $val; ?>">
                        <input type="radio" name="paymentType" value="<?php echo $val.'" '.$checked; ?> id="yoomoney_<?php echo $val; ?>">
                        <img src="<?php echo $imageurl.'yoomoney/'.strtolower ($val).'.png'; ?>"/>
                        <?php echo $methodName; ?>
                    </label>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <input type="hidden" name="paymentType" value="">
<?php endif; ?>

<input type="hidden" name="receiver" value="<?php echo htmlspecialchars($account); ?>" />
<input type="hidden" name="formcomment" value="<?php echo htmlspecialchars($formcomment);?>" />
<input type="hidden" name="short-dest" value="<?php echo htmlspecialchars($short_dest);?>" />
<input type="hidden" name="writable-targets" value="false" />
<input type="hidden" name="comment-needed" value="true" />
<input type="hidden" name="label" value="<?php echo htmlspecialchars($order_id);?>" />
<input type="hidden" name="successURL" value="<?php echo htmlspecialchars($shopSuccessURL); ?>" />
<input type="hidden" name="quickpay-form" value="shop" />
<input type="hidden" name="targets" value="<?php echo htmlspecialchars($order_text) ;?> <?php echo htmlspecialchars($order_id);?>" />
<input type="hidden" name="sum" value="<?php echo htmlspecialchars($sum); ?>" data-type="number" />
<input type="hidden" name="comment" value="<?php echo htmlspecialchars($comment); ?>" />
<input type="hidden" name="need-fio" value="false" />
<input type="hidden" name="need-email" value="false" />
<input type="hidden" name="need-phone" value="false" />
<input type="hidden" name="need-address" value="false" />
