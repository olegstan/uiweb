<?php
/**
 * @var App\Models\Product\Product $product
 * @var Framework\View\View $this
 */
?>
<div class="item col-lg-3 col-md-6 col-sm-12 col-xs-12">
    <div class="item-top">
        <a href="<?=$this->getUrl()->route('catalog_product', ['category_url' => $product->category_url, 'product_url' => $product->product_url])?>">
            <img src="<?=$product->image->resized(250, 250)?>">
        </a>
    </div>
    <div class="item-bottom">
        <h4><?=$product->name?></h4>
        <h4>Цена: <?=$this->getModificator()->toMoney($product->price)?></h4>
        <button class="btn btn-default" onclick="cart.add(<?=$product->id?>)">Купить</button>
    </div>
</div>


