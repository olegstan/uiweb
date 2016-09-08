<?php
namespace App\Models\Act;

use App\Layers\LayerDatabaseModel;

class Good extends LayerDatabaseModel
{
    protected $table = 'acts_goods';

    protected $fillable = [
        'act_id',
        'name',
        'count',
        'price',
        'position',
        'unit_id'
    ];

//    public function unit()
//    {
//        return $this->hasOne('App\Models\InvoicesGoodsUnit', 'id', 'unit_id');
//    }

}