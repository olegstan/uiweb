<?php
namespace Database\Migrations;

use Framework\Schema\Migration;
use Framework\Db\Table;
use Framework\Schema\Schema;

class CreateActsGoods extends Migration
{
    public function getDate()
    {
        return '2016-02-08 12:04:08';
    }

    public function getName()
    {
        return 'create_acts_goods';
    }

    public function up()
    {
        Schema::create('acts_goods', function(Table $table)
        {
            $table->int('id')->setAutoIncrement()->setUnsigned()->setPrimaryKey();
            $table->integer('invoice_id');
            $table->varchar('name', 511);
            $table->integer('count');
            $table->decimal('price', 10, 2);
            $table->integer('position');
            $table->integer('unit_id')->setDefault(1);
        });
    }

    public function down()
    {
        Schema::drop('acts_goods');
    }
}