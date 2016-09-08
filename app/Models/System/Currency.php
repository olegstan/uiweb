<?php
namespace App\Models\System;

use App\Layers\LayerDatabaseModel;

class Currency extends LayerDatabaseModel
{
    protected $table = 'currencies';

    protected $fillable = [
        'name',
        'code',
        'sign'
    ];

    public function getByCode($code)
    {
        return $this->getQuery()->select()->where('code = :code', [':code' => $code])->execute()->one()->getResult();
    }
}