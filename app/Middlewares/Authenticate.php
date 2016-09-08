<?php
namespace App\Middlewares;

use Framework\Auth\Auth;
use Framework\Response\Response;
use Framework\Route\Middleware;
use Framework\Request\Interfaces\Requestable;
use Closure;

class Authenticate extends Middleware
{
    public function __handle(Requestable $request, Closure $callback)
    {
        if(Auth::isAuth()){
            return $request;
        }else{
            //return Response::json(['ass' => 'asd']);
        }
    }
}