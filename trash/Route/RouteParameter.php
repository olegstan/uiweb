<?php
namespace Uiweb\Route;
/**
 * Class RouteParameter
 * @package Uiweb\Route
 */
class RouteParameter
{
    /**
     * @var string
     */
    public $route;
    /**
     * @var string
     */
    public $pattern = '[A-Za-z0-9\_\-]+';
    /**
     * @var bool
     */
    public $is_dynamic = false;
    /**
     * @var string
     */
    public $value;
    /**
     * @param string $route
     */
    public function __construct($route)
    {
        $this->route = $route;
    }
    /**
     * @return string
     */
    public function getRoute()
    {
        return $this->route;
    }
    /**
     * @param $route
     */
    public function setRoute($route)
    {
        $this->route = $route;
    }
    /**
     * @return string
     */
    public function getPattern()
    {
        return $this->pattern;
    }
    /**
     * @param $pattern
     */
    public function setPattern($pattern)
    {
        $this->pattern = $pattern;
    }
    /**
     * @return bool
     */
    public function isDynamic()
    {
        return $this->is_dynamic;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }
}