<?php
namespace Uiweb\Route;
/**
 * Class RouteParameterCollection
 * @package Uiweb\Route
 */
class RouteParameterCollection
{
    public $route;
    /**
     * @var array
     */
    public $routes = [];
    /**
     * @var array
     */
    public $routes_map = [];
    /**
     * @var int
     */
    public $routes_count = 0;
    /**
     * @var bool
     */
    public $is_empty = true;

    public function __construct($route)
    {
        $this->route = $route;
        $this->getRoutes();
    }

    /**
     * @return mixed
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * @return array
     */
    public function getRoutes()
    {
        if(!$this->routes){
            $route_parts = explode('/', $this->getRoute());
            foreach ($route_parts as $key => $part) {
                if(!empty($part)){
                    $this->is_empty = false;
                    $this->routes[$key] = new RouteParameter($part);
                    $this->routes_count++;
                    if(preg_match('#^\{[A-Za-z0-9\_]+\}$#', $part) === 1){
                        $this->routes_map[str_replace(['{', '}'], '', $part)] = $this->routes[$key];
                        $this->routes[$key]->is_dynamic = true;
                    }
                }
            }
        }
        return $this->routes;
    }

    /**
     * @return int
     */
    public function getRoutesCount()
    {
        if(!$this->routes){
            $this->getRoutes();
        }
        return $this->routes_count;
    }

    /**
     * @param $route
     * @param $pattern
     *
     * @return void
     */
    public function setParameterPattern($route, $pattern)
    {
        if(isset($this->routes_map[$route])){
            $this->routes_map[$route]->setPattern($pattern);
        }
    }

    public function compareCount($count)
    {
        if($count === $this->getRoutesCount()){
            return true;
        }else{
            return false;
        }
    }

    public function compareRoute($string, $key)
    {
        /* @var $parameter RouteParameter */
        $parameter = $this->routes[$key];
        if(!$parameter->isDynamic() && $parameter->route === $string){
            return true;
        }else if($parameter->isDynamic() && preg_match('#^' . $parameter->getPattern() . '$#', $string)){
            $parameter->setValue($string);
            return true;
        }else{
            return false;
        }
    }

    public function isEmpty()
    {
        return $this->is_empty;
    }

    /**
     * @param $key
     * @return RouteParameter|null
     */
    public function getRouteByKey($key)
    {
        return isset($this->routes_map[$key]) ? $this->routes_map[$key] : null;
    }
}