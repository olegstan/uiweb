<?php
namespace app\models\group;

use app\layer\LayerModel;
use app\models\category\Category;
use app\models\group\GroupRelatedCategory;
use app\models\image\Image;

class GroupCategoryMain extends LayerModel
{
    protected $table = 'mc_groups_categories_main';

    public $module_id = 3;

    public $categories = [];

    public function getRelatedCategories()
    {
        $groups_categories_main_collection = $this
            ->query()
            ->select()
            ->where('is_visible = :is_visible', [':is_visible' => 1])
            ->order('position')
            ->execute()
            ->all(null, 'id');

        $groups_categories_main_ids = $groups_categories_main_collection->getId();
        $groups_categories_main = $groups_categories_main_collection->getResult();

        $groups_related_categories_main = (new GroupRelatedCategory())
            ->query()
            ->select()
            ->where('group_id IN (' . implode(',', $groups_categories_main_ids) . ')')
            ->order('position')
            ->execute()
            ->all(null, 'id');


        $categories_related = $groups_related_categories_main->getResult();
        $categories_ids = $groups_related_categories_main->getField('category_id');

        $categories = (new Category())
            ->query()
            ->select()
            ->where('id IN (' . implode(',', $categories_ids) . ')')
            ->execute()
            ->all(null, 'id')
            ->getResult();

        /**
         * id модуля 3
         * показываем только первую картинку товара
         */

        $images = (new Image())
            ->query()
            ->select()
            ->where('object_id IN (' . implode(',', $categories_ids) . ') AND module_id = 3')
            //->order('position')
            ->execute()
            ->all(['folder' => 'categories'], 'object_id')
            ->getResult();

        foreach($categories_related as $k => $category){
            if(isset($images[$category->category_id])){
                $categories[$category->category_id]->image = $images[$category->category_id];
            }

            $groups_categories_main[$category->group_id]->categories[] = $categories[$category->category_id];
        }

        foreach($groups_categories_main as $k => $group){
            if(!count($group->categories)){
                unset($groups_categories_main[$k]);
            }
        }

        return $groups_categories_main;
    }

    //public function beforeSelect()
}