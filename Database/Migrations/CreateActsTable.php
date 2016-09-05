<?php
namespace Database\Migrations;

use Framework\Schema\Migration;
use Framework\Db\Table;
use Framework\Schema\Schema;

class CreateActsTable extends Migration
{
    public function getDate()
    {
        return '2016-02-08 12:02:44';
    }

    public function getName()
    {
        return 'create_acts_table';
    }

    public function up()
    {
        Schema::create('acts', function(Table $table)
        {
            $table->int('id')->setAutoIncrement()->setUnsigned()->setPrimaryKey();
            $table->integer('number');
            $table->integer('customer_company_id');
            $table->integer('provider_company_id');
            $table->timestamp('created_at')->setNullable();
            $table->timestamp('modified_at')->setNullable();
        });
    }

    public function down()
    {
        Schema::drop('acts');
    }
}