<?php
namespace Uiweb\FileSystem\Exceptions;

use Exception;

class NotExistsExeption extends Exception
{
    public function __construct($path, $message = 'Not exists path ', $code = 500, Exception $previous = null)
    {
        parent::__construct($message . $path, $code, $previous);
    }
}