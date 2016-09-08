<?php
namespace app\controllers;

use app\layer\LayerController;
use app\models\Brand;
use app\models\category\Category;
use app\models\product\Product;
use app\models\Variant;
use app\models\image\Image;
use core\helper\Response;

class CatalogController extends LayerController
{
    public function __construct()
    {
        parent::__construct();
        $this->getCore()->asset->addFooterJS('/libraries/matchHeight/jquery.matchHeight-min.js');
    }

    public function product($url)
    {
        $this->getCore()->asset->addHeaderCSS('/libraries/fancybox/source/jquery.fancybox.css');
        $this->getCore()->asset->addFooterJS('/libraries/fancybox/source/jquery.fancybox.js');

        $product = (new Product())
            ->query()
            ->with(['badges', 'images', 'variants'])
            ->select()
            ->where('url = :url', [':url' => $url])
            ->limit()
            ->execute()
            ->one()
            ->getResult();

        if($product){
            $product->countViews();
            $product->update();


            $this->getCore()->title = $product->meta_title;
            $this->getCore()->meta_keywords = $product->meta_keywords;
            $this->getCore()->meta_description = $product->meta_description;

            $this->design->assign('product', $product);

            return Response::html($this->render(TPL . '/' . $this->getCore()->tpl_path .'/html/product.tpl'));
        }else{
            $error = '404';
            return Response::html((new ErrorController())->$error('Страница не существует'));
        }
    }

    public function catalog($url)
    {
        $category = (new Category())
            ->query()
            ->with(['products'])
            ->select()
            ->where('is_visible = 1 AND url = :url', [':url' => $url])
            ->limit()
            ->execute()
            ->one()
            ->getResult();

        if($category){

            $this->getCore()->title = $category->meta_title;
            $this->getCore()->meta_keywords = $category->meta_keywords;
            $this->getCore()->meta_description = $category->meta_description;

            $this->design->assign('category', $category);

            return Response::html($this->render(TPL . '/' . $this->getCore()->tpl_path .'/html/catalog.tpl'));
        }else{
            $error = '404';
            return Response::html((new ErrorController())->$error('Страница не существует'));
        }
    }


    public function brand($url)
    {
        $brand = (new Brand())
            ->query()
            ->with(['products'])
            ->select()
            ->where('is_visible = 1 AND url = :url', [':url' => $url])
            ->limit()
            ->execute()
            ->one()
            ->getResult();

        if($brand){
            $this->getCore()->title = $brand->meta_title;
            $this->getCore()->meta_keywords = $brand->meta_keywords;
            $this->getCore()->meta_description = $brand->meta_description;

            $this->design->assign('brand', $brand);

            return Response::html($this->render(TPL . '/' . $this->getCore()->tpl_path .'/html/brand.tpl'));
        }else{
            $error = '404';
            return Response::html((new ErrorController())->$error('Страница не существует'));
        }
    }
}