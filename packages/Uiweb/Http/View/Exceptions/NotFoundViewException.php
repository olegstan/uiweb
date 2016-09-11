<?php
namespace Uiweb\View\Exceptions;

use Exception;

class NotFoundViewException extends Exception
{
    public function __construct($path, $message = 'Not found file ', $code = 0, Exception $previous = null)
    {
        parent::__construct($message . $path, $code, $previous);
    }
}