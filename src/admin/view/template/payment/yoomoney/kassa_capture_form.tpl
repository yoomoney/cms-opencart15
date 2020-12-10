<?php echo $header; ?>

<div id="content">
    <?php include dirname(__FILE__) . '/breadcrumbs.php'; ?>
    <div class="box">
        <div class="heading">
            <h1><img src="view/image/order.png" alt=""><?= $language->get('captures_payment_data'); ?></h1>
        </div>
        <div class="content">
            <form id="form_capture" method="post" action="<?= $capture_action; ?>">
                <input type="hidden" name="action" id="captures_action" value="">
                <table class="form">
                    <tbody>
                    <tr>
                        <td><?= $language->get('captures_payment_id')?></td>
                        <td><?= $payment->getId(); ?></td>
                    </tr>
                    <tr>
                        <td><?= $language->get('captures_order_id')?></td>
                        <td><?= $order['order_id']; ?></td>
                    </tr>
                    <tr>
                        <td><?= $language->get('captures_payment_method')?></td>
                        <td><?= $this->language->get('text_method_'.$payment->getPaymentMethod()->getType()); ?></td>
                    </tr>
                    <?php if($payment->getExpiresAt() && !$payment->getCapturedAt()): ?>
                    <tr>
                        <td><?= $language->get('captures_expires_date')?></td>
                        <td><?= $payment->getExpiresAt() ? $payment->getExpiresAt()->format('d.m.Y H:i') : ''; ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td><?= $language->get('captures_payment_sum')?></td>
                        <td><?= $payment->getAmount()->getValue() . ' ' . $payment->getAmount()->getCurrency(); ?>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <table class="list">
                                <thead>
                                <tr>
                                    <td class="left"><?= $language->get('column_product'); ?></td>
                                    <td class="left"><?= $language->get('column_model'); ?></td>
                                    <td class="right"><?= $language->get('column_quantity'); ?></td>
                                    <td class="right"><?= $language->get('column_price'); ?></td>
                                    <td class="right"><?= $language->get('column_total'); ?></td>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($products as $product) { ?>
                                <tr>
                                    <td class="left">
                                        <a href="<?= $product['href']; ?>"><?= $product['name']; ?></a>
                                    </td>
                                    <td class="left"><?= $product['model']; ?></td>
                                    <td class="right">
                                        <input
                                                type="number"
                                                name="quantity[<?= $product['product_id']; ?>]"
                                                value="<?= $product['quantity']; ?>"
                                                min="0"
                                                max="<?= $product['quantity']; ?>"
                                                size="4"
                                                style="text-align: right"
                                                class="product-quantity"
                                                <?= !$is_waiting_for_capture ? ' readonly="readonly"' : ''; ?>
                                                data-total-element-id="product-total-<?= $product['product_id']; ?>"
                                                data-price="<?= $product['price']; ?>"
                                                data-total="<?= $product['total']; ?>"
                                                data-delta="0"
                                                readonly="readonly"
                                        >
                                    </td>
                                    <td class="right"><?= sprintf("%01.2f", $product['price']); ?></td>
                                    <td class="right product-total" id="product-total-<?= $product['product_id']; ?>">
                                        <?= sprintf("%01.2f", $product['total']); ?>
                                    </td>
                                </tr>
                                <?php } ?>
                                <?php foreach ($vouchers as $voucher) { ?>
                                <tr>
                                    <td class="left"><a
                                                href="<?= $voucher['href']; ?>"><?= $voucher['description']; ?></a></td>
                                    <td class="left"></td>
                                    <td class="right">1</td>
                                    <td class="right"><?= $voucher['amount']; ?></td>
                                    <td class="right"><?= $voucher['amount']; ?></td>
                                </tr>
                                <?php } ?>
                                </tbody>
                                <?php foreach ($totals as $total) { ?>
                                <tbody id="totals">
                                <tr>
                                    <td colspan="4" class="right"><?= $total['title']; ?>:</td>
                                    <td class="right">
                                        <input
                                                type="hidden"
                                                name="totals[<?= $total['code']; ?>]"
                                                value="<?= $total['value']; ?>"
                                                id="order-<?= $total['code']; ?>"
                                                data-original-value="<?= $total['value']; ?>"
                                        >
                                        <span id="span-order-<?= $total['code']; ?>">
                                            <?= sprintf("%01.2f", $total['value']); ?>
                                        </span>
                                    </td>
                                </tr>
                                </tbody>
                                <?php } ?>
                            </table>
                        </td>
                    </tr>
                    <?php if($is_waiting_for_capture): ?>
                    <tr>
                        <td></td>
                        <td>
                            <div class="buttons">
                                <a href="javascript:void();" class="button" id="captures_capture_create">
                                    <?= $language->get('captures_capture_create'); ?>
                                </a>
                                <a href="javascript:void();" class="button" id="captures_capture_cancel">
                                    <?= $language->get('captures_capture_cancel'); ?>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php if($payment->getCapturedAt()): ?>
                    <tr>
                        <td><?= $language->get('captures_captured')?></td>
                        <td><?= $payment->getCapturedAt()->format('d.m.Y H:i'); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if($message): ?>
                    <tr>
                        <td></td>
                        <td><?= $message ?></td>
                    </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </form>
        </div>
    </div>
</div>
<script type="text/javascript">
    jQuery(document).ready(function () {
        function changeProductTotal(element) {
            tolal = element.attr('data-total');
            delta = (tolal - element.val() * element.attr('data-price')).toFixed(2);
            element.attr('data-delta', delta);
            $('#' + element.attr('data-total-element-id')).text((tolal - delta).toFixed(2));
            changeTotals();
        }
        function changeTotals(){
            delta = 0;
            $('.product-quantity').each(function() {
                delta += parseFloat($(this).attr('data-delta'))
            });
            $('#order-sub_total').val(($('#order-sub_total').attr('data-original-value') - delta).toFixed(2));
            $('#span-order-sub_total').text($('#order-sub_total').val());
            $('#order-total').val(($('#order-total').attr('data-original-value') - delta).toFixed(2));
            $('#span-order-total').text($('#order-total').val());
        }
        $('#captures_capture_create').on('click', function(){
            $('#captures_action').val('capture');
            $('#form_capture').submit();
        });
        $('#captures_capture_cancel').on('click', function(){
            $('#captures_action').val('cancel');
            $('#form_capture').submit();
        });
        $('.product-quantity').on('change', function(){
            changeProductTotal($(this));
        });
        $('.product-quantity').on('keyup', function(){
            changeProductTotal($(this));
        });
    });
</script>

<?php echo $footer; ?>
