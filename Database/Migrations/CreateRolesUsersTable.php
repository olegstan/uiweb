<?php
namespace Database\Migrations;

use Framework\Schema\Migration;
use Framework\Db\Table;
use Framework\Schema\Schema;

class CreateRolesUsersTable extends Migration
{
    public function getDate()
    {
        return '2016-02-16 22:32:55';
    }

    public function getName()
    {
        return 'create_roles_users_table';
    }

    public function up()
    {
        Schema::create('roles_users', function (Table $table) {
            $table->int('id')->setAutoIncrement()->setUnsigned()->setPrimaryKey();
            $table->int('user_id')->setUnsigned();
            $table->int('role_id')->setUnsigned();
            $table->timestamp('created_at')->setNullable();
            $table->timestamp('modified_at')->setNullable();
        });
    }

    public function down()
    {
        Schema::drop('roles_users');
    }
}