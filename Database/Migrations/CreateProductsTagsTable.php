<?php
namespace Database\Migrations;

use Framework\Schema\Migration;
use Framework\Db\Table;
use Framework\Schema\Schema;

class CreateProductsTagsTable extends Migration
{
    public function getDate()
    {
        return '2016-02-08 17:56:21';
    }

    public function getName()
    {
        return 'create_products_values_table';
    }

    public function up()
    {
        Schema::create('products_tags_values', function (Table $table) {
            $table->int('id')->setAutoIncrement()->setUnsigned()->setPrimaryKey();
            $table->integer('product_id');
            $table->integer('group_id');
            $table->integer('tag_id');
            $table->integer('value_id');
        });
    }

    public function down()
    {
        Schema::drop('');
    }
}