<?php
namespace Uiweb\Response;

use Uiweb\Response\Types\HtmlResponse;
use Uiweb\Response\Types\JsonResponse;
use Uiweb\Response\Types\XmlResponse;

/**
 * Class Response
 * @package Uiweb\Http
 */
class Response
{
    /**
     * @var bool
     */
    public $is_cli;
    /**
     * @var string
     */
    public $type;
    /**
     * @var string
     */
    public $data = '';

    /**
     * @param array $array
     * @param int $status
     * @return JsonResponse
     */
    public static function json(array $array = [], $status = 200, array $headers = [])
    {
        return HttpResponse::json($array, $status, $headers);
    }

    /**
     * @param $html
     * @param int $status
     * @return HtmlResponse
     */
    public static function html($html = '', $status = 200, array $headers = [])
    {
        return HttpResponse::html($html, $status, $headers);
    }

    /**
     * @param array $array
     * @param int $status
     * @return XmlResponse
     */
    public static function xml(array $array = [], $status = 200, array $headers = [], array $options = ['root' => 'urlset'])
    {
        return HttpResponse::xml($array, $status, $headers, $options);
    }
    /**
     * @param string $text
     * @param int $status
     * @return ConsoleResponse
     */
    public static function console($text = '')
    {
        return new ConsoleResponse($text);
    }

    /**
     * @param array $array
     */
    public static function toArray(array $array = [])
    {

    }

    /**
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
}
