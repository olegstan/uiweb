<?php
namespace Framework\FileSystem;

use Framework\Config;
use Framework\Resize\ResizeGd;
use Framework\Resize\ResizeImagick;

class Image extends File
{
    /**
     * @var string
     */
    public $name;
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
    public $extension;
    /**
     * @var array
     */
    public $extensions = [
        'png', 'gif', 'jpg', 'jpeg'
    ];

    /**
     * @return ResizeGd|ResizeImagick
     */
    public function getResizeDriver()
    {
        switch(Config::get('resize', 'driver')){
            case 'imagick':
                return new ResizeImagick();
            case 'gd':
                return new ResizeGd();
        }
    }

    /**
     * @param string $original
     * @param string $resized
     * @param int $width
     * @param int $height
     * @return bool
     */
    public function resize($original, $resized, $width, $height)
    {
        if(!Folder::isExists($this->getDirname())){
            Folder::create($this->getDirname());
        }

        if(!Folder::isExists($this->getDirname()) || !Folder::isReadeble($this->getDirname())){
            die('не пишет ' . $this->getDirname());
        }

        return $this->getResizeDriver()->resize($original, $resized, $width, $height);

//        $watermark_offet_x = 50;
//        $watermark_offet_y = 50;

        //$sharpen = min(100, $this->settings->images_sharpen)/100;
        //$watermark_transparency =  1-min(100, $this->settings->watermark_transparency)/100;

        /*if((in_array($object, array('products', 'image')) || ($object == 'reviews' && $this->settings->watermark_reviews_is_enabled == 1)) && is_file($this->config->watermark_file) && $this->settings->watermark_is_enabled==1)
            $watermark = $this->config->watermark_file;
        else
            $watermark = null;*/

//
//
//        if (isset($path_info['dirname']) && !file_exists(ABS . $path_info['dirname'])) {
//            mkdir(ABS . $path_info['dirname'], 0777, true);
//        }


//        return $this->resized_filename;
    }
}