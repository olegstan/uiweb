<?php
namespace App\Controllers\Http\Requests;

use App\Layers\LayerHttpRequest;

class FileRequest extends LayerHttpRequest
{
    /**
     * @var bool
     */
    public $is_set;
    /**
     * @var string
     */
    public $width;
    /**
     * @var string
     */
    public $height;
    /**
     * @var string
     */
    public $path;
    /**
     * @var string
     */
    public $resized_path;
    /**
     * @var string
     */
    public $extension;

    /**
     * @return string
     */
    public function getWidth()
    {
        $this->getResizeParams();
        return $this->width;
    }

    /**
     * @return string
     */
    public function getHeight()
    {
        $this->getResizeParams();
        return $this->height;
    }

    /**
     * @return string
     */
    public function getExtension()
    {
        $this->getResizeParams();
        return $this->extension;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        $this->getResizeParams();
        return ABS . '/public' . $this->path . '.' . $this->extension;
    }

    /**
     * @return string
     */
    public function getUriWithAbsolutePath()
    {
        return ABS . '/public' . $this->getUriWithoutGetParameters();
    }

    /**
     * @return void
     */
    private function getResizeParams()
    {
        if(!$this->is_set){
            if(!preg_match('/([^.]+)\.(([0-9]*)x([0-9]*)(w)?\.)?([^\.]+)$/', $this->getUriWithoutGetParameters(), $matches)){
                //return false;
            }

            $resized_path = $matches[1];// имя запрашиваемого файла
            $this->width = $matches[3];// ширина будущего изображения
            $this->height = $matches[4];// высота будущего изображения
            $this->extension = $matches[6];// расширение файла

            $file_parts = explode('/', $resized_path);
            $file_parts[3] = 'original';
            $this->path = implode('/', $file_parts);

            $this->is_set = true;
        }
    }
}