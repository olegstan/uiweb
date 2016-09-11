<?php
namespace Uiweb\Route\Exceptions;

use Exception;

class NotFoundMethodException extends Exception
{
    public function __construct($method, $message = 'Not found method ', $code = 500, Exception $previous = null)
    {
        parent::__construct($message . $method, $code, $previous);
    }
}