<?php
namespace Database\Migrations;

use Framework\Schema\Migration;
use Framework\Db\Table;
use Framework\Schema\Schema;

class AddIpc2uPriceFieldToProductsTable extends Migration
{
    public function getDate()
    {
        return '2016-01-29 01:20:45';
    }

    public function getName()
    {
        return 'add_ipc2u_price_field_to_products_table';
    }

    public function up()
    {
        Schema::update('products', function(Table $table){
            $table->decimal('ipc2u_price', 10, 2)->setDefault(0)->setAfter('price');
        });
    }

    public function down()
    {
        Schema::update('products', function(Table $table){
            $table->dropField('ipc2u_price');
        });
    }
}