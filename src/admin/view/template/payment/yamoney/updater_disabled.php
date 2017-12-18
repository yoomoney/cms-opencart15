<div role="tabpanel" class="tab-pane" id="updater">
    <div class="row">
        <div class="col-md-12">
            <h4><?php echo $lang->get('updater_header'); ?></h4>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <p>
                Здесь будут появляться новые версии модуля — с новыми возможностями или с исправленными ошибками.
            </p>
            <p>
                К сожалению функция обновления модуля недоступна
                <?php if (!$zip_enabled && !$curl_enabled): ?>
                    так как для не установлены расширения <strong>"zip"</strong> и <strong>"curl"</strong>.
                <?php elseif (!$zip_enabled) : ?>
                    так как для не установлено расширение <strong>"zip"</strong>.
                <?php elseif (!$curl_enabled) : ?>
                    так как для не установлено расширение <strong>"curl"</strong>.
                <?php endif; ?>
            </p>
        </div>
    </div>
</div>
