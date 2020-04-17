<?php
// Heading
$_['heading_title'] = 'Yandex.Money 2.0';

// Text
$_['text_yamoney'] = '<a onclick="window.open(\'https://money.yandex.ru\');"><img src="view/image/payment/yamoney.png" alt="Yandex.Money" title="Yandex.Money" /></a>';

$_['text_success']     = 'Settings saved';
$_['text_need_update'] = "Your module version is outdated. You can <a target='_blank' href='https://github.com/yandex-money/yandex-money-cms-opencart/releases'>download and install</a> a new one (%s)";

$_['yandexmoney_license'] = '<p>By using this program in any way, you fully and unconditionally accept the terms of the license agreement as posted at <a href="https://money.yandex.ru/doc.xml?id=527132"> https://money.yandex.ru/doc.xml?id=527132 </a>(hereinafter referred to "license agreement"). If you do not accept any part of the terms of the license agreement, you are forbidden to use the program for any purpose.</p>';

$_['error_permission']                  = 'You don\'t have the permission to use this module!';
$_['error_empty_payment']               = 'Please select at least one option from the list';
$_['error_ya_kassa_shop_id']            = 'Specify shopId';
$_['error_ya_kassa_password']           = 'Specify secret key';
$_['error_invalid_shop_password']       = 'Invalid format for the secret key';
$_['error_invalid_shop_id_or_password'] = 'Such secret key does not exist. If you are sure you copied the key correctly, this means it does not work for some reason. Issue and activate the key again—under your Merchant Profile';

$_['module_settings_header']                = "Settings";
$_['module_license']                        = "By using the module, you accept its <a href='https://money.yandex.ru/doc.xml?id=527132' target='_blank'>terms of use</a>.";
$_['module_version']                        = "Module version ";
$_['kassa_tab_label']                       = "Yandex.Checkout";
$_['kassa_header_description']              = "To start operating the module, connect your store to <a target=\"_blank\" href=\"https://kassa.yandex.ru/\">Yandex.Checkout</a>.";
$_['kassa_test_mode_info']                  = 'You have enabled the test mode. Check the payment making process. <a href="https://yandex.ru/support/checkout/payments/api.html#api__04" target="_blank">Learn more</a>';
$_['kassa_enable']                          = "Enable payment acceptance via Yandex.Checkout";
$_['check_url_help']                        = "Copy this link to the Check URL and Aviso URL fields in the settings section of your Yandex.Checkout\'s Merchant Profile";
$_['kassa_account_header']                  = "Parameters from Yandex.Checkout\'s Merchant Profile";
$_['kassa_shop_id_label']                   = 'shopId';
$_['kassa_shop_id_help']                    = 'Copy your shopId from your Yandex.Checkout\'s Merchant Profile';
$_['kassa_password_label']                  = 'Secret key';
$_['kassa_password_help']                   = 'Issue and activate the secret key under your Yandex.Checkout\'s Merchant Profile. Then copy it here.';
$_['kassa_account_help']                    = "Shop ID, scid, ShopPassword can be found in the <a href='https://kassa.yandex.ru/my' target='_blank'>Merchant Profile</a> after the onboarding process.";
$_['kassa_payment_config_header']           = 'Check the preferable scenario of selecting the payment method';
$_['kassa_payment_mode_label']              = 'Select payment method';
$_['kassa_payment_mode_smart_pay']          = 'On Yandex.Checkout\'s site';
$_['kassa_payment_mode_shop_pay']           = 'On the store\'s site';
$_['kassa_force_button_name']               = 'Use a button with \'Pay with Yandex\' text';
$_['kassa_add_installments_button']         = 'Add the Installments payment method to checkout page';
$_['kassa_add_installments_block_label']    = 'Add the information block about Installments to product descriptions';
$_['kassa_payment_mode_help']               = "<a href='https://tech.yandex.ru/money/doc/payment-solution/payment-form/payment-form-docpage/' target='_blank'>More about payment scenarios</a>";
$_['kassa_payment_method_label']            = "Check payment methods from the contract";
$_['forwork_money']                         = "";
$_['enable_money']                          = "Enable payment acceptance to the Yandex.Money Wallet";
$_['redirectUrl_help']                      = "Copy this link to the Redirect URL field at the <a href='https://money.yandex.ru/myservices/online.xml' target='_blank'>notifications settings page</a>.";
$_['account_head']                          = "Payment acceptance settings";
$_['wallet']                                = "Wallet number";
$_['password']                              = "Codeword";
$_['account_help']                          = "The codeword should be copied from the <a href='https://money.yandex.ru/myservices/online.xml' target='_blank'>notifications settings page</a> at the Yandex.Money website";
$_['option_wallet']                         = "Select payment method";
$_['kassa_payment_method_default']          = "Payment method by default";
$_['kassa_success_page_label']              = "Successful payment page";
$_['kassa_page_default']                    = "Standard---";
$_['kassa_success_page_description']        = "The customer will see this page once they pay for the order";
$_['kassa_failure_page_label']              = "Failure page";
$_['page_standart']                         = "Standard---";
$_['kassa_failure_page_description']        = "The customer will see this page if something goes wrong: for example, if there's not enough funds on their card";
$_['successMP_label']                       = "Success page for the Payment by Card on Delivery method";
$_['successMP_help']                        = "This page contains delivery information. Specify the terms of the delivery and the payment here";
$_['kassa_page_title_label']                = "Payment service title";
$_['kassa_page_title_help']                 = "This title will be shown to the customer";
$_['kassa_description_title']               = 'Transaction data';
$_['kassa_description_default_placeholder'] = 'Payment for order No. %order_id%';
$_['kassa_description_help']                = 'Full description of the transaction that the user will see during the checkout process. You can find it in your Yandex.Checkout Merchant Profile. For example, "Payment for order No. 72". Limitations: no more than 128 symbols.';
$_['kassa_send_receipt_label']              = 'Transmit details for receipts to Yandex.Checkout (Federal Law No.54-FZ)';
$_['kassa_all_tax_rate_label']              = 'VAT';
$_['kassa_tax_rate_table_label']            = ' transmit to Yandex.Checkout as ';
$_['kassa_default_tax_rate_label']          = 'Default rate';
$_['kassa_default_tax_rate_description']    = 'The default rate will be shown in the receipt unless another rate was specified in the product\'s card.';
$_['kassa_tax_rate_label']                  = 'Rate at your store';
$_['kassa_tax_rate_description']            = 'Compare rates';
$_['kassa_tax_rate_site_header']            = 'Rate at your store';
$_['kassa_tax_rate_kassa_header']           = 'Rate for the receipt to the tax service';
$_['kassa_feature_header']                  = "Additional settings for administrator";
$_['kassa_debug_label']                     = "Record of debugging information";
$_['kassa_view_logs']                       = 'View logs';
$_['disable']                               = "Disable";
$_['enable']                                = "Enable";
$_['kassa_second_receipt_header']           = "Второй чек";
$_['kassa_second_receipt_description']      = "Два чека нужно формировать, если покупатель вносит предоплату и потом получает товар или услугу. Первый чек — когда деньги поступают вам на счёт, второй — при отгрузке товаров или выполнении услуг.<br> <a target=\"_blank\" href=\"https://kassa.yandex.ru/developers/payments/54fz/payments#settlement-receipt\">Читать про второй чек в Яндекс.Кассе</a>";
$_['kassa_second_receipt_enable_label']     = "Формировать второй чек при переходе заказа в статус";
$_['kassa_second_receipt_help_info']        = "Если в заказе будут позиции с признаками «Полная предоплата» — второй чек отправится автоматически, когда заказ перейдёт в выбранный статус.";
$_['kassa_second_receipt_history_info']     = "Отправлен второй чек. Сумма %s рублей.";
$_['kassa_debug_description']               = "Only required if Yandex.Checkout\'s specialists ask for it";
$_['kassa_before_redirect_label']           = 'When the user proceeds to checkout';
$_['kassa_create_order_label']              = 'Create an unpaid order in the dashboard';
$_['kassa_clear_cart_label']                = 'Delete items from the cart';
$_['kassa_order_status_label']              = "Order status after the payment";
$_['kassa_ordering_label']                  = "Sorting order";
$_['kassa_geo_zone_label']                  = "Area of display";
$_['kassa_all_zones']                       = 'All areas';
$_['kassa_notification_url_label']          = 'Address for notifications';
$_['kassa_notification_url_description']    = 'Only required if Yandex.Checkout\'s specialists ask for it';
$_['kassa_page_title_default']              = 'Yandex.Checkout (bank cards, e-money, etc.)';

$_['kassa_currency']                     = 'The payment currency in the Yandex.Checkout';
$_['kassa_currency_convert']             = 'Convert an amount from the store\'s current currency';
$_['kassa_currency_help']                = 'Currency must match in the Yandex.Checkout and in the store';
$_['kassa_currency_convert_help']        = 'A value from the store\'s currency list is used. If the currency is not in the list – the exchange rate of the Central Bank of the Russian Federation.';

$_['wallet_tab_label']                = 'Yandex.Money';
$_['wallet_header_description']       = '';
$_['wallet_enable']                   = 'Enable payment acceptance to the Yandex.Money Wallet';
$_['wallet_redirect_url_description'] = "Copy this link to the Redirect URL field at the <a href='https://money.yandex.ru/myservices/online.xml' target='_blank'>notifications settings page</a>.";
$_['wallet_account_head']             = 'Payment acceptance settings';
$_['wallet_number_label']             = 'Wallet number';
$_['wallet_password_label']           = 'Codeword';
$_['wallet_account_description']      = "The codeword should be copied from the <a href='https://money.yandex.ru/myservices/online.xml' target='_blank'>notifications settings page</a> at the Yandex.Money website";
$_['wallet_option_label']             = 'Payment methods';
$_['wallet_feature_header']           = 'Additional settings for administrator';
$_['wallet_debug_label']              = 'Record of debugging information';
$_['wallet_debug_description']        = "Only required if Yandex.Checkout\'s specialists ask for it";
$_['wallet_before_redirect_label']    = 'When the user proceeds to checkout';
$_['wallet_create_order_label']       = 'Create an unpaid order in the dashboard';
$_['wallet_clear_cart_label']         = 'Delete items from the cart';
$_['wallet_order_status_label']       = "Order status after the payment";
$_['wallet_ordering_label']           = "Sorting order";
$_['wallet_geo_zone_label']           = "Area of display";
$_['wallet_all_zones']                = 'All areas';

$_['tab_billing']                = 'Yandex.Billing';
$_['billing_header']             = '';
$_['billing_enable']             = 'Activate payments via Yandex.Billing';
$_['billing_form_id']            = 'Form ID';
$_['billing_purpose']            = 'Purpose of payment';
$_['billing_purpose_desc']       = 'The purpose of payment is added to the payment order: specify whatever will help identify the order paid via Yandex.Billing';
$_['billing_purpose_default']    = 'Order No. %order_id% Payment via Yandex.Billing';
$_['billing_status']             = 'Order status';
$_['billing_status_desc']        = 'The status shouls indicate that the result of the payment is unknown: you can only find out if the customer paid or not by checking the notification sent to your email or by contacting your bank';
$_['billing_feature_header']     = 'Additional settings for administrator';
$_['billing_debug_label']        = 'Record of debugging information';
$_['billing_debug_description']  = "Only required if Yandex.Checkout\'s specialists ask for it";
$_['billing_order_status_label'] = "Order status after the payment";
$_['billing_ordering_label']     = "Sorting order";
$_['billing_geo_zone_label']     = "Area of display";
$_['billing_all_zones']          = 'All areas';
$_['error_ya_billing_id']        = 'Billing ID not specified';
$_['error_ya_billing_purpose']   = 'Purpose of payment not specified';
$_['error_ya_billing_status']    = 'Order status not specified';

$_['tab_updater']                             = 'Updates';
$_['updater_header']                          = 'Module updates';
$_['updater_enable']                          = 'Enable module updates';
$_['updater_error_text_restore']              = 'Unable to restore the data from the backup. More about the error in the <a href="%s">module\'s logs</a>';
$_['updater_error_text_remove']               = 'Unable to delete backup %s. More about the error in the <a href="%s">module\'s logs</a>';
$_['updater_restore_success_text']            = 'Backup %s successfully deleted';
$_['updater_check_version_flash_message']     = 'Module version %s successfully downloaded and installed';
$_['updater_error_text_unpack_failed']        = 'Unable to extract archive %s. More about the error in <a href="%s">module\'s logs</a>';
$_['updater_error_text_create_backup_failed'] = 'Unable to create a backup copy of the installed module version. More about the error in <a href="%s">module\'s logs</a>';
$_['updater_error_text_load_failed']          = 'Unable to load the archive, please try again. More about the error in <a href="%s">module\'s logs</a>';
$_['updater_log_text_load_failed']            = 'Unable to load the archive with the update';

$_['order_captured_text']       = 'Payment for order No. %s confirmed';
$_['payments_list_title']       = 'List of payments';
$_['payments_list_breadcrumbs'] = 'List of payment methods available via the Yandex.Checkout module';

$_['text_method_yandex_money'] = 'Yandex.Money';
$_['text_method_bank_card']    = 'Debit or credit cards—Visa, Mastercard, Maestro, MIR';
$_['text_method_cash']         = "Cash";
$_['text_method_mobile']       = 'Direct carrier billing—Beeline, MegaFon, MTS, Tele2';
$_['text_method_webmoney']     = 'Webmoney';
$_['text_method_alfabank']     = 'Alfa-Click';
$_['text_method_sberbank']     = 'Sberbank Online';
$_['text_method_ma']           = 'MasterPass';
$_['text_method_pb']           = 'Promsvyazbank';
$_['text_method_qiwi']         = 'QIWI Wallet';
$_['text_method_qp']           = 'Deferred payment (QPPI.ru)';
$_['text_method_mp']           = 'Mobile payment kiosk';
$_['text_method_installments'] = 'Installments';
$_['text_method_tinkoff_bank'] = 'Tinkoff online banking';
$_['text_method_widget']       = 'Payment widget from Yandex.Checkout (cards, Apple Pay and Google Play)';
$_['bank_cards_title']         = 'Bank cards';
$_['cash_title']               = 'Cash via payment kiosks';
$_['mobile_balance_title']     = 'Direct carrier billing';

$_['text_vat_none'] = 'Without VAT';
$_['text_vat_10']   = 'Applicable rate 10/110';
$_['text_vat_20']   = 'Applicable rate 20/120';

$_['kassa_hold_setting_label']        = 'Включить отложенную оплату';
$_['kassa_hold_setting_description']  = 'Если опция включена, платежи с карт проходят в 2 этапа: у клиента сумма замораживается, и вам вручную нужно подтвердить её списание – через панель администратора.  <a href="https://kassa.yandex.ru/holdirovani.html" target="_blank">Подробное описание Холдирования.</a>';
$_['kassa_hold_order_statuses_label'] = 'Какой статус присваивать заказу, если он:';
$_['kassa_hold_order_status_label']   = 'ожидает подтверждения';
$_['kassa_hold_order_status_help']    = 'заказ переходит в этот статус при поступлении и остается в нем пока оператор магазина не подтвердит или не отменит платеж';
$_['kassa_cancel_order_status_label'] = 'отменен';
$_['kassa_cancel_order_status_help']  = 'заказ переходит в этот статус после отмены платежа';
$_['kassa_hold_capture_form_link']    = 'Подтверждение';

$_['captures_title']           = 'Подтверждение платежа';
$_['captures_new']             = 'Подтверждение платежа';
$_['captures_expires_date']    = 'Подтвердить до';
$_['captures_payment_data']    = 'Данные платежа';
$_['captures_payment_id']      = 'Номер транзакции в Яндекс.Кассе';
$_['captures_order_id']        = 'Номер заказа';
$_['captures_payment_method']  = 'Способ оплаты';
$_['captures_payment_sum']     = 'Сумма платежа';
$_['captures_capture_data']    = '';
$_['captures_capture_sum']     = 'Сумма подтверждения';
$_['captures_capture_create']  = 'Подтвердить платеж';
$_['captures_capture_cancel']  = 'Отменить платеж';
$_['captures_captured']        = 'Платеж подтвержден';
$_['captures_capture_success'] = 'Вы подтвердили платёж в Яндекс.Кассе.';
$_['captures_capture_fail']    = 'Платёж не подтвердился. Попробуйте ещё раз.';
$_['captures_cancel_success']  = 'Вы отменили платёж в Яндекс.Кассе. Деньги вернутся клиенту.';
$_['captures_cancel_fail']     = 'Платёж не отменился. Попробуйте ещё раз.';

$_['b2b_sberbank_label']             = 'Enable payments via Sberbank Business Online';
$_['b2b_sberbank_on_label']          = 'If you enable this option, you will be able to accept online payments from legal entities. Learn more at the <a href="https://checkout.yandex.com">Yandex.Checkout website</a>.';
$_['b2b_sberbank_template_label']    = 'Template for payment details';
$_['b2b_sberbank_vat_default_label'] = 'Default VAT rate';
$_['b2b_sberbank_template_help']     = 'These payment details will be shown in the payment order.';
$_['b2b_sberbank_vat_default_help']  = 'This rate will be sent to Sberbank Business Online if there\'s no other rate indicated in the payment description.';
$_['b2b_sberbank_vat_label']         = 'Compare the VAT rates in your store with the rates for Sberbank Business Online';
$_['b2b_sberbank_vat_cms_label']     = 'VAT rate at your store';
$_['b2b_sberbank_vat_sbbol_label']   = 'VAT rate for Sberbank Business Online';
$_['b2b_tax_rate_untaxed_label']     = 'Without VAT';
$_['b2b_tax_rate_7_label']           = '7%';
$_['b2b_tax_rate_10_label']          = '10%';
$_['b2b_tax_rate_18_label']          = '18%';
$_['b2b_sberbank_tax_message']       = 'There is a restriction for payments via Sberbank Business Online: one receipt can only contain products with the same VAT rate. If the client wants to pay for products with different VAT rates at the same time, we will show him the message explaining that it\'s not possible.';

$_['nps_text'] = '';

$_['kassa_default_payment_mode_label']    = 'Признак способа расчета';
$_['kassa_default_payment_subject_label'] = 'Признак предмета расчета';
$_['ffd_help_message']                    = 'Признаки предмета расчёта и способа расчёта берутся из атрибутов товара payment_mode и payment_subject . Их значения можно задать отдельно в карточке товара, если это потребуется. <a href="https://kassa.yandex.ru/docs/guides/#perehod-na-ffd-1-05">Подробнее.</a>

Для товаров, у которых значения этих атрибутов не заданы, будем применять значения по умолчанию:';