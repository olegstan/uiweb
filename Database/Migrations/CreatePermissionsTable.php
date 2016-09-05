<?php
namespace Database\Migrations;

use Framework\Schema\Migration;
use Framework\Db\Table;
use Framework\Schema\Schema;

class CreatePermissionsTable extends Migration
{
    public function getDate()
    {
        return '2016-02-16 22:31:59';
    }

    public function getName()
    {
        return 'create_permissions_table';
    }

    public function up()
    {
        Schema::create('permissions', function (Table $table) {
            $table->int('id')->setAutoIncrement()->setUnsigned()->setPrimaryKey();
            $table->varchar('name');
            $table->varchar('slug')->setUnique();
            $table->varchar('description')->setNullable();
            $table->timestamp('created_at')->setNullable();
            $table->timestamp('modified_at')->setNullable();
        });
    }

    public function down()
    {
        Schema::drop('permissions');
    }
}