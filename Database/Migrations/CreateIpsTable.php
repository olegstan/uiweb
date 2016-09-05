<?php
namespace Database\Migrations;

use Framework\Schema\Migration;
use Framework\Db\Table;
use Framework\Schema\Schema;

class CreateIpsTable extends Migration
{
    public function getDate()
    {
        return '2016-01-02 09:42:25';
    }

    public function getName()
    {
        return 'create_ips_table';
    }

    public function up()
    {
        Schema::create('ips', function (Table $table) {
            $table->int('id')->setAutoIncrement()->setUnsigned()->setPrimaryKey();
            $table->char('ip', 11);
            $table->timestamp('created_at')->setNullable();
        });
    }

    public function down()
    {
        Schema::drop('ips');
    }
}