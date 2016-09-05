<?php
namespace Framework\Route;

use Framework\Config;
use Framework\Request\Request;
use Framework\Request\Types\HttpRequest;
use Framework\Response\Response;
use Framework\Route\Exceptions\NotFoundMethodException;
use Framework\Route\RouteCollection;
use ReflectionClass;
use ReflectionMethod;

/**
 * Class RouteReflection
 * @package Framework\Route
 */
class RouteReflection extends ReflectionClass
{
    /**
     * @var RouteCollection
     */
    public $route;

    /**
     * @var object
     */
    public $controller;

    /**
     * @var ReflectionMethod
     */
    public $method;

    /**
     * @var array
     */
    public $parameters = [];
    /**
     * @var array
     */
    public $middlewares = [];


    /**
     * @param \Framework\Route\RouteCollection $route
     */
    public function __construct(RouteCollection $route)
    {
        $this->route = $route;
        parent::__construct($this->route->getCurrentController());
    }

    /**
     * @return mixed
     */
    public function callController()
    {
        $this->controller = $this->getRouteController();
        $this->method = $this->getRouteMethod();
        $this->parameters = $this->getRouteParameters();

        switch(Request::getType()){
            case 'console':
                $this->middlewares = array_unique(array_merge($this->getRouteMiddleware(), Config::get('middlewares', 'console')));
                break;
            case 'http':
                $this->middlewares = array_unique(array_merge($this->getRouteMiddleware(), Config::get('middlewares', 'http')));
                break;
        }

        if($this->middlewares){
            foreach ($this->middlewares as $middleware) {
                /**
                 * @var Middleware $middleware
                 */
                $result = (new $middleware)->__handle($this->route->getCurrentRequest(), function(){});
                if($result instanceof Request){

                }else if($result instanceof Response){
                    return $result;
                }
            }
        }

        return $this->method->invokeArgs($this->controller, $this->parameters);
    }

    /**
     * @return object
     */
    public function getRouteController()
    {
        if($method = $this->getConstructor()){
            $construct_parameters = $method->getParameters();
            $construct_params = [];
            foreach ($construct_parameters as $key => $parameter) {
                $param_class = $parameter->getClass();
                if($param_class){
                    $param_class = $parameter->getClass()->getName();
                    $construct_params[$parameter->getName()] = new $param_class;
                }
            }
            return $this->newInstanceArgs($construct_params);
        }else{
            return $this->newInstance();
        }
    }

    /**
     * @return ReflectionMethod
     */
    public function getRouteMethod()
    {
        return $this->getMethod($this->route->getCurrentMethod());
//        if(){
//
//        }
//
//        try{
//            return $this->getMethod($this->route->getCurrentMethod());
//        }catch (NotFoundMethodException $e){
//            echo '<pre>';
//            var_dump(111);
//            var_dump($this->route->getCurrentMethod());
//            var_dump(2);
//            echo '</pre>';
//            die();
//        }

    }

    /**
     * @return mixed
     */
    public function getRouteParameters()
    {
        $method_parameters = $this->method->getParameters();

        $method_params = [];
        foreach ($method_parameters as $key => $parameter) {
            if($parameter->getClass()){
                $param_class = $parameter->getClass()->getName();
                $method_params[$parameter->getName()] = new $param_class;
            }else{
                switch(Request::getType()){
                    case 'console':

                        break;
                    case 'http':
                        $method_params[$parameter->getName()] = HttpRequest::getRoute($parameter->getName());
                        break;
                }
            }
        }

        return $method_params;
    }

    public function getRouteMiddleware()
    {
        return $this->route->getCurrentMiddleware();
    }
}