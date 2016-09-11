<?php
namespace Uiweb\Console;

use Uiweb\Pattern\PatternTraits\NonStaticTrait;
use Uiweb\Pattern\PatternTraits\SingletonTrait;

/**
 * Class ConsoleParameterCollection
 * @package Uiweb\Console
 */
class ConsoleParameterCollection
{
    use SingletonTrait, NonStaticTrait;

    /**
     * @var array
     */
    public static $argv = [];
    /**
     * @var int
     */
    public static $argc = 0;
    /**
     * @var array
     */
    public static $map = [];

    /**
     * @return $this
     */
    public function __handle()
    {
        global $argc, $argv;
        self::$argv = $argv ? $argv : [];
        self::$argc = $argc ? $argc : 0;

        return $this;
    }

    public static function setMap()
    {
        if(self::$argv){
            foreach (self::$argv as $key => $param) {
                if($key === 0){
                    continue;
                }
                $parts = explode('=', $param);
                self::$map[$parts[0]] = isset($parts[1]) ? $parts[1] : '';
            }
        }
    }

    /**
     * @return array
     */
    public static function getMap()
    {
        if(!self::$map){
            self::setMap();
        }
        return self::$map;
    }

    /**
     * @return null
     */
    public static function getPath()
    {
        return isset(self::$argv[0]) ? self::$argv[0] : null;
    }

    public static function getCommand()
    {
        return isset(self::$argv[1]) ? self::$argv[1] : null;
    }
}