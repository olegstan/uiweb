<?php
namespace App\Controllers\Console\Requests;

use App\Layers\LayerConsoleRequest;

class SeedRequest extends LayerConsoleRequest
{
    public function getRules($scenario = null)
    {

    }

    public static function getName()
    {
        return self::input('name', 'string');
    }
}