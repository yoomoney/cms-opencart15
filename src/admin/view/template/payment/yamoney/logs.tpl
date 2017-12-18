<?php echo $header; ?>
<link rel="stylesheet" href="https://yastatic.net/bootstrap/3.3.6/css/bootstrap.min.css">
<div id="content" style="background: none;">
    <?php include dirname(__FILE__) . '/breadcrumbs.php'; ?>
    <div class="container" style="padding: 0 20px;">
        <div class="row">
            <div class="col-12">
                <h4>Журнал сообщений платежного модуля Яндекс.Деньги</h4>
            </div>
        </div>

        <form action="" method="post" id="log-form">
            <input type="hidden" name="clear-logs" value="0" />
            <input type="hidden" name="download" value="0" />
            <div class="row buttons">
                <div class="col-12" style="text-align: right;">
                    <button type="button" class="btn btn-primary" data-value="download">Скачать файл сообщений</button>
                    <button type="button" class="btn btn-danger" data-value="clear-logs">Очистить файл сообщений</button>
                </div>
            </div>
        </form>
        <div class="row">
            <div class="col-12">
                <pre style="margin-top: 10px; font-size: 9pt;"><?php echo $logs; ?></pre>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    jQuery(document).ready(function () {
        var form = jQuery('#log-form');
        form.find('button').bind('click', function () {
            form[0][this.dataset.value].value = 1;
            if (this.dataset.value == 'download') {
                form[0].target = '_blank';
            } else {
                form[0].target = '_self';
            }
            form[0].submit();
        });
    });
</script>
<?php echo $footer; ?>