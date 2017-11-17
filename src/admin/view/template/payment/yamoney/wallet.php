<div role="tabpanel" class="tab-pane" id="money">
    <div class='row'>
        <div class='col-md-12'>
            <p><?php echo $lang->get('wallet_header_description'); ?></p>
            <div class='form-horizontal'>
                <div class="form-group">
                    <label for="ya_moneymode" class="col-sm-3 control-label"></label>
                    <div class="col-sm-9">
                        <label class="checkbox" for="ya_money_on">
                            <input type="checkbox" name="ya_money_on" id="ya_money_on" class="cls_ya_moneymode ya_mode" value="1"
                                <?php echo ($ya_money_on == '1' ? ' checked' : ''); ?> />
                            <?php echo $lang->get('wallet_enable'); ?>
                        </label>
                    </div>
                </div>
            </div>
            <div class='form-horizontal'>
                <div class="form-group">
                    <label class="control-label col-sm-3">RedirectURL</label>
                    <div class='col-sm-8'>
                        <input class='form-control disabled' value='<?php echo $wallet_redirect_url; ?>' disabled />
                    </div>
                </div>
                <div class="form-group">
                    <div class='col-sm-8 col-sm-offset-3'>
                        <?php echo $lang->get('wallet_redirect_url_description'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- row -->
    <div class='row'>
        <h4 class="form-heading"><?php echo $lang->get('wallet_account_head'); ?></h4>
        <div class='col-md-12'>
            <div class='form-horizontal'>
                <div class="form-group">
                    <label for="ya_wallet" class="col-sm-3 control-label"><?php echo $lang->get('wallet_number_label'); ?></label>
                    <div class="col-sm-9">
                        <input name='ya_money_wallet' type="text" class="form-control" id="ya_wallet" value="<?php echo $ya_money_wallet; ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="ya_appPassword" class="col-sm-3 control-label"><?php echo $lang->get('wallet_password_label'); ?></label>
                    <div class="col-sm-9">
                        <input name='ya_money_password' type="text" class="form-control" id="ya_appPassword" value="<?php echo $ya_money_password; ?>">
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-sm-9 col-sm-offset-3">
                        <?php echo $lang->get('wallet_account_description'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- row -->
    <div class='row clsOnlyMoney'>
        <div class='col-md-12'>
            <div class='form-horizontal' role="form">
                <div class="form-group">
                    <label for="ya_paymentOpt_wallet" class="col-sm-3 control-label"><?php echo $lang->get('wallet_option_label'); ?></label>
                    <div class="col-sm-9">
                        <?php foreach ($wallet_name_methods as $val => $name) : ?>
                            <div class="checkbox">
                                <label><input name='ya_money_payment_options[]' type="checkbox" value="<?php echo $val;?>" <?php if (is_array($ya_money_payment_options) && in_array($val, $ya_money_payment_options)) { echo "checked"; }?>> <?php echo $name;?> </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <h4 class="form-heading"><?php echo $lang->get('wallet_feature_header'); ?></h4>
        <div class="col-md-12">
            <div class="form-horizontal">
                <div class="form-group">
                    <label for="ya_kassa_debug_mode" class="col-sm-3 control-label"><?php echo $lang->get('wallet_debug_label'); ?></label>
                    <div class="col-sm-9">
                        <label class="radio-inline" for="ya_money_debug_mode_off">
                            <input type="radio" name="ya_money_debug_mode" id="ya_money_debug_mode_off"
                                   class="cls_ya_debugmode" value="0" <?php if ($ya_money_debug_mode != '1') { echo "checked"; }?> />
                            <?php echo $lang->get('disable'); ?>
                        </label>
                        <label class="radio-inline" for="ya_money_debug_mode_on">
                            <input type="radio" name="ya_money_debug_mode" id="ya_money_debug_mode_on"
                                   class="cls_ya_debugmode" value="1" <?php if ($ya_money_debug_mode == '1') { echo "checked"; }?> />
                            <?php echo $lang->get('enable'); ?>
                        </label>
                        <p class="help-block"><?php echo $lang->get('wallet_debug_description'); ?></p>
                    </div>
                </div>
                <div class="form-group" id="ya-new-status">
                    <label class="control-label col-sm-3" for="ya_money_sort_order"><?php echo $lang->get('wallet_order_status_label'); ?></label>
                    <div class="col-sm-8">
                        <select name="ya_money_new_order_status" id="ya_money_new_order_status" class="form-control" data-toggle="tooltip" data-placement="left" title="">
                            <?php foreach ($orderStatusList as $id => $status) : ?>
                                <option value="<?php echo $id; ?>"<?php echo ($id == $ya_money_new_order_status ? ' selected="selected"' : ''); ?>><?php echo htmlspecialchars($status); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-3" for="ya_money_id_zone"><?php echo $lang->get('wallet_ordering_label'); ?></label>
                    <div class="col-sm-8">
                        <input name="ya_money_sort_order" id="ya_money_sort_order" type="text" class="form-control"
                               value="<?php echo (int) $ya_money_sort_order; ?>" />
                        <p class="help-block"></p>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-3" for="ya_money_id_zone"><?php echo $lang->get('wallet_geo_zone_label'); ?></label>
                    <div class="col-sm-8">
                        <select name="ya_money_id_zone" id="ya_money_id_zone" class="form-control">
                            <option value="0"><?php echo $lang->get('wallet_all_zones'); ?></option>
                            <?php foreach ($geoZoneList as $id => $geoZone) : ?>
                                <option value="<?php echo $id; ?>"<?php echo ($id == $ya_money_id_zone ? ' selected="selected"' : ''); ?>><?php echo htmlspecialchars($geoZone); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div> <!-- end row -->

</div>
<!-- для tab-money -->