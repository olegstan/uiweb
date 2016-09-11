<?php
namespace Uiweb;

class Request
{
    /**
     * @var bool
     */
    public static $is_cli;
    /**
     * console or http
     * @var string
     */
    public static $type;
    /**
     * @return string
     */
    public static function getType()
    {
        return Request::isCli() ? 'console' : 'http';
    }
    /**
     * @return bool
     */
    public static function isCli()
    {
        if(!isset(self::$is_cli)){
            if(php_sapi_name() == 'cli'){
                self::$is_cli = true;
            }else{
                self::$is_cli = false;
            }
        }
        return self::$is_cli;
    }
}