<?php
namespace App\Models\Tag;

use App\Layers\LayerDatabaseModel;

/**
 * Class Tag
 *
 * @property string value
 *
 * @package App\Models\Product
 */
class Value extends LayerDatabaseModel
{
    protected $table = 'tags_values';

    protected $fillable = [
        'value'
    ];
}