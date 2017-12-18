<?php if (isset($breadcrumbs)): ?>
<div class="breadcrumb">
    <?php $link = $this->url->link('common/home', 'token=' . $this->session->data['token'], true); ?>
    <a href="<?php echo $link; ?>">Главная</a>
    <?php $link = $this->url->link('extension/payment', 'token=' . $this->session->data['token'], true); ?>
    :: <a href="<?php echo $link; ?>">Модули платежей</a>
    <?php $link = $this->url->link('payment/yamoney', 'token=' . $this->session->data['token'], true); ?>
    :: <a href="<?php echo $link; ?>">Яндекс.Деньги 2.0</a>
    <?php foreach ($breadcrumbs as $breadcrumb): ?>
        :: <a href="<?php echo $breadcrumb['link']; ?>"><?php echo $breadcrumb['name']; ?></a>
    <?php endforeach; ?>
</div>
<?php endif; ?>