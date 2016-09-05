<?php
namespace Framework\Response;

use Framework\Response\Types\HtmlResponse;
use Framework\Response\Types\JsonResponse;
use Framework\Response\Types\XmlResponse;
use Framework\Http\Header;

class HttpResponse extends Response
{
    /**
     * @var Header
     */
    public $header;

    /**
     * @param array $array
     * @param int $status
     * @return Response
     */
    public static function json(array $array = [], $status = 200, array $headers = [])
    {
        return new JsonResponse($array, $status, $headers);
    }

    /**
     * @param $html
     * @param int $status
     * @return HtmlResponse
     */
    public static function html($html = '', $status = 200, array $headers = [])
    {
        return new HtmlResponse($html, $status, $headers);
    }

    /**
     * @param array $array
     * @param int $status
     * @return XmlResponse
     */
    public static function xml(array $array = [], $status = 200, array $headers = [], array $options = ['root' => 'urlset'])
    {
        return new XmlResponse($array, $status, $headers, $options);
    }

    public function getData()
    {
        $this->header->sendHeaders();
        return $this->data;
    }

}
