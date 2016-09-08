<?php
namespace app\models\material;

use app\layer\LayerModel;

class MaterialCategory extends LayerModel
{
    protected $table = 'mc_materials_categories';

    public $materials = [];
    public $materials_count = 0;

    public $url;

    public function afterSelect()
    {
        $this->url = '/pages/' . $this->url . '/';

        return $this;
    }

    public function getMaterials($condition, $bind = null)
    {
        $category = $this
            ->query()
            ->select()
            ->where($condition, $bind)
            ->limit()
            ->execute()
            ->one()
            ->getResult();



        if($category){
            $materials = (new Material())
                ->query()
                ->select()
                ->where('parent_id = :parent_id', [':parent_id' => $category->id])
                ->order('position')
                ->execute()
                ->all($category->url, 'id')
                ->getResult();

            $category->materials = $materials;
            $category->materials_count = count($materials);
        }

        return $category;
    }



}