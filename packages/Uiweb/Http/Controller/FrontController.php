<?php
namespace Uiweb\Controller;

use Uiweb\Core;
use Uiweb\Request\Request;
use Uiweb\Response\ConsoleResponse;
use Uiweb\Response\Response;
use Uiweb\Route\Exceptions\NotFoundRouteException;
use Uiweb\Route\RouteCollection;
use Uiweb\Route\RouteReflection;

class FrontController
{
    /**
     * @var Request
     */

    protected $request;

    /**
     * @var RouteCollection
     */

    protected $route;

    /**
     * @param Request $request
     * @param RouteCollection $route
     */
    public function __construct(Request $request, RouteCollection $route)
    {
        $this->request = $request;
        $this->route = $route;
    }

    /**
     * @return Response
     */
    public function init()
    {
        try{
            return $this->response((new RouteReflection($this->route))->callController());
        }catch (NotFoundRouteException $e){
//            echo $e->getMessage() . '<br>';
//            echo $e->getCode() . '<br>';
//            echo $e->getFile() . '<br>';
//            echo $e->getLine() . '<br>';
            switch(Request::getType()){
                case 'console':
                    return $this->response(new ConsoleResponse('not found command'));
                    break;
                case 'http':
                    return $this->response(HttpController::{'404'}());
                    break;
            }
        }catch (\Exception $e){

//
        }
    }

    /**
     * @param Response $response
     * @return int
     */
    public function response(Response $response)
    {
        return print($response->getData());
    }
}