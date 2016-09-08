<?php
namespace app\controllers;

use app\layer\LayerController;
use app\models\image\Image;
use core\helper\Header;
use core\helper\Response;

class FilesController extends LayerController
{
    public function images()
    {
		
        $resized_filename = $this->getCore()->request_uri;;

		
		
        $image = (new Image());
        $image->type = $this->getCore()->request->routes[3];
        $image->resized_filename = $resized_filename;

        list($image->filename, $image->resized_width , $image->resized_height) = $image->getResizeParams();


		
        $image->resize();

        if(is_readable(ABS . $image->resized_filename))
        {
//            header('Content-type: ' . $image->content_type);
            Header::image($image);
            readfile(ABS . $image->resized_filename);
        }
    }




    /*private $param_url, $options;

    public function __construct()
    {
        parent::__construct();
    }

    public function set_params($url = null, $options = null)
    {
        $this->param_url = $url;
        $this->options = $options;
    }

    /**
     *
     * Отображение
     *
     */
    /*function fetch()
    {
        if ($this->param_url[0] == "/")
            $this->param_url = mb_substr($this->param_url, 1, mb_strlen($this->param_url, 'utf-8')-1, 'utf-8');

        $arr = explode("/", $this->param_url);
        $keys = array_keys($arr);

        if (count($arr)<2)
            return false;

        ### Получим объект
        $object = reset($arr);
        unset($arr[reset($keys)]);

        ### Получим имя файла
        $filename = end($arr);
        unset($arr[end($keys)]);

        ### Внутренний путь
        $inner_path = join("/", $arr);

        ### Извлечем из filename token
        /*$arr = explode("?", $filename);
        if (count($arr) !== 2)
            return false;

        $filename = $arr[0];
        $token = $arr[1];

        if(!$this->config->check_token($filename, $token))
            exit('bad token');*/

        /*$resized_filename =  $this->image->resize($object, $filename);

        if(is_readable($resized_filename))
        {
            header('Content-type: image');
            print file_get_contents($resized_filename);
        }
    }*/


}