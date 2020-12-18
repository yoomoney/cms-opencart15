<div role="tabpanel" class="tab-pane" id="money">
    <div class='row'>
        <div class='col-md-12'>
            <p><?php echo $lang->get('wallet_header_description'); ?></p>
            <div class='form-horizontal'>
                <div class="form-group">
                    <label for="yoomoney_wallet_mode" class="col-sm-3 control-label"></label>
                    <div class="col-sm-9">
                        <label class="checkbox" for="yoomoney_on">
                            <input type="checkbox" name="yoomoney_on" id="yoomoney_on" class="cls_yoomoney_wallet_mode yoomoney_mode" value="1"
                                <?php echo ($yoomoney_on == '1' ? ' checked' : ''); ?> />
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
                    <label for="yoomoney_wallet" class="col-sm-3 control-label"><?php echo $lang->get('wallet_number_label'); ?></label>
                    <div class="col-sm-9">
                        <input name='yoomoney_wallet' type="text" class="form-control" id="yoomoney_wallet" value="<?php echo $yoomoney_wallet; ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="yoomoney_appPassword" class="col-sm-3 control-label"><?php echo $lang->get('wallet_password_label'); ?></label>
                    <div class="col-sm-9">
                        <input name='yoomoney_password' type="text" class="form-control" id="yoomoney_appPassword" value="<?php echo $yoomoney_password; ?>">
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
                    <label for="yoomoney_paymentOpt_wallet" class="col-sm-3 control-label"><?php echo $lang->get('wallet_option_label'); ?></label>
                    <div class="col-sm-9">
                        <?php foreach ($wallet_name_methods as $val => $name) : ?>
                            <div class="checkbox">
                                <label><input name='yoomoney_payment_options[]' type="checkbox" value="<?php echo $val;?>" <?php if (is_array($yoomoney_payment_options) && in_array($val, $yoomoney_payment_options)) { echo "checked"; }?>> <?php echo $name;?> </label>
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
                    <label for="yoomoney_kassa_debug_mode" class="col-sm-3 control-label"><?php echo $lang->get('wallet_debug_label'); ?></label>
                    <div class="col-sm-9">
                        <label class="radio-inline" for="yoomoney_debug_mode_off">
                            <input type="radio" name="yoomoney_debug_mode" id="yoomoney_debug_mode_off"
                                   class="cls_yoomoney_debugmode" value="0" <?php if ($yoomoney_debug_mode != '1') { echo "checked"; }?> />
                            <?php echo $lang->get('disable'); ?>
                        </label>
                        <label class="radio-inline" for="yoomoney_debug_mode_on">
                            <input type="radio" name="yoomoney_debug_mode" id="yoomoney_debug_mode_on"
                                   class="cls_yoomoney_debugmode" value="1" <?php if ($yoomoney_debug_mode == '1') { echo "checked"; }?> />
                            <?php echo $lang->get('enable'); ?>
                        </label>
                        <p class="help-block"><?php echo $lang->get('wallet_debug_description'); ?></p>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-sm-3 control-label"><strong><?php echo $lang->get('wallet_before_redirect_label'); ?></strong></div>
                    <div class="col-sm-8">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="yoomoney_create_order_before_redirect" id="yoomoney_create_order_before_redirect"
                                       value="1" <?php echo ($yoomoney_create_order_before_redirect == '1' ? 'checked' : ''); ?> />
                                <?php echo $lang->get('wallet_create_order_label'); ?>
                            </label>
                        </div>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="yoomoney_clear_cart_before_redirect" id="yoomoney_clear_cart_before_redirect"
                                       value="1" <?php echo ($yoomoney_clear_cart_before_redirect == '1' ? 'checked' : ''); ?> />
                                <?php echo $lang->get('wallet_clear_cart_label'); ?>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="form-group" id="yoomoney-new-status">
                    <label class="control-label col-sm-3" for="yoomoney_new_order_status"><?php echo $lang->get('wallet_order_status_label'); ?></label>
                    <div class="col-sm-8">
                        <select name="yoomoney_new_order_status" id="yoomoney_new_order_status" class="form-control" data-toggle="tooltip" data-placement="left" title="">
                            <?php foreach ($orderStatusList as $id => $status) : ?>
                                <option value="<?php echo $id; ?>"<?php echo ($id == $yoomoney_new_order_status ? ' selected="selected"' : ''); ?>><?php echo htmlspecialchars($status); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-3" for="yoomoney_wallet_sort_order"><?php echo $lang->get('wallet_ordering_label'); ?></label>
                    <div class="col-sm-8">
                        <input name="yoomoney_wallet_sort_order" id="yoomoney_wallet_sort_order" type="text" class="form-control"
                               value="<?php echo (int) $yoomoney_wallet_sort_order; ?>" />
                        <p class="help-block"></p>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-3" for="yoomoney_id_zone"><?php echo $lang->get('wallet_geo_zone_label'); ?></label>
                    <div class="col-sm-8">
                        <select name="yoomoney_id_zone" id="yoomoney_id_zone" class="form-control">
                            <option value="0"><?php echo $lang->get('wallet_all_zones'); ?></option>
                            <?php foreach ($geoZoneList as $id => $geoZone) : ?>
                                <option value="<?php echo $id; ?>"<?php echo ($id == $yoomoney_id_zone ? ' selected="selected"' : ''); ?>><?php echo htmlspecialchars($geoZone); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div> <!-- end row -->

</div>
<!-- для tab-money -->