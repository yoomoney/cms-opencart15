<?php
/**
 * @var YandexMoneyPaymentKassa $paymentMethod
 */

if (isset($header)) {
    echo $header;
}

function renderPayWithYandexButton ($text, $id = '',  $withShadow = false) {
?>
<div class="yamoney-pay-button<?=$withShadow?' yamoney-pay-button_type_fly':'';?>" id="<?=$id;?>">
    <button type="submit"><?=$text;?></button>
</div>
<?php
}

?>
<style type="text/css">
    .yamoney-pay-button {
        position: relative;
        height: 60px;
        width: 155px;
        font-family: YandexSansTextApp-Regular, Arial, Helvetica, sans-serif;
        text-align: center;
    }

    .yamoney-pay-button button{
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border-radius: 4px;
        transition: 0.1s ease-out 0s;
        color: #000;
        box-sizing: border-box;
        outline: 0;
        border: 0;
        background: #FFDB4D;
        cursor: pointer;
        font-size: 12px;
    }

    .yamoney-pay-button button:hover, .yamoney-pay-button button:active {
        background: #f2c200;
    }

    .yamoney-pay-button button span {
        display: block;
        font-size: 20px;
        line-height: 20px;
    }

    .yamoney-pay-button_type_fly {
        box-shadow: 0 1px 0 0 rgba(0,0,0,0.12), 0 5px 10px -3px rgba(0, 0, 0, 0.3);
    }

    .yamoney-buttons-only-yandex-button {
        display: flex;
        justify-content: flex-end;
    }

    .yamoney-buttons-with-installments-button {
        display: flex;
        justify-content: space-between;
    }

    .yamoney-hidden-element{
        display:none;
    }
</style>

    <form accept-charset="UTF-8" enctype="application/x-www-form-urlencoded" method="POST" id='YamoneyForm'
          action="<?php echo $paymentMethod->getFormUrl(); ?>">
        <input type="hidden" name="cms_name" value="<?php echo $cmsname; ?>" >

        <?php include dirname(__FILE__) . '/yamoney/' . $tpl . '.tpl'; ?>

        <div class="buttons">
            <?php if ($paymentMethod->isModeKassa() && $paymentMethod->useYandexButton()) { ?>
            <div class="right" >
                <button id="button-confirm" type="button" class="button yamoney-hidden-element" name="submit-button"></button>
            </div>
            <?php } else { ?>
            <div class="right">
                <input id="button-confirm" type="button" class="button" value="<?php echo $button_confirm; ?>" />
            </div>
            <?php } ?>
        </div>
    </form>
<?php
    if ($paymentMethod->isModeKassa()) {
        $isUseOnlyYandexButton = $paymentMethod->useYandexButton() && !$paymentMethod->isAddInstallmentsButton();
        $buttonsClass = $isUseOnlyYandexButton ? 'yamoney-buttons-only-yandex-button' : 'yamoney-buttons-with-installments-button';
        ?><div class="<?=$buttonsClass;?>"><?php
        if ($paymentMethod->isAddInstallmentsButton()){
            ?><div class="ya_kassa_installments_button_container"></div><?php
        }
        if ($paymentMethod->useYandexButton()) {
            renderPayWithYandexButton($paymentMethod->i18n('yamoney_pay_button_text'), 'YamoneyFormSubmit', $isUseOnlyYandexButton);
        }
        ?></div><?php
    }
?>

<script type="text/javascript"><!--
jQuery(document).ready(function () {

<?php if ($paymentMethod->isModeMoney()): ?>

    jQuery('#button-confirm').bind('click', function(e) {
        jQuery.ajax({
            type: 'get',
            url: 'index.php?route=payment/yamoney/confirm'
        });
        jQuery("#YamoneyForm").submit();
    });
    jQuery('input[name=paymentType]').bind('click', function() {
        if (jQuery('input[name=paymentType]:checked').val()=='MP'){
            var textMpos='<?php echo $mpos_page_url; ?>';
            jQuery("#YamoneyForm").attr('action', textMpos.replace(/&amp;/g, '&'));

        } else {
            jQuery("#YamoneyForm").attr('action', '<?php echo $paymentMethod->getFormUrl(); ?>');
        }
    });

<?php elseif ($paymentMethod->isModeBilling()): ?>

    document.getElementById('button-confirm').addEventListener('click', function () {
        var field = document.getElementById('ya-fio');
        var parts = field.value.trim().split(/\s+/);
        if (parts.length == 3) {
            document.getElementById("ya-fio-error").innerHTML = "";
            jQuery.ajax({
                url: "<?php echo $validate_url; ?>",
                dataType: "json",
                method: "GET",
                data: {},
                success: function (data) {
                    if (data.success) {
                        document.getElementById("YamoneyForm").submit();
                    } else {
                        onValidateError(data.error);
                    }
                },
                failure: function () {
                    onValidateError('Failed to create payment');
                }
            });
        } else {
            document.getElementById("ya-fio-error").innerHTML = "Укажите фамилию, имя и отчество плательщика";
        }
    }, false);

    function onValidateError(errorMessage) {
        var warning = jQuery('#YamoneyForm .warning');
        if (warning.length > 0) {
            warning.fadeOut(300, function () {
                warning.remove();
                var content = '<div class="warning" style="">' + errorMessage + '<img src="catalog/view/theme/default/image/close.png" alt="" class="close"></div>';
                jQuery('#YamoneyForm').prepend(content);
                jQuery('#YamoneyForm .warning').fadeIn(300);
            });
        } else {
            var content = '<div class="warning" style="">' + errorMessage + '<img src="catalog/view/theme/default/image/close.png" alt="" class="close"></div>';
            jQuery('#YamoneyForm').prepend(content);
            jQuery('#YamoneyForm .warning').fadeIn(300);
        }
    }

    function getCheckedValue(radioCollection) {
        for (var i = 0; i < radioCollection.length; ++i) {
            if (radioCollection[i].checked) {
                return radioCollection[i].value;
            }
        }
        return null;
    }

<?php else: ?>
    const ym_installments_shop_id = <?=$paymentMethod->getShopId();?>;
    const ym_installments_total_amount = <?=$sum;?>;
    <?php if ($paymentMethod->getEPL()) : ?>
        <?php if ($paymentMethod->isAddInstallmentsButton()) { ?>
    const checkoutCreditUI = YandexCheckoutCreditUI({
        shopId: ym_installments_shop_id,
        sum: ym_installments_total_amount,
        language: "<?=$paymentMethod->i18n('language_code');?>"
    });
    const checkoutCreditButton = checkoutCreditUI({type: 'button', domSelector: '.ya_kassa_installments_button_container'});
    checkoutCreditButton.on('click', function () {
        jQuery.ajax({
            url: "<?php echo $validate_url; ?>",
            dataType: "json",
            method: "GET",
            data: {
                paymentType: "installments",
            },
            success: function (data) {
                if (data.success) {
                    document.location = data.redirect;
                } else {
                    onValidateError(data.error);
                }
            },
            failure: function () {
                onValidateError("Failed to create payment");
            }
        });
    });
    <?php } ?>

    <?php else : ?>
        jQuery.get("https://money.yandex.ru/credit/order/ajax/credit-pre-schedule?shopId="
            + ym_installments_shop_id + "&sum=" + ym_installments_total_amount, function (data) {
            const ym_installments_amount_text = "<?=$paymentMethod->i18n('text_method_installments_amount')?>";
            if (ym_installments_amount_text && data && data.amount) {
                jQuery('label[for=ym_installments]').append(ym_installments_amount_text.replace('%s', data.amount));
            }
        });
    <?php endif; ?>

    var paymentType = jQuery('input[name=paymentType]');
    paymentType.change(function () {
        var id = '#payment-' + jQuery(this).val();
        jQuery('.additional').css('display', 'none');
        jQuery(id).css('display', 'table-row');
    });

    jQuery('#button-confirm, #YamoneyFormSubmit').bind('click', function () {
        var form = jQuery("#YamoneyForm")[0];
        var checked;
        if (form.paymentType.value !== undefined) {
            checked = form.paymentType.value;
        } else {
            checked = getCheckedValue(form.paymentType);
        }
        jQuery.ajax({
            url: "<?php echo $validate_url; ?>",
            dataType: "json",
            method: "GET",
            data: {
                paymentType: checked,
                qiwiPhone: jQuery('input[name=qiwiPhone]').val(),
                alphaLogin: jQuery('input[name=alphaLogin]').val()
            },
            success: function (data) {
                if (data.success) {
                    document.location = data.redirect;
                } else {
                    onValidateError(data.error);
                }
            },
            failure: function () {
                onValidateError('Failed to create payment');
            }
        });
    });

    function onValidateError(errorMessage) {
        var warning = jQuery('#YamoneyForm .warning');
        if (warning.length > 0) {
            warning.fadeOut(300, function () {
                warning.remove();
                var content = '<div class="warning" style="">' + errorMessage + '<img src="catalog/view/theme/default/image/close.png" alt="" class="close"></div>';
                jQuery('#YamoneyForm').prepend(content);
                jQuery('#YamoneyForm .warning').fadeIn(300);
            });
        } else {
            var content = '<div class="warning" style="">' + errorMessage + '<img src="catalog/view/theme/default/image/close.png" alt="" class="close"></div>';
            jQuery('#YamoneyForm').prepend(content);
            jQuery('#YamoneyForm .warning').fadeIn(300);
        }
    }

    function getCheckedValue(radioCollection) {
        for (var i = 0; i < radioCollection.length; ++i) {
            if (radioCollection[i].checked) {
                return radioCollection[i].value;
            }
        }
        return null;
    }

<?php endif; ?>

});
//--></script>

<?php if (isset($footer)):
    echo $footer;
endif; ?>