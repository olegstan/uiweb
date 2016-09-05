<?php
namespace Framework\Interfaces\Data;

use SimpleXMLElement;

/**
 * Interface Xmlable
 * @package Framework\Interfaces\Data
 */
interface Xmlable
{
    /**
     * @param SimpleXMLElement $xml
     * @param array $array
     * @return mixed
     */
    public static function toXml(SimpleXMLElement $xml, array $array = []);
}