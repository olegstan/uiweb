<?php
namespace Framework;
/**
 * Class Autoloader
 *
 * 
 * @package Framework
 */
class Autoloader
{
    /**
     * @var self|null
     */
    public static $loader;

    /**
     * @return self
     */
    public static function init()
    {
        if(!static::$loader){
            static::$loader = new static();
        }
        return static::$loader;
    }

    /**
     * @return void
     */
    public function __construct()
    {
        spl_autoload_register([$this, 'autoload']);
    }

    /**
     * @param $class
     *
     * @return void
     */
    public function autoload($class)
    {
        //директория с ядром

        $parts = explode('\\', $class);
        $count_parts = count($parts);

        $file_path = ABS . '/';

        $i = 0;
        foreach($parts as $part){
            if($i !== ($count_parts - 1)){
                $file_path .= $parts[$i] . '/';
            }else{
                $file_path .= $parts[$i] . '.php';
            }
            $i++;
        }

        if(file_exists($file_path)){
            require_once($file_path);
        }else{
            echo 'Не найден файл ' . $file_path;
        }
    }

}

/**
 * запускаем автоподгрузку
 */
new Autoloader();