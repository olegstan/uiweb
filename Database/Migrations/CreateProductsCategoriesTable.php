<?php
namespace Database\Migrations;

use Framework\Schema\Migration;
use Framework\Db\Table;
use Framework\Schema\Schema;

class CreateProductsCategoriesTable extends Migration
{
    public function getDate()
    {
        return '2016-01-10 10:00:29';
    }

    public function getName()
    {
        return 'create_products_categories_table';
    }

    public function up()
    {
        Schema::create('products_categories', function (Table $table) {
            $table->int('id')->setAutoIncrement()->setUnsigned()->setPrimaryKey();
            $table->varchar('name');
            $table->varchar('url');
            $table->varchar('preview', 511)->setNullable();
            $table->text('description')->setNullable();
            $table->char('code', 100)->setNullable();
            $table->int('category_id')->setNullable();
            $table->varchar('title')->setNullable();
            $table->varchar('meta_keywords')->setNullable();
            $table->varchar('meta_description')->setNullable();
            $table->int('position')->setNullable();
            $table->timestamp('created_at')->setNullable();
            $table->timestamp('modified_at')->setNullable();
        });
    }

    public function down()
    {
        Schema::drop('products_categories');
    }
}