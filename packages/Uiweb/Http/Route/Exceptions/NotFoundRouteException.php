<?php
namespace Uiweb\Http\Route\Exceptions;

use Exception;

class NotFoundRouteException extends Exception
{
    public function __construct($route, $message = 'Not found route ', $code = 404, Exception $previous = null)
    {
        parent::__construct($message . '[' . $route . ']', $code, $previous);
    }
}