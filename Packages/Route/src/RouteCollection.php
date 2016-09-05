<?php
namespace Framework\Route;

use Framework\Request\Types\ConsoleRequest;
use Framework\Request\Types\HttpRequest;
use Framework\Pattern\PatternTraits\SingletonTrait;
use Framework\Request\Request;
use Framework\Route\Exceptions\AliceDublicatException;
use Framework\Route\Exceptions\NotFoundRouteException;

class RouteCollection
{
    use SingletonTrait;
    /**
     * @var array
     */
    private static $routes_alice_map = [];
    /**
     * @var array
     */
    private static $routes = [
        'http' => [
            'GET' => [],
            'POST' => [],
            'PUT' => [],
            'PATCH' => [],
            'TRACE' => [],
            'DELETE' => [],
            'HEAD' => [],
            'OPTIONS' => []
        ],
        'console' => []
    ];

    /**
     * @var Route
     */
    public static $current_route;

    /**
     * @return $this
     */
    public function __handle()
    {
        return $this->registerFile();
    }

    public function registerRoutes()
    {
        require_once(ABS . '/App/routes.php');
    }

    public function registerCommands()
    {
        require_once(ABS . '/App/commands.php');
    }

    public function registerFile()
    {
        switch(Request::getType()){
            case 'console':
                $this->registerCommands();
                break;
            case 'http':
                $this->registerRoutes();
                break;
        }
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

        switch($route->getRequestType()){
            case 'console':
                self::$routes[$route->getRequestType()][] = $route;
                break;
            default:
                self::$routes[$route->getRequestType()][$route->getRequestMethod()][] = $route;
                break;
        }
    }

    /**
     * @param Route $route
     * @param object $model
     */
    public static function registerRest(Route $route)
    {
//        self::register((new Route('single', 'http', 'GET', $route->getRoute() . '/index', 'get_index_' . $route->getAlice(), $route->getController(), 'get' . $route->getMethod(), $route->getMiddleware()))->with(['id' => '[0-9]+']));
        self::register((new Route('single', 'http', 'GET', $route->getRoute() . '/{id}', 'get.' . $route->getAlice(), $route->getController(), 'get' . $route->getMethod(), $route->getMiddleware()))->with(['id' => '[0-9]+']));
        self::register((new Route('single', 'http', 'POST', $route->getRoute() . '/{id}', 'post.' . $route->getAlice(), $route->getController(), 'post' . $route->getMethod(), $route->getMiddleware()))->with(['id' => '[0-9]+']));
        self::register((new Route('single', 'http', 'PUT', $route->getRoute() . '/{id}', 'put.' . $route->getAlice(), $route->getController(), 'put' . $route->getMethod(), $route->getMiddleware()))->with(['id' => '[0-9]+']));
        self::register((new Route('single', 'http', 'DELETE', $route->getRoute() . '/{id}', 'delete.' . $route->getAlice(), $route->getController(), 'delete' . $route->getMethod(), $route->getMiddleware()))->with(['id' => '[0-9]+']));
    }

    public static function registerController(Route $route)
    {

    }

    public function getCurrentRequest()
    {
        switch(Request::getType()){
            case 'console':
                return ConsoleRequest::getInstance();
            case 'http':
                return HttpRequest::getInstance();
        }
    }

    public function getCurrentRoute()
    {
        switch(Request::getType()){
            case 'console':
                return $this->getCurrentConsoleRoute(ConsoleRequest::getInstance());
            case 'http':
                return $this->getCurrentHttpRoute(HttpRequest::getInstance());
        }
    }

    public function setCurrentRoute(Route $route)
    {
        return self::$current_route = $route;
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

    public function getCurrent($method)
    {
        if(!self::$current_route){
            self::$current_route = self::getCurrentRoute();
        }

        switch($method)
        {
            case 'alice':
                return self::$current_route->getAlice();
            case 'middleware':
                return self::$current_route->getMiddleware();
            case 'controller':
                return self::$current_route->getController();
            case 'method':
                return self::$current_route->getMethod();
        }
    }

    public static function getRouteByAlice($alice)
    {
        if(isset(self::$routes_alice_map[$alice])){
            return self::$routes_alice_map[$alice];
        }else{
            throw new NotFoundRouteException($alice);
        }
    }

    public static function addRouteToMap(Route $route)
    {
        if(!isset(self::$routes_alice_map[$route->getAlice()])){
            self::$routes_alice_map[$route->getAlice()] = $route;
        }else{
            throw new AliceDublicatException($route->getAlice());
        }
    }
}