<?php
namespace Framework\Request;

use Framework\Request\Interfaces\Requestable;
use Framework\Validation\Interfaces\Validateble;
use Framework\Pattern\PatternTraits\NonStaticTrait;
use Framework\Pattern\PatternTraits\SingletonTrait;
use Framework\Validation\ValidationTraits\ValidationTrait;

class Request implements Requestable, Validateble
{
    use SingletonTrait, NonStaticTrait, ValidationTrait;

    /**
     * @var bool
     */
    public static $is_cli;
    /**
     * @var string
     */
    public static $type; // console or http
    /**
     * @var array
     */
    public static $errors = [];

    /**
     *
     * like __construct for singleton
     * @return $this
     */
    public function __handle()
    {
        return $this;
    }

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

    /**
     * @param null $scenario
     * @return array
     */
    public function getRules($scenario = null)
    {
        return [];
    }
}