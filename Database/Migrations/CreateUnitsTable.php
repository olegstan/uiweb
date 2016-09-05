<?php
namespace Database\Migrations;

use Framework\Schema\Migration;
use Framework\Db\Table;
use Framework\Schema\Schema;

class CreateUnitsTable extends Migration
{
    public function getDate()
    {
        return '2016-02-08 12:17:48';
    }

    public function getName()
    {
        return 'create_units_table';
    }

    public function up()
    {
        Schema::create('units', function(Table $table)
        {
            $table->int('id')->setAutoIncrement()->setUnsigned()->setPrimaryKey();
            $table->varchar('name');
        });
    }

    public function down()
    {
        Schema::drop('units');
    }
}