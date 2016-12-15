<?php
namespace Uiweb\Http\Route;

use Closure;
use Uiweb\Http\Route\Interfaces\Middlewareble;

abstract class Middleware implements Middlewareble
{
    abstract public function boot(Requestable $request, Closure $closure);
}