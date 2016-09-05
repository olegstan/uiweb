<?php

namespace Framework\Debug;

use Framework\Pattern\PatternTraits\NonStaticTrait;
use Framework\Pattern\PatternTraits\SingletonTrait;

class Debug
{
    use SingletonTrait, NonStaticTrait;

    private static $seconds = [];
    private static $queries = [];
    private static $errors = [];

    public function __handle()
    {
        return $this;
    }

    public static function getQueries()
    {
        return self::$queries;
    }

    public static function addQuery($query)
    {
        self::$queries[] = $query;
    }

    public static function getErrors()
    {
        return self::$errors;
    }

    public static function addError($error)
    {
        self::$errors[] = $error;
    }

    public static function addSecondsStart($key)
    {
        self::$seconds[$key]['start'] = microtime(true);
    }

    public static function addSecondsEnd($key)
    {
        self::$seconds[$key]['end'] = microtime(true);
    }

    public static function addSeconds($key, $type = 'start')
    {
        self::$seconds[$key][$type] = microtime(true);
    }

    public static function getSecconds()
    {
        foreach(self::$seconds as $key => $second){
            if(isset($second[$key]['start']) && isset($second[$key]['end'])){
                self::$seconds[$key]['result'] = number_format(($second[$key]['start'] - $second[$key]['end']), 5);
            }
        }
        return self::$seconds;
    }

    public static function getBytes()
    {
        return memory_get_usage(true);
    }

    public static function printData()
    {
        foreach(self::getQueries() as $query){
            echo '<!--' . $query . ' -->';
        }
        foreach(self::getErrors() as $error){
            echo '<!--' . $error . ' bytes -->';
        }
        foreach(self::getSecconds() as $second){
            echo '<!--' . $second['result'] . ' bytes -->';
        }
    }
}