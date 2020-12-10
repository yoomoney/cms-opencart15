<?php if (isset($header)) :
    echo $header;
endif; ?>
<h4>Failure</h4>
<?php if (isset($order)):
    $link = $this->url->link('account/order/info', 'order_id=' . $order['order_id']);
?>
<p><a href="<?php echo $link; ?>">Order#<?php echo $order['order_id']; ?></a> payment failure</p>
<?php else: ?>
<p>Order payment failure</p>
<?php endif; ?>
<?php if (isset($footer)):
    echo $footer;
endif; ?>