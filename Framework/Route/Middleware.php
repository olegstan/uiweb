<?php
namespace Framework\Route;

use Framework\Request\Interfaces\Requestable;
use Closure;
use Framework\Route\Interfaces\Middlewareble;

abstract class Middleware implements Middlewareble
{
    abstract public function __handle(Requestable $request, Closure $closure);
}