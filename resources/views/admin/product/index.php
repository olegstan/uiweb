<?php
/**
 * @var $this Framework\View\View
 */
$this->setLayoutPath('layouts/admin.php');
?>
<?php $this->startBuffer('content'); ?>
<table>
<?php
//    foreach ($products as $product) {
//        echo '<tr>';
//            echo '<td>' . $product->name . '</td>';
//            echo '<td>' . $product->url . '</td>';
//            echo '<td>' . $product->price . '</td>';
//        echo '</tr>';
//    }
    foreach ($products as $product) {
        echo $this->render('admin/product/product.php', ['product' => $product]);
    }
?>
</table>
<?php $this->endBuffer(); ?>


