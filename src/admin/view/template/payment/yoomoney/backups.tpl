<?php echo $header; ?>
<link rel="stylesheet" href="https://yastatic.net/bootstrap/3.3.6/css/bootstrap.min.css">
<div id="content" class="container">
    <div class="row">
        <div class="col-12">
            <h4>Список резервных копий платёжного модуля</h4>
        </div>
    </div>

    <?php if (isset($success) || !empty($errors)) : ?>
    <div class="row">
        <?php if (isset($success)) : ?>
        <div class='col-md-12'>
            <div class="success"><?php echo $success; ?></div>
        </div>
        <?php endif; ?>
        <?php if (!empty($errors)) : ?>
        <div class='col-md-12'>
            <?php foreach ($errors as $error) : ?>
            <div class="warning"><?php echo $error; ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (empty($backups)) : ?>
    <p>Не найдено ни одной резервной копии.</p>
    <?php else: ?>

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

    <?php endif; ?>
    <form id="action-form" method="post">
        <input type="hidden" name="action" id="action-form-action" value="none" />
        <input type="hidden" name="file_name" id="action-form-file-name" value="" />
        <input type="hidden" name="version" id="action-form-version" value="" />
    </form>
</div>
<?php echo $footer; ?>

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
    });
</script>