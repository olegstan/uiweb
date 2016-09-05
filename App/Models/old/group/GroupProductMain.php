<?php
namespace app\models\group;

use app\layer\LayerModel;
use app\models\badge\Badge;
use app\models\badge\BadgeProduct;
use app\models\group\GroupRelatedProduct;
use app\models\image\Image;
use app\models\product\Product;
use app\models\tag\Tag;
use app\models\tag\TagProduct;
use app\models\Variant;
use core\Collection;

class GroupProductMain extends LayerModel
{
    protected $table = 'mc_groups_products_main';

    public $module_id = 2;

    public $products = [];

    public static function products(Collection $collection, $rules = null)
    {
        switch($rules['type']){
            case 'one':


                break;
            case 'all':
                $groups_products_main_ids = $collection->getId();
                $groups_products_main = $collection->getResult();

                if($groups_products_main) {
                    $groups_related_products_main = (new GroupRelatedProduct())
                        ->query()
                        ->select()
                        ->where('group_id IN (' . implode(',', $groups_products_main_ids) . ')')
                        ->order('position')
                        ->execute()
                        ->all(null, 'id');

                    $products_related = $groups_related_products_main->getResult();
                    $products_ids = $groups_related_products_main->getField('product_id');

                    if($products_related){
                        $products = (new Product())
                            ->query()
                            ->with(['visibleBadges', 'visibleVariants'])
                            ->select()
                            ->where('id IN (' . implode(',', $products_ids) . ')')
                            ->execute()
                            ->all(null, 'id')
                            ->getResult();
                    }

                    $tags_products_collection = (new TagProduct())
                        ->query()
                        ->select()
                        ->where('product_id IN (' . implode(',', $products_ids) . ')')
                        ->execute()
                        ->all(null, 'id');

                    $tags_ids = $tags_products_collection->getField('tag_id');
                    $tags_products = $tags_products_collection->getResult();

                    $tags = (new Tag())
                        ->query()
                        ->select()
                        ->where('id IN (' . implode(',', $tags_ids) . ')')
                        ->execute()
                        ->all(null, 'id')
                        ->getResult();


                    foreach ($tags_products as $tag_product) {
                        $products[$tag_product->product_id]->tags[] = $tags[$tag_product->tag_id];
                    }

                    /**
                     * id модуля 2
                     * показываем только первую картинку товара
                     */

                    $images = (new Image())
                        ->query()
                        ->select()
                        ->where('object_id IN (' . implode(',', $products_ids) . ') AND module_id = 2 AND position = 0')
                        ->group('object_id')
                        ->execute()
                        ->all(['folder' => 'products'], 'object_id')
                        ->getResult();

                    foreach ($products_related as $product) {
                        $products[$product->product_id]->image = $images[$product->product_id];
                        $groups_products_main[$product->group_id]->products[] = $products[$product->product_id];
                    }

                    foreach ($groups_products_main as $k => $group) {
                        if (!count($group->products)) {
                            unset($groups_products_main[$k]);
                        }
                    }

                }
                break;
        }
    }
}