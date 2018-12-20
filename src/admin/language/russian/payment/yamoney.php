<?php
// Heading
$_['heading_title'] = 'Яндекс.Деньги 2.0';

// Text
$_['text_yamoney'] = '<a onclick="window.open(\'https://money.yandex.ru\');"><img src="view/image/payment/yamoney.png" alt="Яндекс.Деньги" title="Яндекс.Деньги" /></a>';

$_['text_success']     = 'Настройки модуля обновлены!';
$_['kassa_all_zones']  = 'Все зоны';
$_['text_need_update'] = "У вас неактуальная версия модуля. Вы можете <a target='_blank' href='https://github.com/yandex-money/yandex-money-cms-opencart/releases'>загрузить и установить</a> новую (%s)";

$_['yandexmoney_license'] = '<p>Любое использование Вами программы означает полное и безоговорочное принятие Вами условий лицензионного договора, размещенного по адресу <a href="https://money.yandex.ru/doc.xml?id=527132"> https://money.yandex.ru/doc.xml?id=527132 </a>(далее – «Лицензионный договор»). Если Вы не принимаете условия Лицензионного договора в полном объёме, Вы не имеете права использовать программу в каких-либо целях.</p>';

$_['error_permission']                  = 'У Вас нет прав для управления этим модулем!';
$_['error_empty_payment']               = 'Нужно выбрать хотя бы один метод оплаты!';
$_['error_ya_kassa_shop_id']            = 'Укажите идентификатор магазина (shopId)';
$_['error_ya_kassa_password']           = 'Укажите секретный ключ (shopPassword)';
$_['error_invalid_shop_password']       = 'Секретный ключ указан в не верном формате';
$_['error_invalid_shop_id_or_password'] = 'Проверьте shopId и Секретный ключ — где-то есть ошибка. А лучше скопируйте их прямо из <a href="https://kassa.yandex.ru/my" target="blank">личного кабинета Яндекс.Кассы</a>';
//
$_['module_settings_header']                = "Настройки";
$_['module_license']                        = "Работая с модулем, вы автоматически соглашаетесь с <a href='https://money.yandex.ru/doc.xml?id=527132' target='_blank'>условиями его использования</a>.";
$_['module_version']                        = "Версия модуля ";
$_['kassa_tab_label']                       = "Яндекс.Касса";
$_['kassa_header_description']              = "Для работы с модулем нужно подключить магазин к <a target=\"_blank\" href=\"https://kassa.yandex.ru/\">Яндекс.Кассе</a>.";
$_['kassa_test_mode_info']                  = 'Вы включили тестовый режим приема платежей. Проверьте, как проходит оплата. <a href="https://yandex.ru/support/checkout/payments/api.html#api__04" target="_blank">Подробнее</a>';
$_['kassa_enable']                          = "Включить приём платежей через Яндекс.Кассу";
$_['check_url_help']                        = "Скопируйте эту ссылку в поля Check URL и Aviso URL в настройках личного кабинета Яндекс.Кассы";
$_['kassa_account_header']                  = "Параметры из личного кабинета Яндекс.Кассы";
$_['kassa_shop_id_label']                   = 'shopId';
$_['kassa_shop_id_help']                    = 'Скопируйте shopId из личного кабинета Яндекс.Кассы';
$_['kassa_password_label']                  = 'Секретный ключ';
$_['kassa_password_help']                   = 'Выпустите и активируйте секретный ключ в <a href="https://kassa.yandex.ru/my" target="_blank">личном кабинете Яндекс.Кассы</a>. Потом скопируйте его сюда.';
$_['kassa_account_help']                    = "Shop ID, scid, ShopPassword можно посмотреть в <a href='https://money.yandex.ru/my' target='_blank'>личном кабинете</a> после подключения Яндекс.Кассы.";
$_['kassa_payment_config_header']           = 'Настройка сценария оплаты';
$_['kassa_payment_mode_label']              = 'Выбор способа оплаты';
$_['kassa_payment_mode_smart_pay']          = 'На стороне Кассы';
$_['kassa_payment_mode_shop_pay']           = 'На стороне магазина';
$_['kassa_force_button_name']               = 'Назвать кнопку оплаты «Заплатить через Яндекс»';
$_['kassa_add_installments_button']         = 'Добавить кнопку «Заплатить по частям» на страницу оформления заказа';
$_['kassa_add_installments_block_label']    = 'Добавить блок «Заплатить по частям» в карточки товаров';
$_['kassa_payment_mode_help']               = "<a href='https://tech.yandex.ru/money/doc/payment-solution/payment-form/payment-form-docpage/' target='_blank'>Подробнее о сценариях оплаты</a>";
$_['kassa_payment_method_label']            = "Отметьте способы оплаты, которые указаны в вашем договоре с Яндекс.Деньгами";
$_['forwork_money']                         = "";
$_['enable_money']                          = "Включить прием платежей в кошелек на Яндексе";
$_['redirectUrl_help']                      = "Скопируйте эту ссылку в поле Redirect URL на <a href='https://money.yandex.ru/myservices/online.xml' target='_blank'>странице настройки уведомлений</a>.";
$_['account_head']                          = "Настройки приема платежей";
$_['wallet']                                = "Номер кошелька";
$_['password']                              = "Секретное слово";
$_['account_help']                          = "Cекретное слово нужно скопировать со <a href='https://money.yandex.ru/myservices/online.xml' target='_blank'>странице настройки уведомлений</a> на сайте Яндекс.Денег";
$_['option_wallet']                         = "Способы оплаты";
$_['kassa_payment_method_default']          = "Способ оплаты по умолчанию";
$_['kassa_success_page_label']              = "Страница успеха платежа";
$_['kassa_page_default']                    = "Стандартная---";
$_['kassa_success_page_description']        = "Эту страницу увидит покупатель, когда оплатит заказ";
$_['kassa_failure_page_label']              = "Страница отказа";
$_['page_standart']                         = "Стандартная---";
$_['kassa_failure_page_description']        = "Эту страницу увидит покупатель, если что-то пойдет не так: например, если ему не хватит денег на карте";
$_['successMP_label']                       = "Страница успеха для способа «Оплата картой при доставке»";
$_['successMP_help']                        = "Это страница с информацией о доставке. Укажите на ней, когда привезут товар и как его можно будет оплатить";
$_['kassa_page_title_label']                = "Название платежного сервиса";
$_['kassa_page_title_help']                 = "Это название увидит пользователь";
$_['kassa_description_title']               = "Описание платежа";
$_['kassa_description_default_placeholder'] = "Оплата заказа №%order_id%";
$_['kassa_description_help']                = "Это описание транзакции, которое пользователь увидит при оплате, а вы — в личном кабинете Яндекс.Кассы. <br>
Ограничение для описания — 128 символов.";
$_['kassa_send_receipt_label']              = 'Отправлять в Яндекс.Кассу данные для чеков (54-ФЗ)';
$_['kassa_all_tax_rate_label']              = 'НДС';
$_['kassa_tax_rate_table_label']            = '';
$_['kassa_default_tax_rate_label']          = 'Ставка по умолчанию';
$_['kassa_default_tax_rate_description']    = 'Ставка по умолчанию будет в чеке, если в карточке товара не указана другая ставка.';
$_['kassa_tax_rate_label']                  = 'Ставка в вашем магазине';
$_['kassa_tax_rate_description']            = 'Сопоставьте ставки';
$_['kassa_tax_rate_site_header']            = 'Ставка в вашем магазине';
$_['kassa_tax_rate_kassa_header']           = 'Ставка для чека в налоговую';
$_['kassa_feature_header']                  = "Дополнительные настройки для администратора";
$_['kassa_debug_label']                     = "Запись отладочной информации";
$_['kassa_view_logs']                       = 'Просмотр имеющихся логов';
$_['disable']                               = "Отключена";
$_['enable']                                = "Включена";
$_['kassa_debug_description']               = "Настройку нужно будет поменять, только если попросят специалисты Яндекс.Денег";
$_['kassa_before_redirect_label']           = 'Когда пользователь переходит к оплате';
$_['kassa_create_order_label']              = 'Создать неоплаченный заказ в панели управления';
$_['kassa_clear_cart_label']                = 'Удалить товары из корзины';
$_['kassa_order_status_label']              = "Статус заказа после оплаты";
$_['kassa_ordering_label']                  = "Порядок сортировки";
$_['kassa_geo_zone_label']                  = "Регион отображения";
$_['kassa_notification_url_label']          = 'Адрес для уведомлений';
$_['kassa_notification_url_description']    = 'Этот адрес понадобится, только если его попросят специалисты Яндекс.Кассы';
$_['kassa_page_title_default']              = 'Яндекс.Касса (банковские карты, электронные деньги и другое)';

$_['wallet_tab_label']                = 'Яндекс.Деньги';
$_['wallet_header_description']       = '';
$_['wallet_enable']                   = 'Включить прием платежей в кошелек на Яндексе';
$_['wallet_redirect_url_description'] = "Скопируйте эту ссылку в поле Redirect URL на <a href='https://money.yandex.ru/myservices/online.xml' target='_blank'>странице настройки уведомлений</a>.";
$_['wallet_account_head']             = 'Настройки приема платежей';
$_['wallet_number_label']             = 'Номер кошелька';
$_['wallet_password_label']           = 'Секретное слово';
$_['wallet_account_description']      = "Cекретное слово нужно скопировать со <a href='https://money.yandex.ru/myservices/online.xml' target='_blank'>странице настройки уведомлений</a> на сайте Яндекс.Денег";
$_['wallet_option_label']             = 'Способы оплаты';
$_['wallet_feature_header']           = 'Дополнительные настройки для администратора';
$_['wallet_debug_label']              = 'Запись отладочной информации';
$_['wallet_debug_description']        = "Настройку нужно будет поменять, только если попросят специалисты Яндекс.Денег";
$_['wallet_before_redirect_label']    = 'Когда пользователь переходит к оплате';
$_['wallet_create_order_label']       = 'Создать неоплаченный заказ в панели управления';
$_['wallet_clear_cart_label']         = 'Удалить товары из корзины';
$_['wallet_order_status_label']       = "Статус заказа после оплаты";
$_['wallet_ordering_label']           = "Порядок сортировки";
$_['wallet_geo_zone_label']           = "Регион отображения";
$_['wallet_all_zones']                = 'Все зоны';

$_['tab_billing']                = 'Яндекс.Платежка';
$_['billing_header']             = '';
$_['billing_enable']             = 'Включить прием платежей через Платежку';
$_['billing_form_id']            = 'ID формы';
$_['billing_purpose']            = 'Назначение платежа';
$_['billing_purpose_desc']       = 'Назначение будет в платежном поручении: напишите в нем всё, что поможет отличить заказ, который оплатили через Платежку';
$_['billing_purpose_default']    = 'Номер заказа %order_id% Оплата через Яндекс.Платежку';
$_['billing_status']             = 'Статус заказа';
$_['billing_status_desc']        = 'Статус должен показать, что результат платежа неизвестен: заплатил клиент или нет, вы можете узнать только из уведомления на электронной почте или в своем банке';
$_['billing_feature_header']     = 'Дополнительные настройки для администратора';
$_['billing_debug_label']        = 'Запись отладочной информации';
$_['billing_debug_description']  = "Настройку нужно будет поменять, только если попросят специалисты Яндекс.Денег";
$_['billing_order_status_label'] = "Статус заказа после оплаты";
$_['billing_ordering_label']     = "Порядок сортировки";
$_['billing_geo_zone_label']     = "Регион отображения";
$_['billing_all_zones']          = 'Все зоны';
$_['error_ya_billing_id']        = 'Не был указан идентификатор Платёжки';
$_['error_ya_billing_purpose']   = 'Не было указано назначение платежа';
$_['error_ya_billing_status']    = 'Не был указан статус заказа';

$_['tab_updater']                             = 'Обновления';
$_['updater_header']                          = 'Обновление модуля';
$_['updater_enable']                          = 'Включить возможность обновления модуля';
$_['updater_error_text_restore']              = 'Не удалось восстановить данные из резервной копии, подробную информацию о произошедшей ошибке можно найти в <a href="%s">логах модуля</a>';
$_['updater_error_text_remove']               = 'Не удалось удалить резервную копию %s, подробную информацию о произошедшей ошибке можно найти в <a href="%s">логах модуля</a>';
$_['updater_restore_success_text']            = 'Резервная копия %s был успешно удалён';
$_['updater_check_version_flash_message']     = 'Версия модуля %s была успешно загружена и установлена';
$_['updater_error_text_unpack_failed']        = 'Не удалось распаковать загруженный архив %s. Подробная информация об ошибке — в <a href="%s">логах модуля</a>';
$_['updater_error_text_create_backup_failed'] = 'Не удалось создать резервную копию установленной версии модуля. Подробная информация об ошибке — в <a href="%s">логах модуля</a>';
$_['updater_error_text_load_failed']          = 'Не удалось загрузить архив, попробуйте еще раз. Подробная информация об ошибке — в <a href="%s">логах модуля</a>';
$_['updater_log_text_load_failed']            = 'Не удалось загрузить архив с обновлением';

$_['order_captured_text']       = 'Платёж для заказа №%s подтверждён';
$_['payments_list_title']       = 'Список платежей';
$_['payments_list_breadcrumbs'] = 'Список платежей через модуль Кассы';

$_['text_method_yandex_money'] = 'Яндекс.Деньги';
$_['text_method_bank_card']    = 'Банковские карты — Visa, Mastercard и Maestro, «Мир»';
$_['text_method_cash']         = "Наличные";
$_['text_method_mobile']       = 'Баланс мобильного — Билайн, Мегафон, МТС, Tele2';
$_['text_method_webmoney']     = 'Webmoney';
$_['text_method_alfabank']     = 'Альфа-Клик';
$_['text_method_sberbank']     = 'Сбербанк Онлайн';
$_['text_method_ma']           = 'MasterPass';
$_['text_method_pb']           = 'Интернет-банк Промсвязьбанка';
$_['text_method_qiwi']         = 'QIWI Wallet';
$_['text_method_qp']           = 'Доверительный платеж (Куппи.ру)';
$_['text_method_mp']           = 'Мобильный терминал';
$_['text_method_installments'] = 'Заплатить по частям';
$_['bank_cards_title']         = 'Банковские карты';
$_['cash_title']               = 'Наличные через терминалы';
$_['mobile_balance_title']     = 'Баланс мобильного';

$_['text_vat_none'] = 'без НДС';
$_['text_vat_10']   = 'Расчетная ставка 10/110';
$_['text_vat_18']   = 'Расчетная ставка 18/118';

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

$_['nps_text'] = 'Помогите нам улучшить модуль Яндекс.Кассы — ответьте на %s один вопрос %s';

$_['b2b_sberbank_label']             = 'Включить платежи через Сбербанк Бизнес Онлайн';
$_['b2b_sberbank_on_label']          = 'Если эта опция включена, вы можете принимать онлайн-платежи от юрлиц. Подробнее — <a href="https://kassa.yandex.ru">на сайте Кассы.</a>';
$_['b2b_sberbank_template_label']    = 'Шаблон для назначения платежа';
$_['b2b_sberbank_vat_default_label'] = 'Ставка НДС по умолчанию';
$_['b2b_sberbank_template_help']     = 'Это назначение платежа будет в платёжном поручении.';
$_['b2b_sberbank_vat_default_help']  = 'Эта ставка передаётся в Сбербанк Бизнес Онлайн, если в карточке товара не указана другая ставка.';
$_['b2b_sberbank_vat_label']         = 'Сопоставьте ставки НДС в вашем магазине со ставками для Сбербанка Бизнес Онлайн';
$_['b2b_sberbank_vat_cms_label']     = 'Ставка НДС в вашем магазине';
$_['b2b_sberbank_vat_sbbol_label']   = 'Ставка НДС для Сбербанк Бизнес Онлайн';
$_['b2b_tax_rate_untaxed_label']     = 'Без НДС';
$_['b2b_tax_rate_7_label']           = '7%';
$_['b2b_tax_rate_10_label']          = '10%';
$_['b2b_tax_rate_18_label']          = '18%';
$_['b2b_sberbank_tax_message']       = 'При оплате через Сбербанк Бизнес Онлайн есть ограничение: в одном чеке могут быть только товары с одинаковой ставкой НДС. Если клиент захочет оплатить за один раз товары с разными ставками — мы покажем ему сообщение, что так сделать не получится.';

$_['kassa_default_payment_mode_label']    = 'Признак способа расчета';
$_['kassa_default_payment_subject_label'] = 'Признак предмета расчета';
$_['ffd_help_message']                    = 'Признаки предмета расчёта и способа расчёта берутся из атрибутов товара payment_mode и payment_subject . Их значения можно задать отдельно в карточке товара, если это потребуется. <a href="https://kassa.yandex.ru/docs/guides/#perehod-na-ffd-1-05">Подробнее.</a>

Для товаров, у которых значения этих атрибутов не заданы, будем применять значения по умолчанию:';