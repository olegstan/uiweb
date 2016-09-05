<?php
return '<?php
namespace Database\Seeds;

use Framework\Model\Types\DatabaseModel;
use Framework\Schema\Seed;

class ' . \Framework\Text\Inflector::camelize($name) . ' extends Seed
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