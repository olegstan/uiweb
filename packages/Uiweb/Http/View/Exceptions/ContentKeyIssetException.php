<?php
namespace Uiweb\View\Exceptions;

use Exception;

class ContentKeyIssetException extends Exception
{
    public function __construct($key, $message = '', $code = 0, Exception $previous = null)
    {
        parent::__construct('Content with key [' . $key . '] exists', $code, $previous);
    }
}