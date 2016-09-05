<?php
/**
 * @var Framework\View\View $this
 */
$this->setLayoutPath('layouts/main.php');
?>
<div class="container">
    <div class="row">
        <?php
        /**
         * @var $product App\Models\Product\Product
         */
        foreach ($products as $product) {
            echo $this->render('product/product.php', ['product' => $product]);
        }
        ?>
    </div>
</div>
