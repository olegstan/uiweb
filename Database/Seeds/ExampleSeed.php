<?php
namespace Database\Seeds;

use Framework\Model\Types\DatabaseModel;
use Framework\Schema\Seed;

class FillBrandsTable extends Seed
{
    public function getName()
    {
        return 'fill_brands_table';
    }

    public function getClass()
    {
        return 'App\\Models\\Product\\Brand';
    }

    public function getFields()
    {
        return [
            'name',
            'alias'
        ];
    }

    public function up()
    {
        return [
            //name, alias
            ['Moxa', 'moxa'],
            ['ADLink', 'adlink'],
            ['ADVANTECH', 'advantech'],
            ['ICP DAS', 'icp das'],
            ['NEXCOM', 'nexcom'],
            ['IEI', 'iei'],
        ];
    }
}