<?php
namespace Database\Migrations;

use Framework\Schema\Migration;
use Framework\Db\Table;
use Framework\Schema\Schema;

class AddIsActiveFieldToProductsTable extends Migration
{
    public function getDate()
    {
        return '2016-01-11 17:48:10';
    }

    public function getName()
    {
        return 'add_is_active_field_to_products_table';
    }

    public function up()
    {
        Schema::update('products', function(Table $table){
            $table->tinyint('is_active')->setDefault(1);
        });
    }

    public function down()
    {
        Schema::update('products', function(Table $table){
            $table->dropField('is_active');
        });
    }
}