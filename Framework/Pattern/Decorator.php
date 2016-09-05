<?php
namespace Framework\Pattern;

class Decorator
{
    private $obj;

    public function __construct($obj)
    {
        $this->obj = $obj;
    }

    function __call($method_name, $args)
    {
        return call_user_func_array([$this->obj, $method_name], $args);
    }
}