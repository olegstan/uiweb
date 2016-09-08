<?php
namespace Framework\FileSystem\Exceptions;

use Exception;

class NotReadebleException extends Exception
{
    public function __construct($path, $message = 'Not readeble path ', $code = 500, Exception $previous = null)
    {
        parent::__construct($message . $path, $code, $previous);
    }
}