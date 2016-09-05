<?php
namespace Database\Migrations;

use Framework\Schema\Migration;
use Framework\Db\Table;
use Framework\Schema\Schema;

class CreateUsersTable extends Migration
{
    public function getDate()
    {
        return '2016-01-08 12:12:47';
    }

    public function getName()
    {
        return 'create_users_table';
    }

    public function up()
    {
        Schema::create('users', function (Table $table) {
            $table->int('id')->setAutoIncrement()->setUnsigned()->setPrimaryKey();
            $table->char('username', 50);
            $table->char('password', 80);
            $table->char('auth_key', 50)->setNullable();
            $table->timestamp('last_login_at')->setNullable();
            $table->timestamp('created_at')->setNullable();
            $table->timestamp('modified_at')->setNullable();
        });
    }

    public function down()
    {
        Schema::drop('users');
    }
}