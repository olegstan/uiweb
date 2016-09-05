<?php
namespace App\Models\Invoice;

use App\Layers\LayerDatabaseModel;

class Good extends LayerDatabaseModel
{
    protected $table = 'invoices_goods';

    protected $fillable = [
        'invoice_id',
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