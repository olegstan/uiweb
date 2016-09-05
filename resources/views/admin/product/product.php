<?php
/**
 * @var App\Models\Product\Product $product
 */
?>
<div class="item col-lg-4 col-md-6 col-sm-12 col-xs-12">
    <?php
        if($product->images){
            foreach($product->images as $k => $image){
                echo '<img ' . (!$product->is_active ? '' : 'style="padding: 10px; background: red;"') . ' onclick="ajax.getRequestObject().get(\'/ajax/product/active/' . $product->id . '\'); this.style.padding = \'10px\'; this.style.background = \'red\';" src="' . $image->resized(200, 200) . '">';
            }
        }else{
            echo '<img ' . (!$product->is_active ? '' : 'style="padding: 10px; background: red;"') . ' onclick="ajax.getRequestObject().get(\'/ajax/product/active/' . $product->id . '\'); this.style.padding = \'10px\'; this.style.background = \'red\';" src="' . $product->image->resized(200, 200) . '">';
        }

    ?>

    <h4><?=$product->name?></h4>
</div>