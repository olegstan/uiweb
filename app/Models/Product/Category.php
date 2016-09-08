<?php
namespace App\Models\Product;

use App\Layers\LayerDatabaseModel;

/**
 * Class Category
 *
 * @property int $id
 * @property string $name
 * @property string $url
 * @property string $category_url
 * @property string $preview
 * @property string $description
 * @property int $category_id
 * @property string $title
 * @property string $meta_keywords
 * @property string $meta_description
 * @property int $position
 * @property string $created_at
 * @property string $modified_at
 * @property bool $is_active
 *
 * @package App\Models\Product
 */
class Category extends LayerDatabaseModel
{
    protected $table = 'products_categories';

    protected $fillable = [
        'name',
        'url',
        'preview',
        'description',
        'code',
        'category_id',
        'title',
        'meta_keywords',
        'meta_description',
        'position',
        'created_at',
        'modified_at',
    ];
}