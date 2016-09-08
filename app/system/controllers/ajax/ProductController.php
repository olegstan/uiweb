<?php
namespace app\system\controllers\ajax;

use app\controllers\ajax;
use app\layer\LayerController;
use app\models\image\Image;
use app\models\product\Product;
use app\models\Variant;
use core\helper\Response;
use app\controllers\ErrorController;

class ProductController extends LayerController
{
    public function updateVisible()
    {
        $product_id = $this->getCore()->request->get('product_id');
        $is_visible = filter_var($this->getCore()->request->get('is_visible'), FILTER_VALIDATE_BOOLEAN) ? 1 : 0;


        $model = (new Product())
            ->query()
            ->select()
            ->where('id = :id', [':id' => $product_id])
            ->limit()
            ->execute()
            ->one()
            ->getResult();

        if($model){
            $model->is_visible = $is_visible;
            $model->update();
        }else{
            $error = '404';
            return Response::json((new ErrorController())->$error());
        }
    }

    public function save()
    {

    }

    public function delete()
    {
        $product_id = $this->getCore()->request->get('product_id');


        $model = (new Product())
            ->query()
            ->select()
            ->where('id = :id', [':id' => $product_id])
            ->limit()
            ->execute()
            ->one()
            ->getResult();

        if($model){
            $model->delete();
        }else{
            $error = '404';
            return Response::json((new ErrorController())->$error());
        }
    }

    public function productImages()
    {
        $product_id = $this->getCore()->request->get('product_id');

        $images = (new Image())
            ->query()
            ->select()
            ->where('object_id = :object_id AND module_id = :module_id', [':object_id' => $product_id, ':module_id' => Product::$module_id])
            ->order('position')
            ->execute()
            ->all(['folder' => 'products', 'resize' => ['width' => 90, 'height' => 90]])
            ->getResult();

        return Response::json(['result' => 'success', 'items' => $images]);
    }

    public function productVariants()
    {
        $product_id = $this->getCore()->request->get('product_id');

        $images = (new Variant())
            ->query()
            ->select()
            ->where('product_id = :product_id', [':product_id' => $product_id])
            ->order('position')
            ->execute()
            ->all(null, 'id')
            ->getResult();

        return Response::json(['result' => 'success', 'items' => $images]);
    }

}