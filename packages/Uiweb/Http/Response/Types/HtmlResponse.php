<?php
namespace Uiweb\Response\Types;

use Uiweb\Response\HttpResponse;
use Uiweb\Http\Header;

class HtmlResponse extends HttpResponse
{
    /**
     * @var string
     */
    public $type = 'html';
    /**
     * @var Header
     */
    public $header;
    /**
     * @var int
     */
    public $status = 200;

    /**
     * @param $data
     * @param int $status
     * @param array $headers
     */
    public function __construct($data, $status = 200, array $headers = [])
    {
        $this->header = new Header();
        $this->header->{$this->type}($status);
        $this->header->setHeaders($headers);

        $this->data = $data;
        $this->status = $status;
    }
}