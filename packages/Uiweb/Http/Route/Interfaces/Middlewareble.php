<?php
namespace Uiweb\Http\Route\Interfaces;

use Uiweb\Request\Interfaces\Requestable;
use Closure;

interface Middlewareble
{
    public function __handle(Requestable $request, Closure $closure);
}