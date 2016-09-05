<?php
namespace Database\Migrations;

use Framework\Schema\Migration;
use Framework\Db\Table;
use Framework\Schema\Schema;

class CreateInvoicesGoods extends Migration
{
    public function getDate()
    {
        return '2016-02-08 12:05:06';
    }

    public function getName()
    {
        return 'create_invoices_goods';
    }

    public function up()
    {
        Schema::create('invoices_goods', function(Table $table)
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
        Schema::drop('invoices_goods');
    }
}