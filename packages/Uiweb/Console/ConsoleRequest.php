<?php
namespace Uiweb\Request\Types;

use Uiweb\Console\ConsoleParameterCollection;
use Uiweb\Request\Request;

class ConsoleRequest extends Request
{
    /**
     * @var ConsoleParameterCollection
     */
    public static $parameters;

    public function __handle()
    {
        self::$parameters = ConsoleParameterCollection::getInstance();
        return $this;
    }

    public static function getPath()
    {
        return self::$parameters->getPath();
    }

    public static function getCommand()
    {
        return self::$parameters->getCommand();
    }

    /**
     * @param $name
     * @param string $method
     * @return bool
     */
    public static function has($name)
    {
        $array = self::$parameters->getMap();

        if(isset($array[$name])){
            return true;
        }else{
            return false;
        }
    }

    /**
     * @param null $name
     * @return bool|int|null|string
     */
    public static function input($name, $type = null)
    {
        $array = self::$parameters->getMap();

        $val = null;
        if(isset($array[$name]))
            $val = $array[$name];

        switch($type){
            case 'string':
            case 'str':
                return strval($val);
            case 'integer':
            case 'int':
                return intval($val);
            case 'boolean':
            case 'bool':
                return !empty($val);
            default:
                return $val;
        }
    }


}