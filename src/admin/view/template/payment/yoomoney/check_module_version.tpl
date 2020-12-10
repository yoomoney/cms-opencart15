<?php echo $header; ?>
<link rel="stylesheet" href="https://yastatic.net/bootstrap/3.3.6/css/bootstrap.min.css">
<div id="content" class="container">
    <div class="row">
        <div class="col-12">
            <h3>Проверка наличия новой версии модуля</h3>
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

    <ul>
        <li>Установленная версия модуля: <?php echo $currentVersion; ?></li>
        <li>Последняя доступная версия модуля: <?php echo $newVersion; ?></li>
        <li>
            Дата проверки наличия новой версии: <?php echo $newVersionInfo['date'] ?>
            <?php if (time() - $newVersionInfo['time'] > 300) : ?>
            <button type="button" class="btn btn-success btn-xs" id="force-check">Проверить наличие обновлений</button>
            <?php endif; ?>
        </li>
    </ul>

    <?php if ($new_version_available) : ?>

    <h4>История изменений:</h4>
    <p><?php echo $changelog; ?></p>
    <button type="button" id="update-module" class="btn btn-primary">Обновить модуль</button>
    <form method="post" id="update-form">
        <input name="update" value="1" type="hidden" />
        <input name="version" value="<?php echo htmlspecialchars($newVersion) ?>" type="hidden" />
    </form>

    <?php else: ?>
    <p>Установлена последняя версия модуля.</p>
    <?php endif; ?>

    <form method="post" id="check-version">
        <input name="force" value="1" type="hidden" />
    </form>
</div>

<script type="text/javascript">
    jQuery(document).ready(function () {
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

<?php echo $footer; ?>
