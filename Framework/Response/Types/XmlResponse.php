<?php
namespace Framework\Response\Types;

use Framework\Response\HttpResponse;
use Framework\Http\Header;
use SimpleXMLElement;

class XmlResponse extends HttpResponse
{
    /**
     * @var string
     */
    public $type = 'xml';
    /**
     * @var Header
     */
    public $header;
    /**
     * @var int
     */
    public $status = 200;

    /**
     * @param array $array
     * @param int $status
     * @param array $headers
     */
    public function __construct(array $array, $status = 200, array $headers = [], array $options = ['root' => 'urlset'])
    {
        $this->header = new Header();
        $this->header->{'xml'}($status);
        $this->header->setHeaders($headers);

        $this->data = self::toXml(new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><' . $options['root'] . '/>'), $array)->asXML();
        $this->status = $status;
    }

    /**
     * @param SimpleXMLElement $xml
     * @param array $array
     * @return SimpleXMLElement
     */
    public static function toXml(SimpleXMLElement $xml, array $array = [])
    {
        if($array) {
            foreach ($array as $key => $value) {
                if (is_array($value) || is_object($value) && $value = get_object_vars($value)) {
                    if (!is_numeric($key)) {
                        $subnode = $xml->addChild($key);
                        self::toXml($subnode, $value);
                    } else {
                        self::toXml($xml, $value);
                    }
                } else {
                    $xml->addChild($key, $value);
                }
            }
        }
        return $xml;
    }
}