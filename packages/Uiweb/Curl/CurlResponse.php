<?php
namespace Uiweb\Curl;
/**
 * Class CurlResponse
 * @package Uiweb\Curl
 */
class CurlResponse
{
    /**
     * @var string
     */
    public $content;
    /**
     * @var array
     */
    public $headers;

    public function __construct($content, array $headers = [])
    {
        $this->content = $content;
        $this->headers = $headers;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->headers['http_code'];
    }
}