<?php
namespace App\Layers;

use Framework\Model\Types\DatabaseModel;

class LayerDatabaseModel extends DatabaseModel
{
    public function getRules($scenario = null)
    {
        return [];
    }
}