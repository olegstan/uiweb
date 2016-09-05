<?php
namespace Framework\Interfaces\Data;
/**
 * Interface Arrayable
 * @package Framework\Interfaces\Data
 */
interface Arrayable
{
    /**
     * @param array $array
     * @return mixed
     */
    public static function toArray(array $array = []);
}