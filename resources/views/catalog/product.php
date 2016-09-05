<?php
/**
 * @var Framework\View\View $this
 */
$this->setLayoutPath('layouts/main.php');
?>
<?php $this->startBuffer('content'); ?>
<div class="container">
    <div class="row">
        <?=$this->render('product/product.php', ['product' => $product]);?>
    </div>
</div>
<?php $this->endBuffer(); ?>
<?php $this->startBuffer('footer'); ?>
<script>
    var bar;
    var cart;
    Autoload.prototype.loadClasses(["Component/Bar/Bar", "Component/Cart/Cart"], function(){
        bar = (new Bar()).handle("bar");
        cart = new Cart();
    });
</script>
<?php $this->endBuffer(); ?>
