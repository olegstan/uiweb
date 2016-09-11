<?php
namespace Uiweb\Schema;

use Uiweb\Model\Types\DatabaseModel;

class Seed
{

    public function getName()
    {
        return '';
    }

    public function getClass()
    {
        return '';
    }

    public function up()
    {
        return [];
    }

    public function getFields()
    {
        return [];
    }

    public function fill()
    {
        if($rows = $this->up()){
            foreach ($rows as $row) {
                $name = $this->getClass();
                /** @var DatabaseModel $class */
                $class = new $name();
                $attributes = [];
                foreach ($this->getFields() as $key => $field) {
                    $attributes[$field] = $row[$key];
                }

                $class->fill($attributes);
                $class->save();
            }
        }
    }

}