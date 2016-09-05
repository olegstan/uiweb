<?php
namespace Database\Migrations;

use Framework\Schema\Migration;
use Framework\Db\Table;
use Framework\Schema\Schema;

class CreateDomainsTable extends Migration
{
    public function getDate()
    {
        return '2016-01-09 21:19:09';
    }

    public function getName()
    {
        return 'create_domains_table';
    }

    public function up()
    {
        Schema::create('domains', function (Table $table) {
            $table->int('id')->setAutoIncrement()->setUnsigned()->setPrimaryKey();
            $table->varchar('name', 255);
        });
    }

    public function down()
    {
        Schema::drop('domains');
    }
}