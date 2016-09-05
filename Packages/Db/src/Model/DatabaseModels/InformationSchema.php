<?php
namespace Framework\Model\DatabaseModels;

use Framework\Model\Types\DatabaseModel;

class InformationSchema extends DatabaseModel
{
    protected $table = 'tables';

    public $connection = 'information_schema';

    public function getRules($scenario = null)
    {

    }
}