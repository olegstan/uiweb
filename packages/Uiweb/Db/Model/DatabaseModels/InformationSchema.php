<?php
namespace Uiweb\Model\DatabaseModels;

use Uiweb\Model\Types\DatabaseModel;

class InformationSchema extends DatabaseModel
{
    protected $table = 'tables';

    public $connection = 'information_schema';

    public function getRules($scenario = null)
    {

    }
}