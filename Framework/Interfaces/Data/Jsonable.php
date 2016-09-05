<?php
namespace Framework\Interfaces\Data;
/**
 * Interface Jsonable
 * @package Framework\Interfaces\Data
 */
interface Jsonable
{
    /**
     * @param array $array
     * @return mixed
     */
    public static function toJson(array $array = []);

//    public static function arrayToStdClass($argument)
//    {
//        if(is_array($argument))
//            return (object) array_map(__METHOD__, $argument);
//        else
//            return $argument;
//    }
//
//    /**
//     * Convert \StdClass object to array recursively
//     * @param $argument
//     * @return array
//     */
//    public static function stdClassToArray($argument)
//    {
//        if (is_object($argument))
//            $argument = get_object_vars($argument);
//        if (is_array($argument))
//            return array_map(__METHOD__, $argument);
//        else
//            return $argument;
//    }
}