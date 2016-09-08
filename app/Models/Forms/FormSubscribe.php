<?
namespace app\model;

use app\layer\LayerModel;

class FormSubscribe extends LayerModel
{
    protected $table = 'form-subscribes';

    public function rules($scenario)
    {
        return [
            ['field' => 'email', 'filter' => 'trim']
        ];
    }

    public function validateRules($scenario)
    {
        return [
            ['field' => 'email', 'property' => 'email', 'rule' => 'unique', 'class_name' => 'FormSubscribe', 'class_field' => 'email', 'table_field' => '`form-subscribes`.`email`', 'msg' => 'Вы уже подписаны на рассылку'],
            ['field' => 'email', 'property' => 'email', 'rule' => 'email', 'msg' => 'Адрес электронной почты не соответствует формату'],
            ['field' => 'email', 'property' => 'email', 'rule' => 'max_length', 'length' => 253, 'msg' => 'Адрес электронной почты не может содержать больше 320 символов'],
        ];
    }




}