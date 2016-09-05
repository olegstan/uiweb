<?php
namespace Database\Migrations;

use Framework\Schema\Migration;
use Framework\Db\Table;
use Framework\Schema\Schema;

class CreateInvoicesTable extends Migration
{
    public function getDate()
    {
        return '2016-02-08 12:02:37';
    }

    public function getName()
    {
        return 'create_invoices_table';
    }

    public function up()
    {
        Schema::create('invoices', function(Table $table)
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
        Schema::drop('invoices');
    }
}