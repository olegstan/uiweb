<?php
namespace Database\Migrations;

use Framework\Schema\Migration;
use Framework\Db\Table;
use Framework\Schema\Schema;

class CreateCountriesTable extends Migration
{
    public function getDate()
    {
        return '2016-01-10 18:25:16';
    }

    public function getName()
    {
        return 'create_countries_table';
    }

    public function up()
    {
        Schema::create('countries', function (Table $table) {
            $table->int('id')->setAutoIncrement()->setUnsigned()->setPrimaryKey();
            $table->char('name', 100);
            $table->char('alias', 100);
            $table->char('code', 10);
            $table->char('pattern', 50);
            $table->int('flag', 7)->setNullable();
        });
    }

    public function down()
    {
        Schema::drop('countries');
    }
}