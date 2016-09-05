<?php
namespace Framework\Pattern\PatternTraits;

trait NonStaticTrait
{
    public function __call($name, $arguments)
    {
        call_user_func([$this, $name], $arguments);
    }

    public static function __callStatic($name, $arguments)
    {
        call_user_func([static::class, $name], $arguments);
    }
}