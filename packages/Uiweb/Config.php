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
     * @param $fileName
     * @param $key 'to.do.to.do'
     * @return mixed
     */
    public static function get($fileName, $key)
    {
        $path = self::getFilePath($fileName);
        
        //TODO cache
        
        $array = require($path);

        $keys = explode('.', $key);

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
    public static function has($fileName, $key)
    {
        $path = self::getFilePath($fileName);

        //TODO cache

        $array = require($path);

        $keys = explode('.', $key);

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