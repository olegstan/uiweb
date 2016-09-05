<?php namespace App\Models;

use App\Layers\LayerDatabaseModel;

class InvoicesGoodsUnit extends LayerDatabaseModel
{
    protected $table = 'units';

    protected $fillable = [
        'name',
    ];



}