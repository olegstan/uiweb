<?php
namespace Uiweb\Response;

use Uiweb\Console\Console;

class ConsoleResponse extends Response
{
    /**
     * @var bool
     */
    public $is_cli = true;
    /**
     * @var string
     */
    public $foreground_color;
    /**
     * @var string
     */
    public $background_color;

    /**
     * @param $data
     * @param string $foreground_color
     * @param string $background_color
     */
    public function __construct($data, $foreground_color = 'green', $background_color = 'black')
    {
        $this->data = $data;
        $this->foreground_color = $foreground_color;
        $this->background_color = $background_color;
    }

    /**
     * @return string
     */
    public function getData()
    {
        return Console::getString($this->data, $this->foreground_color, $this->background_color);
    }
}