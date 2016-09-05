<?php
namespace Database\Seeds;

use Framework\Schema\Seed;

class FillCurrenciesTable extends Seed
{
    public function getName()
    {
        return 'fill_currencies_table';
    }

    public function getClass()
    {
        return 'App\\Models\\System\\Currency';
    }

    public function getFields()
    {
        return [
            'name',
            'code',
            'sign'
        ];
    }

    public function up()
    {
        return [
            //`name`, `code`, 'sign'
            ['Доллар США', 'USD', '$'],
            ['Российский рубль', 'RUB', '₽'],
            ['Гривна', 'UAH', '₴'],
            ['Тенге', 'KZT', '₸'],
            ['Белорусский рубль', 'BYR', '']
        ];
    }
}