<?php
namespace Uiweb;

/**
 * Class Config
 * @package Uiweb
 */
class Config
{
    public static $data = [];

    /**
     * @param $key
     * @return mixed
     */
    public static function get($key)
    {

        $keys = explode('.', $key);

        $path = self::getFilePath(array_shift($keys));

        $array = require($path);

        foreach ($keys as $key) {
            $array = &$array[$key];
        }
        
        return $array;
        
        
//        if(!self::exists($file_name, $key)){
//            if(file_exists($path)){
//                self::set($file_name, require($path));
//            }else{
//                die(__FILE__ . ' ' . __LINE__ . 'Не найден файл с настройками: ' . $path);
//            }
//        }
//        return self::$data[$file_name][$key];
    }

    public static function getFilePath($file_name)
    {
        return ABS . '/config/' . $file_name .'.php';
    }

    /**
     * @param $fileName
     * @param $key
     * @return bool
     */
    public static function has($key)
    {

        $keys = explode('.', $key);

        $path = self::getFilePath(array_shift($keys));

        $array = require($path);

        foreach ($keys as $key) {
            if(isset($array[$key])){
                $array = &$array[$key];
            }else{
                return false;
            }
        }

        return true;
    }

    /**
     * @param $file_name
     * @param $key
     * @param $data
     */
//    public static function set($file_name, $data)
//    {
//        self::$data[$file_name] = $data;
//    }
}