<?php
namespace Uiweb;


class Core
{
    public static function run()
    {
        echo '<pre>';
        var_dump(1);
        echo '</pre>';
        die();
    }

//    public $classes = [
//        'auth' => ''
//    ];

    public $pool = [];

    public function load()
    {
//        foreach (Config::get('core', 'boot') as $class) {
//            $this->pool[] = $class::getInstance();
//        }
    }

}