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
class Group extends LayerDatabaseModel
{
    protected $table = 'tags_groups';

    protected $fillable = [
        'name'
    ];
}