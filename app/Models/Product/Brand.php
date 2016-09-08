<?php
namespace App\Models\Product;

use App\Layers\LayerDatabaseModel;

class Brand extends LayerDatabaseModel
{
    protected $table = 'brands';

    protected $fillable = [
        'name',
        'alias'
    ];

    public function getByAlias($alias)
    {
        return $this->getQuery()->select()->where('alias = :alias', [':alias' => $alias])->execute()->one()->getResult();
    }
}