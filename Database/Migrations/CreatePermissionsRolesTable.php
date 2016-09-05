<?php
namespace Database\Migrations;

use Framework\Schema\Migration;
use Framework\Db\Table;
use Framework\Schema\Schema;

class CreatePermissionsRolesTable extends Migration
{
    public function getDate()
    {
        return '2016-02-16 22:43:34';
    }

    public function getName()
    {
        return 'create_permissions_roles_table';
    }

    public function up()
    {
        Schema::create('permissions_roles', function (Table $table) {
            $table->int('id')->setAutoIncrement()->setUnsigned()->setPrimaryKey();
            $table->int('permission_id')->setUnsigned();
            $table->int('role_id')->setUnsigned();
            $table->timestamp('created_at')->setNullable();
            $table->timestamp('modified_at')->setNullable();
        });
    }

    public function down()
    {
        Schema::drop('permissions_roles');
    }
}