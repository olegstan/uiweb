<?php
/**
 * @var Framework\View\View $this
 */
$this->setLayoutPath('layouts/account.php');
?>
<?php $this->startBuffer('content'); ?>
<div class="container">
    <?php if($products){ ?>
        <h2>Корзина</h2>
        <p></p>
        <table class="table">
            <thead>
                <tr>
                    <th>№</th>
                    <th>Название</th>
                    <th>Код</th>
                    <th>Количество</th>
                    <th>Цена</th>
                    <th>Сумма</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php $k = 0; ?>
            <?php $sum = 0; ?>
            <?php foreach ($products as $product) { ?>
                <?php $sum += $product->sum; ?>
                <tr data-product="<?=$product->id?>">
                    <td data-number><?=++$k?></td>
                    <td data-name><a href="<?=$this->getUrl()->route('catalog_product', ['category_url' => $product->category_url, 'product_url' => $product->product_url])?>"><?=$product->name?></a></td>
                    <td data-code><?=$product->code?></td>
                    <td data-count><input type="text" onkeyup="cart.change(<?=$product->id?>, event, this)" value="<?=$product->count?>"></td>
                    <td data-price><?=$this->getModificator()->toMoney($product->price)?></td>
                    <td data-sum><?=$this->getModificator()->toMoney($product->sum)?></td>
                    <td><button onclick="cart.delete(<?=$product->id?>, event, this)" class="btn btn-default">Удалить</button></td>
                </tr>
            <?php } ?>
                <tr>
                    <td>Итого:</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td data-sum><?=$this->getModificator()->toMoney($sum)?></td>
                    <td></td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th><button class="btn btn-default">Оформить заказ</button></th>
                </tr>
            </tfoot>
        </table>
    <?php }else{ ?>
        <h2>Корзина пуста</h2>
    <?php } ?>
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
