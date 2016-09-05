<?php
namespace Database\Migrations;

use Framework\Schema\Migration;
use Framework\Db\Table;
use Framework\Schema\Schema;

class CreateRolesTable extends Migration
{
    public function getDate()
    {
        return '2016-02-16 22:31:34';
    }

    public function getName()
    {
        return 'create_roles_table';
    }

    public function up()
    {
        Schema::create('roles', function (Table $table) {
            $table->int('id')->setAutoIncrement()->setUnsigned()->setPrimaryKey();
            $table->varchar('name');
            $table->varchar('slug')->setUnique();
            $table->varchar('description')->setNullable();
            $table->int('level')->setDefault(1);
            $table->timestamp('created_at')->setNullable();
            $table->timestamp('modified_at')->setNullable();
        });
    }

    public function down()
    {
        Schema::drop('roles');
    }
}