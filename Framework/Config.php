<?php
namespace Framework;

use Framework\Pattern\PatternTraits\NonStaticTrait;
use Framework\Pattern\PatternTraits\SingletonTrait;

/**
 * Class Config
 * @package Framework
 */
class Config
{
    use SingletonTrait, NonStaticTrait;

    public static $data = [];

    /**
     * @return $this
     */
    public function __handle()
    {
        return $this;
    }

    /**
     * @param $file_name
     * @param $key
     * @return mixed
     */
    public static function get($file_name, $key)
    {
        $path = ABS . '/config/' . $file_name .'.php';
        if(!self::exists($file_name, $key)){
            if(file_exists($path)){
                self::set($file_name, require($path));
            }else{
                die(__FILE__ . ' ' . __LINE__ . 'Не найден файл с настройками: ' . $path);
            }
        }
        return self::$data[$file_name][$key];
    }

    /**
     * @param $file_name
     * @param $key
     * @return bool
     */
    public static function exists($file_name, $key)
    {
        return isset(self::$data[$file_name][$key]) ? true : false;
    }

    /**
     * @param $file_name
     * @param $key
     * @param $data
     */
    public static function set($file_name, $data)
    {
        self::$data[$file_name] = $data;
    }
}