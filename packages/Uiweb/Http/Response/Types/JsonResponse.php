<?php
namespace Uiweb\Response\Types;

use Uiweb\Response\HttpResponse;
use Uiweb\Http\Header;
use Uiweb\Interfaces\Data\Jsonable;

class JsonResponse extends HttpResponse implements Jsonable
{
    /**
     * @var string
     */
    public $type = 'json';
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
    public function __construct(array $array, $status = 200, array $headers = [])
    {
        $this->header = new Header();
        $this->header->{'json'}($status);
        $this->header->setHeaders($headers);

        $this->data = self::toJson($array);
        $this->status = $status;
    }

    /**
     * @param array $array
     * @return mixed
     */
    public static function toJson(array $array = [])
    {
        return json_encode($array);
    }

    public function getData()
    {
        $this->header->sendHeaders();
        return $this->data;
    }
}