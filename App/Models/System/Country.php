<?php
namespace App\Models\System;

use App\Layers\LayerDatabaseModel;

class Country extends LayerDatabaseModel
{
    protected $table = 'countries';

    protected $fillable = [
        'name',
        'alias',
        'code',
        'pattern',
        'flag'
    ];

//    public function rules($scenario)
//    {
//        switch ($scenario) {
//            case 'insert':
//                return [
//                    ['field' => 'name', 'filter' => 'trim']
//                ];
//                break;
//            case 'update':
//                return [
//                    ['field' => 'id', 'filter' => 'trim'],
//                    ['field' => 'name', 'filter' => 'trim'],
//                ];
//                break;
//            case 'delete':
//                return [
//                    ['field' => 'id', 'filter' => 'trim'],
//                ];
//                break;
//        }
//    }
}