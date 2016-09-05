<?php
namespace Framework\Schema;

use Framework\Db\Table;

class Migration
{
    public function up()
    {
        if (!Schema::exists('migrations')) {
            Schema::create('migrations', function (Table $table) {
                $table->int('id')->setAutoIncrement()->setUnsigned()->setPrimaryKey();
                $table->varchar('name')->setUnique();
                $table->int('batch');
                $table->timestamp('migrated_at')->setNullable();
            });
        }
    }

    public function down()
    {
        if (Schema::exists('migrations')) {
            Schema::drop('migrations');
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        return '';
    }

    /**
     * @return string
     */
    public function getDate()
    {
        return '';
    }
}