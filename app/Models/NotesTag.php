<?
namespace app\model;

use app\layer\LayerModel;

class NotesTag extends LayerModel
{
    protected $table = 'notes-tags';

    public function join($rules)
    {
        switch($rules){
            case 'tags':
                return [
                    [
                        'class_name' => 'Tags',
                        'table_name' => 'tags',
                        'key' => 'tag_id',
                        'foreign_key' => 'id',
                        'fields' => [
                            'name' => 'name',
                            'alias' => 'alias',
                            'is_published' => 'is_published'
                        ]
                    ]
                ];
                break;
        }
    }
}