<?php
return '<?php
namespace Database\Migrations;

use Uiweb\Schema\Migration;
use Uiweb\Db\Table;
use Uiweb\Schema\Schema;

class ' . \Uiweb\Text\Inflector::camelize($name) . ' extends Migration
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