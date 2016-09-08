<?php
namespace Framework\View;

use Framework\Route\Route;
use Framework\Route\RouteCollection;
use Framework\Route\RouteParameterCollection;
use Framework\Route\RouteParameter;

/**
 * Class Url
 * @package Framework\View
 */
class Url
{

    /**
     * @var RouteCollection
     */
    public $route;

    /**
     *
     */
    public function __construct()
    {
        $this->route = RouteCollection::getInstance();
    }

    /**
     * @param $alice
     * @param array $routes
     * @param array $parameteters
     * @return string
     * @throws \Framework\Route\Exceptions\NotFoundRouteException
     */
    public function route($alice, array $routes = [], array $parameteters = [])
    {
        return $this->generate($this->route->getRouteByAlice($alice), $routes, $parameteters);
    }

//    public function asset($path)
//    {
//        return
//    }

    /**
     * @param Route $route
     * @param array $routes
     * @param array $parameteters
     * @return string
     */
    public function generate(Route $route, array $routes = [], array $parameteters = [])
    {
        $url = '';
        /**
         * @var RouteParameterCollection $collection
         */
        $collection = $route->getRouteParameterCollection();
        //TODO param
        if($collection->getRoutes()){
            foreach ($collection->getRoutes() as $parameter) {
                /**
                 * @var RouteParameter $parameter
                 */
                if($parameter->isDynamic()){
                    $key = str_replace(['{', '}'], '', $parameter->getRoute());
                    $url .= '/' . (isset($routes[$key]) ? $routes[$key]: $parameter->getRoute());
                }else{
                    $url .= '/' . $parameter->getRoute();
                }
            }
        }else{
            $url .= '/';
        }

        if($parameteters){
            $count = count($parameteters);
            $k = 1;
            foreach ($parameteters as $key => $value) {
                $separator = ($k++ === 1) ? '?' : '&';
                $url .= $separator . $key . '=' . $value;
            }
        }
        return $url;
    }
}