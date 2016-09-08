<?php
return '<?php
namespace Database\Migrations;

use Framework\Schema\Migration;
use Framework\Db\Table;
use Framework\Schema\Schema;

class ' . \Framework\Text\Inflector::camelize($name) . ' extends Migration
{
    public function getDate()
    {
        return \'' . date('Y-m-d H:i:s', time()) . '\';
    }

    public function getName()
    {
        return \''. $name .'\';
    }

    public function up()
    {
        //
    }

    public function down()
    {
        //
    }
}';