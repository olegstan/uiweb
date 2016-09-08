<?php
namespace App\Models\Product;

use App\Layers\LayerDatabaseModel;

/**
 * Class Tag
 * @package App\Models\Product
 */
class Tag extends LayerDatabaseModel
{
    protected $table = 'products_tags';

    protected $fillable = [
        'product_id',
        'group_id',
        'tag_id',
        'value_id'
    ];
}