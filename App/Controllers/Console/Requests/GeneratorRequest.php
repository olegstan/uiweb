<?php
namespace App\Controllers\Console\Requests;

use App\Layers\LayerConsoleRequest;

class GeneratorRequest extends LayerConsoleRequest
{
    public function getRules($scenario = null)
    {
        return [
            ['field' => 'type', 'property' => 'type', 'rule' => 'required', 'msg' => ''],
            ['field' => 'name', 'property' => 'name', 'rule' => 'required', 'msg' => ''],
            ['field' => 'type', 'property' => 'type', 'rule' => 'not_empty', 'msg' => ''],
            ['field' => 'name', 'property' => 'name', 'rule' => 'not_empty', 'msg' => ''],
        ];
    }

    public static function getTemplateType()
    {
        return self::input('type', 'string');
    }

    public static function getName()
    {
        return self::input('name', 'string');
    }
}