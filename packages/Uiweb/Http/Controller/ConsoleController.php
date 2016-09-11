<?php
namespace Uiweb\Controller;

use Uiweb\Console\Console;
use Uiweb\Response\ConsoleResponse;

abstract class ConsoleController
{
    /**
     * @param string $text
     */
    public function info($text = '')
    {
        echo Console::getString($text, 'green');
    }

    /**
     * @param string $text
     */
    public function error($text = '')
    {
        echo Console::getString($text, 'red');
    }

    /**
     * @param string $text
     */
    public function text($text = '')
    {
        echo Console::getString($text, 'white');
    }

    /**
     * @param $text
     * @return ConsoleResponse
     */
    public static function responseError($text)
    {
        return ConsoleResponse::console($text);
    }
}