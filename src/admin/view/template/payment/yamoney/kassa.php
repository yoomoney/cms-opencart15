<?php

/**
 * @var YandexMoneyPaymentKassa $kassa
 * @var Language $lang
 */

?>
<div role="tabpanel" class="tab-pane active" id="kassa">
    <div class="row">
        <div class="col-md-12">
            <p><?php echo $lang->get('kassa_header_description'); ?></p>
            <div class="form-group row">
                <div class="col-sm-9 col-sm-offset-3" style="padding-left: 35px;">
                    <label class="checkbox control-label" for="ya_kassa_enable">
                        <input type="checkbox" name="ya_kassa_enable" class="cls_ya_kassamode ya_mode" id="ya_kassa_enable"
                               value="1" <?php echo ($kassa->isEnabled() ? 'checked' : ''); ?> />
                        <?php echo $lang->get('kassa_enable'); ?>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <!-- row -->
    <div class="row">
        <h4 class="form-heading"><?php echo $lang->get('kassa_account_header'); ?></h4>
        <div class="col-md-12">
            <div class="form-horizontal">
                <div class="form-group">
                    <label for="ya_kassa_shop_id" class="col-sm-3 control-label"><?php echo $lang->get('kassa_shop_id_label'); ?></label>
                    <div class="col-sm-9">
                        <input name="ya_kassa_shop_id" type="text" class="form-control" id="ya_kassa_shop_id"
                               value="<?php echo htmlspecialchars($kassa->getShopId()); ?>" />
                        <p class="help-block"><?php echo $lang->get('kassa_shop_id_help'); ?></p>
                    </div>
                </div>
                <div class="form-group">
                    <label for="ya_kassa_password" class="col-sm-3 control-label"><?php echo $lang->get('kassa_password_label'); ?></label>
                    <div class="col-sm-9">
                        <input name="ya_kassa_password" type="text" class="form-control" id="ya_kassa_password"
                               value="<?php echo htmlspecialchars($kassa->getPassword()); ?>" />
                        <p class="help-block"><?php echo $lang->get('kassa_password_help'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- row -->
    <div class="row">
        <h4 class="form-heading"><?php echo $lang->get('kassa_payment_config_header'); ?></h4>
        <div class="col-md-12">
            <div class="form-horizontal">
                <div class="form-group">
                    <label class="col-sm-3 control-label"><?php echo $lang->get('kassa_payment_mode_label'); ?></label>
                    <?php if (!$kassa->isTestMode()) : ?>
                    <div class="col-sm-9">
                        <label for="ya_kassa_payment_mode_kassa" class="control-label radio-inline">
                            <input type="radio" name="ya_kassa_payment_mode" id="ya_kassa_payment_mode_kassa"
                                class="cls_ya_paymode" value="kassa"
                                <?php echo ($kassa->getEPL() ? 'checked' : ''); ?> />
                            <?php echo $lang->get('kassa_payment_mode_smart_pay'); ?>
                        </label>
                    </div>
                    <div class="col-sm-9 col-sm-offset-3 selectPayKassa">
                        <label for="ya_kassa_force_button_name" class="radio-inline control-label" style="margin-left: -20px;">
                            <input type="checkbox" name="ya_kassa_force_button_name" id="ya_kassa_force_button_name"
                                value="1"<?php echo ($ya_kassa_force_button_name == '1' ? ' checked="checked"' : ''); ?> />
                            <?php echo $lang->get('kassa_force_button_name'); ?>
                        </label>
                    </div>
                    <div class="col-sm-9 col-sm-offset-3">
                    <?php else: ?>
                    <div class="col-sm-9">
                    <?php endif; ?>
                        <label for="ya_kassa_payment_mode_shop" class="radio-inline control-label">
                            <input type="radio" name="ya_kassa_payment_mode" id="ya_kassa_payment_mode_shop"
                                class="cls_ya_paymode" value="shop"
                                <?php echo ($kassa->getEPL() ? '' : 'checked'); ?> />
                            <?php echo $lang->get('kassa_payment_mode_shop_pay'); ?>
                        </label>
                    </div>
                    <div class="col-sm-9 col-sm-offset-3 selectPayOpt">
                        <p style="margin: 15px 0 0;"><?php echo $lang->get('kassa_payment_method_label'); ?></p>
                        <?php foreach ($kassa->getPaymentMethods() as $val => $name) : ?>
                            <?php if ($kassa->isTestMode() && !in_array($val, array(\YaMoney\Model\PaymentMethodType::YANDEX_MONEY, \YaMoney\Model\PaymentMethodType::BANK_CARD))) continue; ?>
                            <div class="checkbox">
                                <label>
                                    <input name="ya_kassa_payment_options[]" class="cls_ya_paymentOpt" type="checkbox"
                                           value="<?php echo $val; ?>"
                                        <?php echo ($kassa->isPaymentMethodEnabled($val) ? 'checked' : '') ?> />
                                    <?php echo htmlspecialchars($name); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="form-horizontal">
                <div class="form-group" id="ya-success-page">
                    <label class="control-label col-sm-3" for="ya_kassa_page_success"><?php echo $lang->get('kassa_success_page_label'); ?></label>
                    <div class="col-sm-8">
                        <select name="ya_kassa_page_success" id="ya_kassa_page_success" class="form-control">
                            <option value="0" <?php if ($kassa->getSuccessPageId() <= 0) echo 'selected="selected"'; ?>>---<?php echo $lang->get('kassa_page_default'); ?></option>
                            <?php foreach ($pages_mpos as $id => $title) :
                            $mp_checked = ($id == $kassa->getSuccessPageId()) ? 'selected="selected"':''; ?>
                            <option value="<?php echo $id; ?>" <?php echo $mp_checked;?>><?php echo $title; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="help-block"><?php echo $lang->get('kassa_success_page_description'); ?></p>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-3" for="ya_kassa_page_failure"><?php echo $lang->get('kassa_failure_page_label'); ?></label>
                    <div class="col-sm-8">
                        <select name="ya_kassa_page_failure" id="ya_kassa_page_failure" class="form-control">
                            <option value="0" <?php if ($kassa->getFailurePageId() <= 0) echo 'selected="selected"'; ?>>---<?php echo $lang->get('kassa_page_default'); ?></option>
                            <?php foreach ($pages_mpos as $id => $title) :
                            $mp_checked = ($id == $kassa->getFailurePageId())?'selected="selected"':''; ?>
                            <option value="<?php echo $id; ?>" <?php echo $mp_checked;?>><?php echo $title; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="help-block"><?php echo $lang->get('kassa_failure_page_description'); ?></p>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-3" for="ya_kassa_payment_method_name"><?php echo $lang->get('kassa_page_title_label'); ?></label>
                    <div class="col-sm-8">
                        <input name="ya_kassa_payment_method_name" type="text" class="form-control"
                               id="ya_kassa_payment_method_name" value="<?php echo empty($ya_kassa_payment_method_name) ? $lang->get('kassa_page_title_default') : $ya_kassa_payment_method_name; ?>" />
                        <p class="help-block"><?php echo $lang->get('kassa_page_title_help'); ?></p>
                    </div>
                </div>

                <!-- 54-ФЗ -->
                <div class="form-group">
                    <label class="col-sm-3 control-label"><?php echo $lang->get('kassa_send_receipt_label'); ?></label>
                    <div class="col-sm-9">
                        <label class="radio-inline" for="ya_kassa_send_receipt_off">
                            <input type="radio" name="ya_kassa_send_receipt" id="ya_kassa_send_receipt_off"
                                   class="cls_ya_54lawmode" value="0"
                                <?php if ($ya_kassa_send_receipt != '1') { echo "checked"; }?>> <?php echo $lang->get('disable'); ?>
                        </label>
                        <label class="radio-inline" for="ya_kassa_send_receipt_on">
                            <input type="radio" name="ya_kassa_send_receipt" id="ya_kassa_send_receipt_on"
                                   class="cls_ya_54lawmode" value="1"
                                <?php if ($ya_kassa_send_receipt == '1') { echo "checked"; }?>> <?php echo $lang->get('enable'); ?>
                        </label>
                    </div>
                </div>
                <div class="form-group select54Law">
                    <label class="col-sm-3 control-label"><?php echo $lang->get('kassa_all_tax_rate_label'); ?></label>
                    <div class="col-sm-9">
                        <p style="padding-top: 8px;"><b><?php echo $lang->get('kassa_default_tax_rate_label'); ?></b></p>
                        <select name="ya_kassa_receipt_tax_id[default]" class="form-control" data-toggle="tooltip" data-placement="left" title="">
                            <?php foreach ($kassa_taxes as $tax_id => $tax_name) : ?>
                                <?php if (isset($ya_kassa_receipt_tax_id["default"]) && $tax_id == $ya_kassa_receipt_tax_id["default"]) : ?>
                                <option value="<?php echo $tax_id; ?>" selected="selected"><?php echo $tax_name; ?></option>
                                <?php else : ?>
                                <option value="<?php echo $tax_id; ?>"><?php echo $tax_name; ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                        <p><?php echo $lang->get('kassa_default_tax_rate_description'); ?></p>
                    </div>
                    <div class="col-sm-9 col-sm-offset-3">
                        <p style="padding-top: 15px;"><b><?php echo $lang->get('kassa_tax_rate_description'); ?></b></p>
                        <table class="table table-hover">
                            <thead>
                            <tr>
                                <th><?php echo $lang->get('kassa_tax_rate_site_header'); ?></th>
                                <th><?php echo $lang->get('kassa_tax_rate_kassa_header'); ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($tax_classes as $id => $title) : ?>
                            <tr>
                                <td><?php echo $title; ?></td>
                                <td>
                                    <select name="ya_kassa_receipt_tax_id[<?php echo $id; ?>]" class="form-control" data-toggle="tooltip" data-placement="left" title="">
                                    <?php foreach ($kassa_taxes as $tax_id => $tax_name) : ?>
                                        <?php if (isset($ya_kassa_receipt_tax_id[$id]) && $tax_id == $ya_kassa_receipt_tax_id[$id]) : ?>
                                        <option value="<?php echo $tax_id; ?>" selected="selected"><?php echo $tax_name; ?></option>
                                        <?php else : ?>
                                        <option value="<?php echo $tax_id; ?>"><?php echo $tax_name; ?></option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- -->
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="form-horizontal">
                <div class="form-group">
                    <label class="control-label col-sm-3"><?php echo $lang->get('kassa_notification_url_label'); ?></label>
                    <div class="col-sm-8">
                        <input class="form-control disabled" value="<?php echo htmlspecialchars($callback_url); ?>" disabled>
                        <p class="help-block"><?php echo $lang->get('kassa_notification_url_description'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <h4 class="form-heading"><?php echo $lang->get('kassa_feature_header'); ?></h4>
        <div class="col-md-12">
            <div class="form-horizontal">
                <div class="form-group">
                    <label for="ya_kassa_debug_mode" class="col-sm-3 control-label"><?php echo $lang->get('kassa_debug_label'); ?></label>
                    <div class="col-sm-9">
                        <label class="radio-inline" for="ya_kassa_debug_mode_off">
                            <input type="radio" name="ya_kassa_debug_mode" id="ya_kassa_debug_mode_off"
                                   class="cls_ya_debugmode" value="0" <?php if ($ya_kassa_debug_mode != '1') { echo "checked"; }?> />
                            <?php echo $lang->get('disable'); ?>
                        </label>
                        <label class="radio-inline" for="ya_kassa_debug_mode_on">
                            <input type="radio" name="ya_kassa_debug_mode" id="ya_kassa_debug_mode_on"
                                   class="cls_ya_debugmode" value="1" <?php if ($ya_kassa_debug_mode == '1') { echo "checked"; }?> />
                            <?php echo $lang->get('enable'); ?>
                        </label>
                        <p class="help-block"><?php echo $lang->get('kassa_debug_description'); ?></p>
                        <p class="help-block"><a href="<?php echo $kassa_logs_link; ?>"><?php echo $lang->get('kassa_view_logs'); ?></a></p>
                    </div>
                </div>
                <div class="form-group" id="ya-new-status">
                    <label class="control-label col-sm-3" for="ya_kassa_new_order_status"><?php echo $lang->get('kassa_order_status_label'); ?></label>
                    <div class="col-sm-8">
                        <select name="ya_kassa_new_order_status" id="ya_kassa_new_order_status" class="form-control" data-toggle="tooltip" data-placement="left" title="">
                            <?php foreach ($orderStatusList as $id => $status) : ?>
                            <option value="<?php echo $id; ?>"<?php echo ($id == $ya_kassa_new_order_status ? ' selected="selected"' : ''); ?>><?php echo htmlspecialchars($status); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-3" for="ya_kassa_sort_order"><?php echo $lang->get('kassa_ordering_label'); ?></label>
                    <div class="col-sm-8">
                        <input name="ya_kassa_sort_order" id="ya_kassa_sort_order" type="text" class="form-control"
                               value="<?php echo (int) $ya_kassa_sort_order; ?>" />
                        <p class="help-block"></p>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-3" for="ya_kassa_id_zone"><?php echo $lang->get('kassa_geo_zone_label'); ?></label>
                    <div class="col-sm-8">
                        <select name="ya_kassa_id_zone" id="ya_kassa_id_zone" class="form-control">
                            <option value="0"><?php echo $lang->get('kassa_all_zones'); ?></option>
                            <?php foreach ($geoZoneList as $id => $geoZone) : ?>
                            <option value="<?php echo $id; ?>"<?php echo ($id == $ya_kassa_id_zone ? ' selected="selected"' : ''); ?>><?php echo htmlspecialchars($geoZone); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div> <!-- end row -->
</div>
<!-- для tab-kassa -->

<script>

jQuery(document).ready(function() {

    function getRadioValue(elements) {
        for (var i = 0; i < elements.length; ++i) {
            if (elements[i].checked) {
                return elements[i].value;
            }
        }
        return elements[0].value;
    }

    function triggerPaymentMode(value) {
        if (value == 'kassa') {
            jQuery('.selectPayOpt').slideUp();
            jQuery('.selectPayKassa').slideDown();
        } else {
            jQuery('.selectPayOpt').slideDown();
            jQuery('.selectPayKassa').slideUp();
        }
    }

    function triggerReceipt(value) {
        if (value == '1') {
            jQuery('.select54Law').slideDown();
        } else {
            jQuery('.select54Law').slideUp();
        }
    }

    var paymentMode = jQuery('input[name=ya_kassa_payment_mode]');
    paymentMode.change(function () {
        triggerPaymentMode(this.value);
    });
    triggerPaymentMode(getRadioValue(paymentMode));

    var sendReceipt = jQuery('input[name=ya_kassa_send_receipt]');
    sendReceipt.change(function () {
        triggerReceipt(this.value);
    });
    triggerReceipt(getRadioValue(sendReceipt));
});

</script>
