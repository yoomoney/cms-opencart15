<div role="tabpanel" class="tab-pane" id="updater">
    <div class="row">
        <div class="col-md-12">
            <p>
                Здесь будут появляться новые версии модуля — с новыми возможностями или с исправленными ошибками.
                Чтобы установить новую версию модуля, нажмите кнопку «Обновить».
            </p>
            <h4>О модуле:</h4>
            <ul>
                <li>Установленная версия модуля — <?php echo $currentVersion; ?></li>
                <li>Последняя версия модуля — <?php echo $newVersion; ?></li>
                <?php if (!empty($newVersionInfo)) : ?>
                <li>
                    Последняя проверка наличия новых версий — <?php echo $newVersionInfo['date'] ?>
                    <?php if (time() - $newVersionInfo['time'] > 300) : ?>
                        <button type="button" class="btn btn-success btn-xs" id="force-check">Проверить наличие обновлений</button>
                    <?php endif; ?>
                </li>
                <?php endif; ?>
            </ul>

            <?php if ($new_version_available) : ?>

                <h4>История изменений:</h4>
                <p><?php echo $changelog; ?></p>

                <button type="button" id="update-module" class="btn btn-primary">Обновить</button>
            <?php else: ?>
                <p>Установлена последняя версия модуля.</p>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($backups)) : ?>
    <div class="row">
        <div class="col-md-12">
            <h4>Резервные копии</h4>
            <table class="table table-striped table-hover">
                <thead>
                <tr>
                    <th>Версия модуля</th>
                    <th>Дата создания</th>
                    <th>Имя файла</th>
                    <th>Размер файла</th>
                    <th>&nbsp;</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($backups as $backup) : ?>
                    <tr>
                        <td><?php echo $backup['version'] ?></td>
                        <td><?php echo $backup['date'] ?></td>
                        <td><?php echo $backup['name'] ?></td>
                        <td><?php echo $backup['size'] ?></td>
                        <td class="text-right">
                            <button type="button" class="btn btn-success btn-xs restore-backup" data-id="<?php echo $backup['name'] ?>" data-version="<?php echo $backup['version'] ?>" data-date="<?php echo $backup['date'] ?>">Восстановить</button>
                            <button type="button" class="btn btn-danger btn-xs remove-backup" data-id="<?php echo $backup['name'] ?>" data-version="<?php echo $backup['version'] ?>" data-date="<?php echo $backup['date'] ?>">Удалить</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</div>

<script type="text/javascript">
    jQuery(document).ready(function () {
        jQuery('button.restore-backup').click(function () {
            var message = 'Вы действительно хотите восстановить модуль из резервной копии версии ' + this.dataset.version
                + ' от ' + this.dataset.date + '?';
            if (confirm(message)) {
                jQuery('#action-form-action').val('restore');
                jQuery('#action-form-file-name').val(this.dataset.id);
                jQuery('#action-form-version').val(this.dataset.version);
                jQuery('#action-form').submit();
            }
        });
        jQuery('button.remove-backup').click(function () {
            var message = 'Вы действительно хотите удалить резервную копию модуля версии ' + this.dataset.version
                + ' от ' + this.dataset.date + '?';
            if (confirm(message)) {
                jQuery('#action-form-action').val('remove');
                jQuery('#action-form-file-name').val(this.dataset.id);
                jQuery('#action-form-version').val(this.dataset.version);
                jQuery('#action-form').submit();
            }
        });
        jQuery('#force-check').click(function () {
            jQuery('#check-version')[0].submit();
        });
        <?php if ($new_version_available) : ?>
        jQuery('#update-module').click(function () {
            jQuery('#update-form')[0].submit();
        });
        <?php endif; ?>
    });
</script>