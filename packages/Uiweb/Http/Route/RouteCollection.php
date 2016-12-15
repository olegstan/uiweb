<?php
namespace Uiweb\Http\Route;

use Uiweb\Request\Types\ConsoleRequest;
use Uiweb\Request\Types\HttpRequest;
use Uiweb\Pattern\PatternTraits\SingletonTrait;
use Uiweb\Request\Request;
use Uiweb\Http\Route\Exceptions\AliceDublicatException;
use Uiweb\Http\Route\Exceptions\NotFoundRouteException;

class RouteCollection
{
    /**
     * @var array
     */
    private static $routesAliceMap = [];
    /**
     * @var array
     */
    private static $routes = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'PATCH' => [],
        'TRACE' => [],
        'DELETE' => [],
        'HEAD' => [],
        'OPTIONS' => []
    ];

    /**
     * @var Route
     */
    public static $currentRoute;


//    public function registerRoutes()
//    {
//        require_once(ABS . '/App/routes.php');
//    }
//
//    public function registerCommands()
//    {
//        require_once(ABS . '/App/commands.php');
//    }

    /**
     * @return $this
     */
    public function registerFile()
    {
//        switch(Request::getType()){
//            case 'console':
//                $this->registerCommands();
//                break;
//            case 'http':
//                $this->registerRoutes();
//                break;
//        }
        return $this;
    }

    /**
     * @param Route $route
     * @return Route
     */
    public static function register(Route $route)
    {
        switch($route->type){
            case 'single':
                self::registerSingle($route);
                break;
            case 'rest':
                self::registerRest($route);
                break;
            case 'controller':
                self::registerController($route);
                break;
        }
        return $route;
    }

    /**
     * @param Route $route
     * @return void
     */
    public static function registerSingle(Route $route)
    {
        self::addRouteToMap($route);
        self::$routes[$route->getRequestType()][$route->getRequestMethod()][] = $route;
    }

    /**
     * @param Route $route
     */
    public static function registerRest(Route $route)
    {
//        self::register((new Route('single', 'http', 'GET', $route->getRoute() . '/index', 'get_index_' . $route->getAlice(), $route->getController(), 'get' . $route->getMethod(), $route->getMiddleware()))->with(['id' => '[0-9]+']));
        self::register((new Route('single', 'http', 'GET', $route->getRoute() . '/{id}', 'get.' . $route->getAlice(), $route->getController(), 'get' . $route->getMethod(), $route->getMiddleware()))->with(['id' => '[0-9]+']));
        self::register((new Route('single', 'http', 'POST', $route->getRoute() . '/{id}', 'post.' . $route->getAlice(), $route->getController(), 'post' . $route->getMethod(), $route->getMiddleware()))->with(['id' => '[0-9]+']));
        self::register((new Route('single', 'http', 'PUT', $route->getRoute() . '/{id}', 'put.' . $route->getAlice(), $route->getController(), 'put' . $route->getMethod(), $route->getMiddleware()))->with(['id' => '[0-9]+']));
        self::register((new Route('single', 'http', 'DELETE', $route->getRoute() . '/{id}', 'delete.' . $route->getAlice(), $route->getController(), 'delete' . $route->getMethod(), $route->getMiddleware()))->with(['id' => '[0-9]+']));
    }

    /**
     * @param Route $route
     */
    public static function registerController(Route $route)
    {

    }

    /**
     * @return mixed
     */
    public function getCurrentRequest()
    {
        return HttpRequest::getInstance();
    }

    /**
     * @return Route
     * @throws NotFoundRouteException
     */
    public function getCurrentRoute()
    {
        switch(Request::getType()){
            case 'console':
                return $this->getCurrentConsoleRoute(ConsoleRequest::getInstance());
            case 'http':
                return $this->getCurrentHttpRoute(HttpRequest::getInstance());
        }
    }

    /**
     * @param Route $route
     * @return Route
     */
    public function setCurrentRoute(Route $route)
    {
        return self::$currentRoute = $route;
    }

    /**
     * TODO redirect to add slash
     * @param HttpRequest $request
     */
    public function getCurrentHttpRoute(HttpRequest $request)
    {
        if($routes = self::$routes['http'][$request->getMethod()]){
            $request_routes = $request->getRoutes();
            $request_routes_count = $request->getRoutesCount();

            if($request_routes){
                if($routes){
                    foreach ($routes as $k1 => $route) {
                        /* @var $route Route */
                        if (!$route->getRouteParameterCollection()->compareCount($request_routes_count)) {
                            unset($routes[$k1]);
                        }
                    }
                }else{
                    throw new NotFoundRouteException($request->getUri());
                }

                foreach ($request_routes as $k1 => $request_route) {


                    foreach ($routes as $k2 => $route) {
                        /* @var $route Route */
                        if($route->getRouteParameterCollection()->compareRoute($request_route, $k1)){
                            if (($request_routes_count - 1) === $k1) {
                                return $this->setCurrentRoute($route);
                            }
                        }else{
                            unset($routes[$k2]);
                        }
                    }
                }
                if(!$routes){
                    throw new NotFoundRouteException($request->getUri());
                }
            }else{
                foreach ($routes as $key => $route) {
                    /* @var $route Route */
                    if (!$route->getRouteParameterCollection()->isEmpty()) {
                        unset($routes[$key]);
                    }else{
                        return $this->setCurrentRoute($route);
                    }
                }
                if(!$routes){
                    throw new NotFoundRouteException($request->getUri());
                }
            }
        }else{
            throw new NotFoundRouteException($request->getUri());
        }
    }

    /**
     * @param ConsoleRequest $request
     * @return Route
     * @throws NotFoundRouteException
     */
    public function getCurrentConsoleRoute(ConsoleRequest $request)
    {
        if($routes = self::$routes['console']){
            $command = $request->getCommand();
            /* @var $route Route */
            foreach ($routes as $key => $route) {
                if($command === $route->getRouteParameterCollection()->getRoute()){
                    return $route;
                }else{
                    unset($routes[$key]);
                }
            }
            if(!$routes){
                throw new NotFoundRouteException();
            }
        }else{
            throw new NotFoundRouteException();
        }
    }

    /**
     * @return mixed
     */
    public function getCurrentController()
    {
        return self::getCurrent('controller');
    }

    /**
     * @return mixed
     */
    public function getCurrentMethod()
    {
        return self::getCurrent('method');
    }

    /**
     * @return mixed
     */
    public function getCurrentMiddleware()
    {
        return self::getCurrent('middleware');
    }

    /**
     * @return mixed
     */
    public function getCurrentAlice()
    {
        return self::getCurrent('alice');
    }

    /**
     * @param $method
     * @return array|string
     */
    public function getCurrent($method)
    {
        if(!self::$currentRoute){
            self::$currentRoute = self::getCurrentRoute();
        }

        switch($method)
        {
            case 'alice':
                return self::$currentRoute->getAlice();
            case 'middleware':
                return self::$currentRoute->getMiddleware();
            case 'controller':
                return self::$currentRoute->getController();
            case 'method':
                return self::$currentRoute->getMethod();
        }
    }

    /**
     * @param $alice
     * @return mixed
     * @throws NotFoundRouteException
     */
    public static function getRouteByAlice($alice)
    {
        if(isset(self::$routesAliceMap[$alice])){
            return self::$routesAliceMap[$alice];
        }else{
            throw new NotFoundRouteException($alice);
        }
    }

    /**
     * @param Route $route
     * @throws AliceDublicatException
     */
    public static function addRouteToMap(Route $route)
    {
        if(!isset(self::$routesAliceMap[$route->getAlice()])){
            self::$routesAliceMap[$route->getAlice()] = $route;
        }else{
            throw new AliceDublicatException($route->getAlice());
        }
    }
}