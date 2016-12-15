<?php
namespace Uiweb\Http\Route\Exceptions;

use Exception;

class AliceDublicatException extends Exception
{
    public function __construct($alice, $message = 'Alice exists ', $code = 500, Exception $previous = null)
    {
        parent::__construct($message . $alice, $code, $previous);
    }
}