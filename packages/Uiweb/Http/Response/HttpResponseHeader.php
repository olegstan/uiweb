<?php
namespace Framework\Http;

use Framework\FileSystem\Image;
use Framework\Pattern\PatternTraits\NonStaticTrait;
use Framework\Pattern\PatternTraits\SingletonTrait;

class Header
{
    use SingletonTrait, NonStaticTrait;
    /**
     * @var array
     */
    public static $statuses = [
        100 => 'Continue',
        101 => 'Switching Protocols',

        // Success 2xx
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',

        // Redirection 3xx
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',  // 1.1
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        // 306 is deprecated but reserved
        307 => 'Temporary Redirect',

        // Client Error 4xx
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',

        // Server Error 5xx
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        509 => 'Bandwidth Limit Exceeded'
    ];

    /**
     * @var array
     */
    public static $headers = [
        'X-Powered-By' => 'Uiweb/3.0'
    ];

    public function __handle()
    {
        return $this;
    }

    public function setStatus($status = 200)
    {
        header('HTTP/1.1 ' . $status . ' ' . self::$statuses[$status]);
    }

    public static function setHeader($k, $v)
    {
        self::$headers[$k] = $v;
    }

    public static function setHeaders(array $headers = [])
    {
        foreach ($headers as $type => $header) {
            self::setHeader($type, $header);
        }
    }

    public static function sendHeaders()
    {
        foreach (self::$headers as $k => $v) {
            header($k . ': ' . $v);
        }
    }

    public function json($status = 200)
    {
        $this->setStatus($status);
        $this->setHeader('Cache-Control', 'no-cache, must-revalidate');
        $this->setHeader('Content-type', 'application/json; charset=UTF-8');
        $this->setHeader('Pragma', 'no-cache');
        $this->setHeader('Expires', '-1');
    }

    public function image(Image $image, $status = 200)
    {
        $this->setStatus($status);
        switch($image->getExtension()) {
            case 'jpeg':
            case 'jpg':
                $this->setHeader('Content-type', 'image/jpeg');
                break;
            case 'gif':
                $this->setHeader('Content-type', 'image/gif');
                break;
            case 'png':
                $this->setHeader('Content-type', 'image/png');
                break;
        }

//        header('Content-type: ' . $image->content_type);
//        header("Content-Length: " . filesize(ABS . $image->resized_filename));
    }

    public function html($status = 200)
    {
        $this->setStatus($status);
        $this->setHeader('Content-Type', 'text/html; charset=utf-8');
    }

    public function xml($status = 200)
    {
        $this->setStatus($status);
        $this->setHeader('Content-Type', 'text/xml; charset=utf-8');
    }
}