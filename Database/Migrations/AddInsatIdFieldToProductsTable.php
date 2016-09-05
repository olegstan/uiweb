<?php
namespace Database\Migrations;

use Framework\Db\Table;
use Framework\Schema\Migration;
use Framework\Schema\Schema;

class AddInsatIdFieldToProductsTable extends Migration
{
    public function getDate()
    {
        return '2016-01-22 11:33:19';
    }

    public function getName()
    {
        return 'add_insat_id_field_to_products_table';
    }

    public function up()
    {
        Schema::update('products', function(Table $table){
            $table->integer('insat_id')->setNullable()->setAfter('insat_price');
        });
    }

    public function down()
    {
        Schema::update('products', function(Table $table){
            $table->dropField('insat_id');
        });
    }
}