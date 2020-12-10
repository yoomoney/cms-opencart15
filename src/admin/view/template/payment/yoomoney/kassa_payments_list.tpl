<?php echo $header; ?>

<div id="content">
    <?php include dirname(__FILE__) . '/breadcrumbs.php'; ?>
    <div class="box">
        <div class="heading">
            <h1><img src="view/image/order.png" alt="">Список платежей через модуль ЮKassa</h1>
            <div class="buttons"><a href="<?php echo $update_link; ?>" class="button">Обновить список</a><a href="<?php echo $capture_link; ?>" class="button">Провести все платежи</a></div>
        </div>
        <div class="content">
            <table class="list">
                <thead>
                <tr>
                    <td class="center">ID заказа</td>
                    <td class="center">ID платежа</td>
                    <td class="center">Сумма</td>
                    <td class="center">Оплачен</td>
                    <td class="center">Статус</td>
                    <td class="center">Дата создания</td>
                    <td class="center">Дата подтверждения</td>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($payments as $payment): ?>
                <tr>
                    <td class="center"><?php echo $payment['order_id']; ?></td>
                    <td class="left"><?php echo $payment['payment_id']; ?></td>
                    <td class="right"><?php echo $payment['amount'] . ' ' . $payment['currency']; ?></td>
                    <td class="center"><?php echo $payment['paid'] === 'Y' ? 'да' : 'нет'; ?></td>
                    <td class="center"><?php echo $payment['status']; ?></td>
                    <td class="center"><?php echo $payment['created_at']; ?></td>
                    <td class="center"><?php
                    if ($payment['captured_at'] === '0000-00-00 00:00:00'):
                        echo 'не подтверждён';
                    else:
                        echo $payment['captured_at'];
                    endif;
                    ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div class="pagination">
                <div class="results"><?php echo $pagination; ?></div>
            </div>
        </div>
    </div>
</div>

<?php echo $footer; ?>
