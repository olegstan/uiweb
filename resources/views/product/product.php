<?php
/**
 * @var App\Models\Product\Product $product
 * @var Framework\View\View $this
 */
?>
<link rel="stylesheet" href="/assets/css/product.css">
<div class="container">
    <div class="item col-lg-12 col-md-12 col-sm-12 col-xs-12">
        <div class="item-left">
            <a href="<?=$this->getUrl()->route('catalog_product', ['category_url' => $product->category_url, 'product_url' => $product->product_url])?>">
                <img src="<?=$product->image->resized(250, 250)?>">
            </a>
        </div>
        <div class="item-right">
            <h4><?=$product->name?></h4>
            <button class="btn btn-default" onclick="cart.add(<?=$product->id?>)">Купить</button>
        </div>
        <div style="clear: both"></div>
        <div class="item-tabs">
            <div class="item-tab active">Характеристики</div>
        </div>
        <div class="item-tabs-content">
            <div class="item-tab-content">
                <div class="item-characters">
                    <?php if($product->tags_groups){ ?>
                        <?php foreach($product->tags_groups as $group_id => $tag_group){ ?>
                            <div class="item-group-craracter"><?=$tag_group->group_name?></div>
                            <?php if($product->tags[$group_id]){ ?>
                                <?php foreach($product->tags[$group_id] as $tag){ ?>
                                    <div class="item-character"><?=$tag['name']?>: <?=$tag['value']?></div>
                                <?php } ?>
                            <?php } ?>
                        <?php } ?>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>

