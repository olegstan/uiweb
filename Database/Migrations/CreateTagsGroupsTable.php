<?php
namespace Database\Migrations;

use Framework\Schema\Migration;
use Framework\Db\Table;
use Framework\Schema\Schema;

class CreateTagsGroupsTable extends Migration
{
    public function getDate()
    {
        return '2016-02-08 17:36:20';
    }

    public function getName()
    {
        return 'create_tags_groups_table';
    }

    public function up()
    {
        Schema::create('tags_groups', function (Table $table) {
            $table->int('id')->setAutoIncrement()->setUnsigned()->setPrimaryKey();
            $table->varchar('name');
        });
    }

    public function down()
    {
        Schema::drop('tags_groups');
    }
}