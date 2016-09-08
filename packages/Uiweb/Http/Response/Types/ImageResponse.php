<?php
namespace Framework\Response\Types;

use Framework\FileSystem\Image;
use Framework\Response\HttpResponse;
use Framework\Http\Header;

class ImageResponse extends HttpResponse
{
    /**
     * @var string
     */
    public $type = 'image';
    /**
     * @var Image
     */
    public $data;

    /**
     * @param $data
     * @param int $status
     * @param array $headers
     */
    public function __construct(Image $image, $status = 200, array $headers = [])
    {
        $this->header = new Header();
        $this->header->{'image'}($image, $status);
        $this->header->setHeaders($headers);

        $this->data = $image;
        $this->status = $status;
    }

    public function getData()
    {
        $this->header->sendHeaders();
        readfile($this->data->path);
    }
}