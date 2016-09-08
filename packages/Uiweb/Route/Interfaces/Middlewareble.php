<?php
namespace Framework\Route\Interfaces;

use Framework\Request\Interfaces\Requestable;
use Closure;

interface Middlewareble
{
    public function __handle(Requestable $request, Closure $closure);
}