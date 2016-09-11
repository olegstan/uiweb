<?php
namespace Uiweb\Route;

use Uiweb\Request\Interfaces\Requestable;
use Closure;
use Uiweb\Route\Interfaces\Middlewareble;

abstract class Middleware implements Middlewareble
{
    abstract public function __handle(Requestable $request, Closure $closure);
}