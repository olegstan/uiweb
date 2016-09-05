<?php
namespace Database\Migrations;

use Framework\Schema\Migration;
use Framework\Db\Table;
use Framework\Schema\Schema;

class CreateTagsValuesTable extends Migration
{
    public function getDate()
    {
        return '2016-02-08 17:58:52';
    }

    public function getName()
    {
        return 'create_tags_values_table';
    }

    public function up()
    {
        Schema::create('tags_values', function (Table $table) {
            $table->int('id')->setAutoIncrement()->setUnsigned()->setPrimaryKey();
            $table->varchar('value', 511);
        });
    }

    public function down()
    {
        Schema::drop('tags_values');
    }
}