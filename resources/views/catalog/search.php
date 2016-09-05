<?php
/**
 * @var Framework\View\View $this
 */
$this->setLayoutPath('layouts/main.php');
?>
<?php $this->startBuffer('content'); ?>
<div class="container">
    <h2>Результат запроса "<?=$search?>"</h2>
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
