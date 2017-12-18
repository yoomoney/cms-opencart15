<?php echo $header; ?>

<div id="content">
    <?php include dirname(__FILE__) . '/breadcrumbs.php'; ?>
    <div class="box">
        <div class="heading">
            <h1><img src="view/image/order.png" alt=""> Payments</h1>
            <div class="buttons"><a onclick="$('#form').attr('action', 'http://opencart15.local/admin/index.php?route=sale/order/invoice&amp;token=02f5a34d1c9287a918b1b01f53c5a0b1'); $('#form').attr('target', '_blank'); $('#form').submit();" class="button">Print Invoice</a><a href="http://opencart15.local/admin/index.php?route=sale/order/insert&amp;token=02f5a34d1c9287a918b1b01f53c5a0b1" class="button">Insert</a><a onclick="$('#form').attr('action', 'http://opencart15.local/admin/index.php?route=sale/order/delete&amp;token=02f5a34d1c9287a918b1b01f53c5a0b1&amp;filter_order_status_id=0'); $('#form').attr('target', '_self'); $('#form').submit();" class="button">Delete</a></div>
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
            <!-- <div class="pagination"><div class="results">Showing 1 to 4 of 4 (1 Pages)</div></div> -->
        </div>
    </div>
</div>

<?php echo $footer; ?>