<?php
namespace Database\Migrations;

use Framework\Schema\Migration;
use Framework\Db\Table;
use Framework\Schema\Schema;

class CreateCompaniesTable extends Migration
{
    public function getDate()
    {
        return '2016-02-19 01:16:19';
    }

    public function getName()
    {
        return 'create_companies_table';
    }

    public function up()
    {
        Schema::create('companies', function(Table $table)
        {
            $table->int('id')->setAutoIncrement()->setUnsigned()->setPrimaryKey();
            $table->int('user_id')->setUnsigned();
            $table->varchar('name', 255);
            $table->char('inn', 12)->setUnique();
            $table->char('kpp', 9);
            $table->char('bik', 9);
            $table->char('correspondent_account', 25);
            $table->char('operating_account', 25);
            $table->varchar('jur_address', 255);
            $table->varchar('nat_address', 255);
            $table->tinyint('is_active')->setDefault(1);
            $table->integer('position');
            $table->timestamp('created_at')->setNullable();
            $table->timestamp('modified_at')->setNullable();
        });
    }

    public function down()
    {
        Schema::drop('companies');
    }
}