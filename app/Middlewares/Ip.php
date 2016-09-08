<?php
namespace App\Middlewares;

use Framework\Request\Interfaces\Requestable;
use Framework\Route\Middleware;
use Closure;

class Ip extends Middleware
{
    public function __handle(Requestable $request, Closure $closure)
    {

    }
}