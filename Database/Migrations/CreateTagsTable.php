<?php
namespace Database\Migrations;

use Framework\Schema\Migration;
use Framework\Db\Table;
use Framework\Schema\Schema;

class CreateTagsTable extends Migration
{
    public function getDate()
    {
        return '2016-02-08 17:31:19';
    }

    public function getName()
    {
        return 'create_tags_table';
    }

    public function up()
    {
        Schema::create('tags', function (Table $table) {
            $table->int('id')->setAutoIncrement()->setUnsigned()->setPrimaryKey();
            $table->varchar('name');
        });
    }

    public function down()
    {
        Schema::drop('tags');
    }
}