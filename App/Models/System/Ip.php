<?php
namespace app\model;

use App\Layers\LayerDatabaseModel;

class Ip extends LayerDatabaseModel
{
    protected $table = 'ips';

    public function join()
    {

    }
}