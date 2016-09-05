<?php
namespace Database\Migrations;

use Framework\Schema\Migration;
use Framework\Db\Table;
use Framework\Schema\Schema;

class CreateBrandsTable extends Migration
{
    public function getDate()
    {
        return '2016-01-09 16:25:37';
    }

    public function getName()
    {
        return 'create_brands_table';
    }

    public function up()
    {
        Schema::create('brands', function (Table $table) {
            $table->int('id')->setAutoIncrement()->setUnsigned()->setPrimaryKey();
            $table->varchar('name');
            $table->varchar('alias');
        });
    }

    public function down()
    {
        Schema::drop('brands');
    }
}