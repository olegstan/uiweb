<?php
namespace Framework\FileSystem\Exceptions;

use Exception;

class NotExecutableExeption extends Exception
{
    public function __construct($path, $message = 'Not executable path ', $code = 500, Exception $previous = null)
    {
        parent::__construct($message . $path, $code, $previous);
    }
}