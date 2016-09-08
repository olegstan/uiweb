<?php
namespace app\models\material;

use app\layer\LayerModel;
use core\Collection;
use app\models\material\MaterialMenuItem;

class MaterialMenu extends LayerModel
{
    protected $table = 'mc_materials_menu';

    public $items = [];

    public static function items(Collection $collection, $rules = null)
    {
        switch($rules['type']){
            case 'one':
                $material_menu = $collection->getResult();

                if($material_menu){
                    $items = (new MaterialMenuItem)
                        ->query()
                        ->with(['materials'])
                        ->select()
                        ->where('is_visible = 1 AND menu_id = :menu_id', [':menu_id' => $material_menu->id])
                        ->execute()
                        ->all(null, 'id')
                        ->getResult();

                    if($items){
                        $material_menu->items = $items;
                    }
                }
                break;
            case 'all':

                break;
        }
    }
}