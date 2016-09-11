<?php
return '<?php
namespace Database\Seeds;

use Uiweb\Model\Types\DatabaseModel;
use Uiweb\Schema\Seed;

class ' . \Uiweb\Text\Inflector::camelize($name) . ' extends Seed
{
    public function getName()
    {
        return \'' . $name . '\';
    }

    public function getClass()
    {
        return \'\';
    }

    public function getFields()
    {
        return [];
    }

    public function up()
    {
        return [];
    }
}';