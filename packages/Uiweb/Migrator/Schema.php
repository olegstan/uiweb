<?php
namespace Uiweb\Schema;

use Closure;
use Uiweb\Db\Table;

class Schema
{
    public static function create($table_name, Closure $callback)
    {
        $table = new Table($table_name);

        $callback($table);

        return $table->create();
    }

    public static function update($table_name, Closure $callback)
    {
        $table = new Table($table_name);

        $callback($table);

        return $table->update();
    }

    public static function drop($table_name)
    {
        $table = new Table($table_name);

        return $table->drop();
    }

    public static function truncate($table_name)
    {
        $table = new Table($table_name);

        return $table->truncate();
    }

    public static function exists($table_name)
    {
        $table = new Table($table_name);

        return $table->exists();
    }
}