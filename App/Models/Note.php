<?
namespace app\model;

use app\layer\LayerModel;
use core\behavior\Rules;
use core\helper\Text;
use core\helper\Translit;

class Note extends LayerModel implements Rules
{
    protected $table = 'notes';

    public function join()
    {
        return [
            [
                'class_name' => 'NotesTag',
                'table_name' => 'notes-tags',
                'key' => 'id',
                'foreign_key' => 'note_id',
                'fields' => [
                    'tag_id' => 'tag_id'
                ]
            ],
            [
                'class_name' => 'NoteCategory',
                'table_name' => 'note-categories',
                'key' => 'category_id',
                'foreign_key' => 'id',
                'fields' => [
                    'name' => 'category_name'
                ]
            ]
        ];
    }

    public function validateRules($scenario)
    {
        return [];
    }

    public function rules($scenario)
    {
        switch ($scenario) {
            case 'insert':
                return [
                    ['field' => 'name', 'filter' => 'trim'],
                    ['field' => 'alias', 'filter' => 'trim'],
                    ['field' => 'text', 'filter' => 'trim']
                ];
        }
    }

    public function beforeSelect($rules = null)
    {
        $this->created_month = strftime('%b', $this->created_at);
        $this->created_day = strftime('%d', $this->created_at);

        switch($rules){
            case 'index':
                $this->text = Text::previewByCharacters($this->text, 300) . '.....';
                break;
            case 'view':

                break;
        }
        return $this;
    }

    public function beforeInsert()
    {
        $this->user_id = $this->core->auth->user->id;
        if(empty($this->alias)){
            $this->alias = Translit::make($this->name, '_');
        }

        if(isset($this->created_at)){
            $this->modified_at = time();
        }else{
            $this->created_at = time();
        }
    }
}