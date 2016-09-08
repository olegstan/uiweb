<?php
namespace app\system\controllers\ajax;

use app\layer\LayerController;
use app\models\image\Image;
use app\models\product\Product;
use core\helper\Response;

class ImageController extends LayerController
{
    public function uploadImage()
    {
        $width = $this->getCore()->request->post('width');
        $height = $this->getCore()->request->post('height');
        $position = $this->getCore()->request->has('position') ? $this->getCore()->request->post('position') : 0;

        $type = $this->getCore()->request->post('type');
        $object_id = $this->getCore()->request->post('object_id');
        $module_id = $this->getCore()->request->post('module_id');

        switch($module_id){
            case 2:
                $object = (new Product())
                    ->query()
                    ->select()
                    ->where('id = :id', [':id' => $object_id])
                    ->limit()
                    ->execute()
                    ->one()
                    ->getResult();
                break;
        }

        if(!$object){
            return Response::json(['result' => 'error']);
        }

        $file = $this->getCore()->request->file('file');

        $image = (new Image());
        $image->tmp_file = $file;
        $image->module_id = $module_id;
        $image->object_id = $object_id;
        $image->type = $type;
        $image->position = $position;
        $result = $image->uploadImage(array_merge(
            pathinfo($file['name']),
            ['name' => $object->name]
        ));

        if($result){
            if(isset($width) && isset($height)){
                $image->res($width, $height);
            }
            return Response::json(['result' => 'success', 'image' => $image]);
        }else{
            return Response::json(['result' => 'error']);
        }
    }

    public function uploadImageByUrl()
    {
        $url = $this->getCore()->request->post('url');

        if(filter_var($url, FILTER_VALIDATE_URL) === false){
            return Response::json(['result' => 'error']);
        }else{
            $width = $this->getCore()->request->post('width');
            $height = $this->getCore()->request->post('height');
            $position = $this->getCore()->request->has('position') ? $this->getCore()->request->post('position') : 0;

            $type = $this->getCore()->request->post('type');
            $object_id = $this->getCore()->request->post('object_id');
            $module_id = $this->getCore()->request->post('module_id');

            switch($module_id){
                case 2:
                    $object = (new Product())
                        ->query()
                        ->select()
                        ->where('id = :id', [':id' => $object_id])
                        ->limit()
                        ->execute()
                        ->one()
                        ->getResult();
                    break;
            }

            if(!$object){
                return Response::json(['result' => 'error']);
            }

            $file = tempnam(sys_get_temp_dir(), '');
            //$file = tempnam(ABS . '/tmp/files', 'tpm_');
            $path_info = pathinfo($url);
            file_put_contents($file, file_get_contents($url));

            if(isset($path_info['extension'])){
                $ext = $path_info['extension'];
            }else{
                $image = getimagesize($url);
                switch($image['mime']){
                    case 'image/jpeg':
                        $ext = 'jpeg';
                        break;
                    case 'image/gif':
                        $ext = 'gif';
                        break;
                    case 'image/png':
                        $ext = 'png';
                        break;
                }
            }

            if(isset($url) && isset($ext)){
                $image = (new Image());
                $image->tmp_file['tmp_name'] = $file;
                $image->module_id = $module_id;
                $image->object_id = $object_id;
                $image->type = $type;
                $image->position = $position;
                $result = $image->uploadImage([
                    'basename' => $path_info['filename'] . '.' . $ext,
                    'filename' => $path_info['filename'],
                    'extension' => $ext,
                    'name' => $object->name
                ]);

                if($result){
                    if(isset($width) && isset($height)){
                        $image->res($width, $height);
                    }
                    return Response::json(['result' => 'success', 'image' => $image]);
                }else{
                    return Response::json(['result' => 'error']);
                }
            }else{
                return Response::json(['result' => 'error']);
            }
        }
    }

    public function updateImagePosition()
    {
        $image_id = $this->getCore()->request->get('image_id');
        $position = $this->getCore()->request->get('position');

        $model = (new Image())
            ->query()
            ->select()
            ->where('id = :id', [':id' => $image_id])
            ->limit()
            ->execute()
            ->one()
            ->getResult();

        if($model){
            $model->position = $position;
            $model->update();

            return Response::json(['result' => 'success']);
        }else{
            $error = '404';
            return Response::json((new ErrorController())->$error());
        }
    }

    public function deleteImage()
    {
        $image_id = $this->getCore()->request->get('image_id');
        $type = $this->getCore()->request->get('type');

        $image = (new Image())
            ->query()
            ->select()
            ->where('id = :id', [':id' => $image_id])
            ->limit()
            ->execute()
            ->one(['folder' => $type])
            ->getResult();

        if($image){
            $image->delete();
            return Response::json(['result' => 'success']);
        }else {
            $error = '404';
            return Response::json((new ErrorController())->$error());
        }
    }
}

//
//    function uploadImage()
//    {
//        $type = $this->getCore()->request->post('type');
//        $object_id = $this->getCore()->request->post('object_id');
//        $module_id = $this->getCore()->request->post('module_id');
//
//
//        $file = $this->getCore()->request->file('file');
//
//        $inner_path = $object_id[0].'/'.$object_id[1].'/';
//        $path_info = pathinfo($file['name']);
//        $new_name = $path_info['basename'];
//        $ext = $path_info['basename'];
//
//
//
//        echo '<pre>';
//        var_dump(pathinfo($file['name']));
//        var_dump($file);
//        var_dump($type);
//        var_dump($object_id);
//        var_dump(ABS);
//        echo '</pre>';
//        die();
//
//        if(in_array($ext, $this->allowed_extentions))
//        {
//            while(file_exists(ABS . '/files/images/originals/' . $type . '/' . $inner_path . $new_name))
//            {
//                if(preg_match('/_([0-9]+)$/', $path_info['basename'], $parts))
//                    $new_name = $base.'_'.($parts[1]+1).'.'.$ext;
//                else
//                    $new_name = $base.'_1.'.$ext;
//            }
//
//            if (!file_exists($this->config->root_dir.$this->config->original_images_dir.$object.'/'.$inner_path))
//                //$this->recursive_mkdir($this->config->root_dir.$this->config->original_images_dir.$object.'/'.$inner_path);
//                mkdir($this->config->root_dir.$this->config->original_images_dir.$object.'/'.$inner_path, 0777, true);
//
//            if(move_uploaded_file($filename, $this->config->root_dir.$this->config->original_images_dir.$object.'/'.$inner_path.$new_name))
//                return $new_name;
//        }
//    }