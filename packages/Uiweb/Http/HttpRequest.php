<?php
namespace Uiweb\Request\Types;

use Uiweb\Auth\Cookie;
use Uiweb\Auth\Session;
use Uiweb\Config;
use Uiweb\FileSystem\File;
use Uiweb\FileSystem\TemporaryFile;
use Uiweb\Request\Request;
use Uiweb\Route\RouteCollection;

/**
 * Class HttpRequest
 * @package Uiweb\Http\Requests
 */
class HttpRequest extends Request
{
    /**
     * @var bool
     */
    public static $is_ajax;
    /**
     * @var bool
     */
    public static $is_admin;
    /**
     * @var bool
     */
    public static $is_files;
    /**
     * @var string
     */
    public static $protocol;
    /**
     * @var int
     */
    public static $port;
    /**
     * @var string
     */
    public static $host;
    /**
     * @var string
     */
    public static $root;
    /**
     * @var string
     */
    public static $method;
    /**
     * @var string
     */
    public static $referer;
    /**
     * @var string
     */
    public static $remote_ip;
    /**
     * @var string
     */
    public static $uri;
    /**
     * @var string
     */
    public static $uri_without_get_parameter;
    /**
     * @var string
     */
    public static $body;
    /**
     * @var array
     */
    public static $routes = [];
    /**
     * @var int
     */
    public static $routes_count = 0;
    /**
     * @var array
     */
    private static $session;
    /**
     * @var array
     */
    private static $cookies;
    /**
     * @var array
     */
    private static $headers;

    /**
     * @return $this
     */
    public function __handle()
    {
        self::$session = Session::getInstance();
        self::$cookies = Cookie::getInstance();

        $this->getHeaders();
        $this->getRoutes();
        $this->validate();

        return $this;
    }

    /**
     * @return bool
     */
    public static function isAjax()
    {
        if(!isset(self::$is_ajax)){
            if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'){
                self::$is_ajax = true;
            }else{
                self::$is_ajax = false;
            }
        }
        return self::$is_ajax;
    }

    /**
     * @return array
     */
    public static function getHeaders()
    {
        if(!self::$headers){
            foreach ($_SERVER as $k => $v) {
                if(substr($k, 0, 5) === 'HTTP_'){
                    self::$headers[$k] = $v;
                }
            }
        }
        return self::$headers;
    }

    /**
     * @return array
     */
    public static function getRoutes()
    {
        if(!self::$routes){
            $route_parts = explode('/', self::getUriWithoutGetParameters());
            $pattern = Config::get('request', 'regex');
            foreach ($route_parts as $part) {
                if(preg_match('#' . $pattern . '#', $part) === 1){
                    self::$routes[] = $part;
                    self::$routes_count++;
                }
            }
        }
        return self::$routes;
    }

    public static function getRoutesCount()
    {
        if(!self::$routes){
            self::getRoutes();
        }
        return self::$routes_count;
    }

    /**
     * @param null $name
     * @param null $type
     * @return bool|int|null|string
     */
    public static function get($name = null, $type = null)
    {
        return self::input($name, $type, 'get');
    }

    /**
     * @param null $name
     * @param null $type
     * @return bool|int|null|string
     */
    public static function post($name = null, $type = null)
    {
        return self::input($name, $type, 'post');
    }

    /**
     * @param null $name
     * @param null $type
     * @return bool|int|null|string
     */
    public static function request($name = null, $type = null)
    {
        return self::input($name, $type, 'request');
    }

    /**
     * @param $key
     * @return string
     */
    public static function getHeader($key)
    {
        return self::$headers[$key];
    }

    /**
     * @return string
     */
    public static function getBody()
    {
        if(!self::$body){
            self::$body = file_get_contents("php://input");
        }
        return self::$body;
    }

    /**
     * @return int
     */
    public static function getPort()
    {
        if(!self::$port){
            self::$port =& $_SERVER['SERVER_PORT'];
        }
        return self::$port;
    }

    /**
     * @return string
     */
    public static function getProtocol()
    {
        if(!self::$protocol){
            self::$protocol = self::getPort() == 443 ? 'https' : 'http';
        }
        return self::$protocol;
    }

    /**
     * @return string
     */
    public static function getHost()
    {
        if(!self::$host){
            self::$host =& $_SERVER['HTTP_HOST'];
        }
        return self::$host;
    }

    /**
     * @return string
     */
    public static function getRoot()
    {
        if(!self::$root){
            self::$root = self::getProtocol() . '://' . self::getHost();
        }
        return self::$root;
    }

    /**
     * @return string
     */
    public static function getMethod()
    {
        if(!self::$method){
            self::$method =& $_SERVER['REQUEST_METHOD'];
        }
        return self::$method;
    }

    /**
     * @return string
     */
    public static function getReferer()
    {
        if(!self::$referer){
            self::$referer =& $_SERVER['HTTP_REFERER'];
        }
        return self::$referer;
    }

    /**
     * @return string
     */
    public static function getRemoteIp()
    {
        if(!self::$remote_ip){
            self::$remote_ip =& $_SERVER['REMOTE_ADDR']?:($_SERVER['HTTP_X_FORWARDED_FOR']?:$_SERVER['HTTP_CLIENT_IP']);
        }
        return self::$remote_ip;
    }

    /**
     * @return string
     */
    public static function getUri()
    {
        if(!self::$uri){
            self::$uri =& $_SERVER['REQUEST_URI'];
        }
        return self::$uri;
    }

    /**
     * @return string
     */
    public static function getUriWithoutGetParameters()
    {
        if(!self::$uri_without_get_parameter){
            self::$uri_without_get_parameter = explode('?', self::getUri())[0];
        }
        return self::$uri_without_get_parameter;
    }

    public static function getRoute($key)
    {
        /* @var $parameter \Uiweb\Route\RouteParameter */
        $parameter = RouteCollection::getInstance()->getCurrentRoute()->getRouteParameterCollection()->getRouteByKey($key);
        return $parameter ? $parameter->getValue() : null;
    }

    /**
     * @param string $method
     * @return mixed
     */
    public static function getMethodArray($method = 'request')
    {
        switch($method){
            case 'get':
                return $_GET;
            case 'post':
                return $_POST;
            default:
                return $_REQUEST;
        }
    }



    /**
     * @param $name
     * @param string $method
     * @return bool
     */
    public static function has($name, $method = 'request')
    {
        $array = self::getMethodArray($method);

        if(isset($array[$name])){
            return true;
        }else{
            return false;
        }
    }

    /**
     * @param null $name
     * @param null $type
     * @param string $method
     * @return bool|int|null|string
     */
    public static function input($name = null, $type = null, $method = 'request')
    {
        $array = self::getMethodArray($method);

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

    /**
     * @param $name
     * @return null|TemporaryFile
     */
    public static function getFile($name)
    {
        if (empty($_FILES[$name]))
            return null;

        return new TemporaryFile($_FILES[$name]);
    }

    /**
     * @return TemporaryFile[]
     */
    public static function getFiles()
    {
        if (empty($_FILES))
            return [];

        $files = [];
        foreach($_FILES as $file){
            $files[] = new TemporaryFile($file);
        }
        return $files;
    }
}