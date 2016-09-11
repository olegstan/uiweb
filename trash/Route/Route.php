<?php
namespace Uiweb\Route;

use Closure;
use Uiweb\Route\Interfaces\Routeble;
/**
 * Class Route
 * @package Uiweb\Route
 */
class Route implements Routeble
{
    /**
     * @var string
     */
    public $type;
    /**
     * @var string
     */
    public $route;
    /**
     * @var RouteParameterCollection
     */
    public $route_parameter_collection;
    /**
     * @var string
     */
    public $request_type;
    /**
     * @var string
     */
    public $request_method;
    /**
     * @var string
     */
    public $alice;
    /**
     * @var string
     */
    public $controller;
    /**
     * @var string
     */
    public $method;
    /**
     * @var array
     */
    public $middleware;

    /**
     * @param $type
     * @param $request_type
     * @param $request_method
     * @param $route
     * @param $alice
     * @param $controller
     * @param $method
     * @param array $middleware
     * TODO  Filter, Params
     */
    public function __construct($type, $request_type, $request_method, $route, $alice, $controller, $method, array $middleware = [])
    {
        switch($type){
            case 'single':
                $this->type = 'single';
                $this->request_type = $request_type;
                $this->request_method = $request_method;
                $this->route = $route;
                $this->route_parameter_collection = new RouteParameterCollection($route);
                $this->alice = $alice;
                $this->controller = $controller;
                $this->method = $method;
                $this->middleware = $middleware;
                break;
            case 'rest':
                $this->type = 'rest';
                $this->route = $route;
                $this->alice = $alice;
                $this->controller = $controller;
                $this->method = $method;
                $this->middleware = $middleware;
                break;
            case 'controller':

                break;
        }
    }

    public static function rest($route, $alice, $controller, $method, array $middleware = [])
    {
        return RouteCollection::register(new self('rest', 'http', null, RouteGroup::getRoute($route), $alice, $controller, $method, RouteGroup::getMiddleware($middleware)));
    }

    /**
     * @param $prefix
     * @param array $middleware
     * @param Closure $callback
     */
    public static function group($prefix, array $middleware = [], Closure $callback)
    {
        $key1 = RouteGroup::setRoute($prefix);
        $key2 = RouteGroup::setMiddleware($middleware);

        $callback();

        RouteGroup::unsetRoute($key1);
        RouteGroup::unsetMiddleware($key2);
    }

    /**
     * @param $route
     * @param $alice
     * @param $controller
     * @param $method
     * @param array $middleware
     * @return Route
     */
    public static function get($route, $alice, $controller, $method, array $middleware = [])
    {
        return RouteCollection::register(new self('single', 'http', 'GET', RouteGroup::getRoute($route), $alice, $controller, $method, RouteGroup::getMiddleware($middleware)));
    }

    /**
     * @param $route
     * @param $alice
     * @param $controller
     * @param $method
     * @param array $middleware
     * @return Route
     */
    public static function put($route, $alice, $controller, $method, array $middleware = [])
    {
        return RouteCollection::register(new self('single', 'http', 'PUT', RouteGroup::getRoute($route), $alice, $controller, $method, RouteGroup::getMiddleware($middleware)));
    }

    /**
     * @param $route
     * @param $alice
     * @param $controller
     * @param $method
     * @param array $middleware
     * @return Route
     */
    public static function post($route, $alice, $controller, $method, array $middleware = [])
    {
        return RouteCollection::register(new self('single', 'http', 'POST', RouteGroup::getRoute($route), $alice, $controller, $method, RouteGroup::getMiddleware($middleware)));
    }

    /**
     * @param $route
     * @param $alice
     * @param $controller
     * @param $method
     * @param array $middleware
     * @return Route
     */
    public static function patch($route, $alice, $controller, $method, array $middleware = [])
    {
        return RouteCollection::register(new self('single', 'http', 'PATCH', RouteGroup::getRoute($route), $alice, $controller, $method, RouteGroup::getMiddleware($middleware)));
    }

    /**
     * @param $route
     * @param $alice
     * @param $controller
     * @param $method
     * @param array $middleware
     * @return Route
     */
    public static function trace($route, $alice, $controller, $method, array $middleware = [])
    {
        return RouteCollection::register(new self('single', 'http', 'TRACE', RouteGroup::getRoute($route), $alice, $controller, $method, RouteGroup::getMiddleware($middleware)));
    }

    /**
     * @param $route
     * @param $alice
     * @param $controller
     * @param $method
     * @param array $middleware
     * @return Route
     */
    public static function delete($route, $alice, $controller, $method, array $middleware = [])
    {
        return RouteCollection::register(new self('single', 'http', 'DELETE', RouteGroup::getRoute($route), $alice, $controller, $method, RouteGroup::getMiddleware($middleware)));
    }

    /**
     * @param $route
     * @param $alice
     * @param $controller
     * @param $method
     * @param array $middleware
     * @return Route
     */
    public static function head($route, $alice, $controller, $method, array $middleware = [])
    {
        return RouteCollection::register(new self('single', 'http', 'HEAD', RouteGroup::getRoute($route), $alice, $controller, $method, RouteGroup::getMiddleware($middleware)));
    }

    /**
     * @param $route
     * @param $alice
     * @param $controller
     * @param $method
     * @param array $middleware
     * @return Route
     */
    public static function options($route, $alice, $controller, $method, array $middleware = [])
    {
        return RouteCollection::register(new self('single', 'http', 'OPTIONS', RouteGroup::getRoute($route), $alice, $controller, $method, RouteGroup::getMiddleware($middleware)));
    }

    /**
     * @param $route
     * @param $alice
     * @param $controller
     * @param $method
     * @param array $middleware
     */
    public static function console($route, $alice, $controller, $method, array $middleware = [])
    {
        return RouteCollection::register(new self('single', 'console', null, $route, $alice, $controller, $method, $middleware));
    }

    /**
     * @param array $params
     * @return $this
     */
    public function with(array $params)
    {
        foreach ($params as $route => $pattern) {
            $this->getRouteParameterCollection()->setParameterPattern($route, $pattern);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getRequestMethod()
    {
        return $this->request_method;
    }

    /**
     * @return string
     */
    public function getRequestType()
    {
        return $this->request_type;
    }

    /**
     * @return string
     */
    public function getRoute()
    {
        return $this->route;
    }
    /**
     * @return RouteParameterCollection
     */
    public function getRouteParameterCollection()
    {
        return $this->route_parameter_collection;
    }

    /**
     * @return string
     */
    public function getAlice()
    {
        return $this->alice;
    }

    /**
     * @return string
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return array
     */
    public function getMiddleware()
    {
        return $this->middleware;
    }

}