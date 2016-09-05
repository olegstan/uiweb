<?php
namespace App\Models\Product;

use App\Layers\LayerDatabaseModel;

class Image extends LayerDatabaseModel
{
    /**
     * @var string
     */
    protected $table = 'products_images';
    /**
     * @var string
     */
    public $path;
    /**
     * @var array
     */
    protected $fillable = [
        'product_id',
        'path'
    ];

    /**
     * @return string
     */
    public function original()
    {
        return '/assets/img/original/products/' . $this->path;
    }

    /**
     * @param int $width
     * @param  int $height
     * @return string
     */
    public function resized($width, $height)
    {
        return '/assets/img/resized/products/' . $this->addResizeParams($width, $height);

    }

    public function addResizeParams($width, $height)
    {
        $path_info = pathinfo($this->path);

        if($path_info['dirname'] === '.'){
            $path_info['dirname'] = '';
        }else{
            $path_info['dirname'] = $path_info['dirname']  . '/';
        }

        if($width > 0 || $height > 0) {
            return $path_info['dirname'] . $path_info['filename'] . '.' . $width . 'x' . $height . '.' . $path_info['extension'];
        }else {
            return $path_info['dirname'] . $path_info['filename'] . '.' . $path_info['extension'];
        }
    }
}