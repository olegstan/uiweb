<?php
namespace Uiweb\Http\Route;
/**
 * Class RouteGroup
 * @package Uiweb\Http\Route
 */
class RouteGroup
{
    /**
     * @var array
     */
    public static $prefixes = [];
    /**
     * @var array
     */
    public static $middlewares = [];

    /**
     * @return array
     */
    public static function makeMiddleware()
    {
        $middlewares = [];
        foreach (self::$middlewares as $middleware) {
            $middlewares += $middleware;
        }
        return $middlewares;
    }

    /**
     * @param array $middlewares
     * @return array
     */
    public static function getMiddleware(array $middlewares = [])
    {
        return array_unique(array_merge($middlewares, self::makeMiddleware()));
    }

    /**
     * @param array $middlewares
     */
    public static function setMiddleware(array $middlewares = [])
    {
        self::$middlewares[] = $middlewares;
        end(self::$middlewares);
        return key(self::$middlewares);
    }

    /**
     *
     */
    public static function unsetMiddleware($key)
    {
        unset(self::$middlewares[$key]);
    }

    /**
     * @param string $route
     * @return string
     */
    public static function makeRoute($route = '')
    {
        foreach (self::$prefixes as $prefix) {
            $route .= $prefix;
        }
        return $route;
    }

    /**
     * @param string $route
     * @return string
     */
    public static function getRoute($route = '')
    {
        return self::makeRoute() . $route;
    }

    /**
     * @param string $route
     */
    public static function setRoute($route = '')
    {
        self::$prefixes[] = $route;
        end(self::$prefixes);
        return key(self::$prefixes);
    }

    /**
     *
     */
    public static function unsetRoute($key)
    {
        unset(self::$prefixes[$key]);
    }


}