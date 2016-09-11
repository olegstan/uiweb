<?php
namespace Uiweb\FileSystem\Exceptions;

use Exception;

class NotWritableException extends Exception
{
    public function __construct($path, $message = 'Not writable path ', $code = 500, Exception $previous = null)
    {
        parent::__construct($message . $path, $code, $previous);
    }
}