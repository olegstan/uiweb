<?php
namespace Database\Seeds;

use Framework\Model\Types\DatabaseModel;
use Framework\Schema\Seed;

class FillCategoriesTable extends Seed
{
    public function getName()
    {
        return 'fill_categories_table';
    }

    public function getClass()
    {
        return 'App\\Models\\Product\\Category';
    }

    public function getFields()
    {
//        return [
//            'name',
//            ''
//        ];
    }

    public function up()
    {
        return [];
    }
}