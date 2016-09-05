<?php
namespace Database\Migrations;

use Framework\Schema\Migration;
use Framework\Db\Table;
use Framework\Schema\Schema;

class CreateCurrenciesTable extends Migration
{
    public function getDate()
    {
        return '2016-01-09 16:39:50';
    }

    public function getName()
    {
        return 'create_currencies_table';
    }

    public function up()
    {
        Schema::create('currencies', function (Table $table) {
            $table->int('id')->setAutoIncrement()->setUnsigned()->setPrimaryKey();
            $table->char('name', 100);
            $table->char('code', 5);
            $table->char('sing', 100);
        });
    }

    public function down()
    {
        Schema::drop('currencies');
    }
}