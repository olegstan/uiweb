<?php
namespace app\models\tag;

use app\layer\LayerModel;
use core\Collection;

class TagGroup extends LayerModel
{
    protected $table = 'mc_tags_groups';

    public $tags = [];

    public $mode;
    public $mode_text;

    public function afterSelect()
    {
        $this->getModeText();

        return $this;
    }

    public function getModeText()
    {
        switch($this->mode){
            case 'select':
                $this->mode_text = 'Выпадающий список';
                return;
            case 'checkbox':
                $this->mode_text = 'Галочки (Чекбоксы)';
                return;
            case 'radio':
                $this->mode_text = 'Радиобаттон';
                return;
            case 'range':
                $this->mode_text = 'Диапазонный фильтр';
                return;
            case 'logical':
                $this->mode_text = 'Логический';
                return;
        }
    }

    public function tags(Collection $collection, $rules = null)
    {
        switch($rules['type']){
            case 'one':
                $tag_group = $collection->getResult();

                if($tag_group){
                    $tags = (new Tag())
                        ->query()
                        ->select()
                        ->where('group_id = :group_id', [':group_id' => $tag_group->id])
                        ->execute()
                        ->all(null, 'id')
                        ->getResult();

                    if($tags){
                        $tag_group->tags = $tags;
                    }
                }
                break;
            case 'all':
                $tag_groups = $collection->getResult();
                $tag_group_ids = $collection->getId();

                if($tag_groups){

                    $tags = (new Tag())
                        ->query()
                        ->select()
                        ->where('group_id IN (' . implode(',', $tag_group_ids) . ')')
                        ->execute()
                        ->all(null, 'id')
                        ->getResult();

                    if($tags){
                        foreach($tags as $tag){
                            $tag_groups[$tag->group_id]->tags[$tag->id] = $tag;
                        }
                    }
                }
                break;
        }
    }
}