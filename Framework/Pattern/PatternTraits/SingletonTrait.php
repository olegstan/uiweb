<?php

namespace Framework\Pattern\PatternTraits;

trait SingletonTrait
{
    /**
     * @var array
     */
    public static $instances_pool = [];

    /**
     * @return static
     */
    public static function getInstance()
    {
        if (!isset(static::$instances_pool[static::class])) {
            static::$instances_pool[static::class] = (new static())->__handle();
        }

        return static::$instances_pool[static::class];
    }

    /**
     *
     */
    private function __clone(){}

    /**
     *
     */
    private function __wakeup(){}

    /**
     * @return mixed required for
     */
    abstract function __handle();
}