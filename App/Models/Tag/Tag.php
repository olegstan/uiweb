<?php
namespace App\Models\Tag;

use App\Layers\LayerDatabaseModel;

/**
 * Class Tag
 *
 * @property string name
 *
 * @package App\Models\Product
 */
class Tag extends LayerDatabaseModel
{
    protected $table = 'tags';

    protected $fillable = [
        'name'
    ];
}