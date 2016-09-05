<?php
/**
 * @var Framework\View\View $this
 */
$this->setLayoutPath('layouts/main.php');
?>
<?php $this->startBuffer('content'); ?>
<div class="container">
    <div class="row products-list">
        <?php
        /**
         * @var $product App\Models\Product\Product
         */
        foreach ($products as $product) {
            echo $this->render('product/product-list.php', ['product' => $product]);
        }
        ?>
    </div>
</div>
<?php $this->endBuffer(); ?>
<?php $this->startBuffer('footer'); ?>
    <script>
        var bar;
        var cart;
        var equalHeight;
        Autoload.prototype.loadClasses(["Component/Bar/Bar", "Component/EquableHeight", "Component/Cart/Cart"], function(){
            bar = (new Bar()).handle("bar");
            cart = new Cart();
            equalHeight = (EquableHeight()).handle("item");
        });
    </script>
<?php $this->endBuffer(); ?>