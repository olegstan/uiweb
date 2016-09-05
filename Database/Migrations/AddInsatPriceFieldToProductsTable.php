<?php
namespace Database\Migrations;

use Framework\Schema\Migration;
use Framework\Db\Table;
use Framework\Schema\Schema;

class AddInsatPriceFieldToProductsTable extends Migration
{
    public function getDate()
    {
        return '2016-01-11 17:47:36';
    }

    public function getName()
    {
        return 'add_insat_price_field_to_products_table';
    }

    public function up()
    {
        Schema::update('products', function(Table $table){
            $table->decimal('insat_price', 10, 2)->setDefault(0)->setAfter('price');
        });
    }

    public function down()
    {
        Schema::update('products', function(Table $table){
            $table->dropField('insat_price');
        });
    }
}