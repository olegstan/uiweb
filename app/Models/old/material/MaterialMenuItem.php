<?php
namespace app\models\material;

use app\layer\LayerModel;
use core\Collection;
use app\models\material\Material;

class MaterialMenuItem extends LayerModel
{
    protected $table = 'mc_materials_menu_items';

    public $url;

    public static function materials(Collection $collection, $rules = null)
    {
        switch($rules['type']){
            case 'one':
                $item = $collection->getResult();
                if($item){
                    $material = $materials = (new Material())
                        ->query()
                        ->select()
                        ->where('is_visible = 1 AND id = :id', [':id' => $item->object_id])
                        ->execute()
                        ->limit()
                        ->one()
                        ->getResult();

                    if($material){
                        $item->material = $material;
                    }

                }
                break;
            case 'all':
                $items = $collection->getResult();

                if($items){
                    $material_ids = [];
                    $material_category_ids = [];

                    foreach($items as $item){
                        switch($item->object_type){
                            case 'material':
                                $material_ids[] = $item->object_id;
                                break;
                            case 'material-category':
                                $material_category_ids[] = $item->object_id;
                                break;
                        }
                    }

                    if($material_ids){
                        $materials = (new Material())
                            ->query()
                            ->select()
                            ->where('is_visible = 1 AND id IN (' . implode(',', $material_ids) . ')')
                            ->execute()
                            ->all('/pages/', 'id')
                            ->getResult();

                        foreach($items as $k => $item){
                            if(isset($materials[$item->object_id])){
                                $item->url = $materials[$item->object_id]->url;
                            }
                        }

                    }

                    if($material_category_ids){
                        $material_categories = (new MaterialCategory())
                            ->query()
                            ->select()
                            ->where('is_visible = 1 AND id IN (' . implode(',', $material_category_ids) . ')')
                            ->execute()
                            ->all(null, 'id')
                            ->getResult();

                        foreach($items as $k => $item){
                            if(isset($material_categories[$item->object_id])){
                                $item->url = $material_categories[$item->object_id]->url;
                            }
                        }
                    }
                }
                break;
        }
    }

}