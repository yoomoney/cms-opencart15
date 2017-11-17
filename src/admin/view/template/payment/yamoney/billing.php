<?php

/**
 * @var YandexMoneyPaymentBilling $billing
 * @var Language $lang
 */

/** @var string $purpose */
$purpose = $billing->getPurpose();
if (empty($purpose)) {
    $purpose = $lang->get('billing_purpose_default');
}
?>
<div role="tabpanel" class="tab-pane" id="yabilling">
    <div class='row'>
        <div class='col-md-12'>
            <h4><?php echo $lang->get('billing_header'); ?></h4>
            <div class="form-horizontal">
                <div class="form-group">
                    <div class="col-sm-9 col-sm-offset-3" style="padding-left: 35px;">
                        <label class="checkbox" for="ya_billing_enable">
                            <input type="checkbox" name="ya_billing_enable" id="ya_billing_enable"
                                class="cls_ya_billingmode ya_mode" value="1"
                                <?php echo ($billing->isEnabled() ? 'checked' : ''); ?> />
                            <?php echo $lang->get('billing_enable'); ?>
                        </label>
                    </div>
                </div>
            </div>
            <div class="form-horizontal">
                <div class="form-group">
                    <label class="control-label col-sm-3" for="ya_billing_form_id"><?php echo $lang->get('billing_form_id') ?></label>
                    <div class='col-sm-9'>
                        <input name='ya_billing_form_id' type="text" class="form-control" id="ya_billing_form_id"
                            value="<?php echo htmlspecialchars($billing->getFormId()); ?>" />
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-sm-3" for="ya_billing_purpose"><?php echo $lang->get('billing_purpose'); ?></label>
                    <div class='col-sm-9'>
                        <input name="ya_billing_purpose" type="text" class="form-control" id="ya_billing_purpose"
                            value="<?php echo htmlspecialchars($purpose); ?>" />
                    </div>
                </div>

                <div class="form-group">
                    <div class='col-sm-9 col-sm-offset-3'>
                        <?php echo $lang->get('billing_purpose_desc'); ?>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-sm-3" for="ya_billing_status"><?php echo $lang->get('billing_status') ?></label>
                    <div class="col-sm-9">
                        <select name="ya_billing_status" id="ya_billing_status" class="form-control" data-toggle="tooltip" data-placement="left" title="">
                            <?php foreach ($orderStatusList as $id => $status) : ?>
                                <option value="<?php echo $id; ?>"<?php echo ($id == $billing->getStatus() ? ' selected="selected"' : ''); ?>><?php echo htmlspecialchars($status); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <div class='col-sm-8 col-sm-offset-3'>
                        <?php echo $lang->get('billing_status_desc'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <h4 class="form-heading"><?php echo $lang->get('billing_feature_header'); ?></h4>
        <div class="col-md-12">
            <div class="form-horizontal">
                <div class="form-group">
                    <label for="ya_kassa_debug_mode" class="col-sm-3 control-label"><?php echo $lang->get('billing_debug_label'); ?></label>
                    <div class="col-sm-9">
                        <label class="radio-inline" for="ya_billing_debug_mode_off">
                            <input type="radio" name="ya_billing_debug_mode" id="ya_billing_debug_mode_off"
                                   class="cls_ya_debugmode" value="0" <?php if ($ya_billing_debug_mode != '1') { echo "checked"; }?> />
                            <?php echo $lang->get('disable'); ?>
                        </label>
                        <label class="radio-inline" for="ya_billing_debug_mode_on">
                            <input type="radio" name="ya_billing_debug_mode" id="ya_billing_debug_mode_on"
                                   class="cls_ya_debugmode" value="1" <?php if ($ya_billing_debug_mode == '1') { echo "checked"; }?> />
                            <?php echo $lang->get('enable'); ?>
                        </label>
                        <p class="help-block"><?php echo $lang->get('billing_debug_description'); ?></p>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-3" for="ya_billing_sort_order"><?php echo $lang->get('billing_ordering_label'); ?></label>
                    <div class="col-sm-8">
                        <input name="ya_billing_sort_order" id="ya_billing_sort_order" type="text" class="form-control"
                               value="<?php echo (int) $ya_billing_sort_order; ?>" />
                        <p class="help-block"></p>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-3" for="ya_billing_id_zone"><?php echo $lang->get('billing_geo_zone_label'); ?></label>
                    <div class="col-sm-8">
                        <select name="ya_billing_id_zone" id="ya_billing_id_zone" class="form-control">
                            <option value="0"><?php echo $lang->get('billing_all_zones'); ?></option>
                            <?php foreach ($geoZoneList as $id => $geoZone) : ?>
                                <option value="<?php echo $id; ?>"<?php echo ($id == $ya_billing_id_zone ? ' selected="selected"' : ''); ?>><?php echo htmlspecialchars($geoZone); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- end row -->

</div>
<!-- для tab-yabilling -->
