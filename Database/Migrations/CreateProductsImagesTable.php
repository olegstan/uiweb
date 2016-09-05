<?php
namespace Database\Migrations;

use Framework\Schema\Migration;
use Framework\Db\Table;
use Framework\Schema\Schema;

class CreateProductsImagesTable extends Migration
{
    public function getDate()
    {
        return '2016-01-24 23:43:37';
    }

    public function getName()
    {
        return 'create_products_images_table';
    }

    public function up()
    {
        Schema::create('products_images', function (Table $table) {
            $table->int('id')->setAutoIncrement()->setUnsigned()->setPrimaryKey();
            $table->int('product_id');
            $table->int('position')->setNullable();
            $table->varchar('path');
        });
    }

    public function down()
    {
        Schema::drop('products_images');
    }
}