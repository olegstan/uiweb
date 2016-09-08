<?php
namespace app\controllers;

use app\layer\LayerController;
use app\models\material\Material;
use app\models\material\MaterialCategory;
use core\helper\Response;

class PagesController extends LayerController
{

    public function category($url)
    {
        $material_category = (new MaterialCategory())->getMaterials('is_visible = 1 AND url = :url', [':url' => $url]);

        if($material_category){

            $this->getCore()->title = $material_category->meta_title;
            $this->getCore()->meta_keywords = $material_category->meta_keywords;
            $this->getCore()->meta_description = $material_category->meta_description;

            $this->design->assign('material_category', $material_category);

            return Response::html($this->render(TPL . '/' . $this->getCore()->tpl_path .'/html/material-category.tpl'));
        }else{
            $error = '404';
            return Response::html((new ErrorController())->$error('Страница не существует'));
        }
    }

    public function material($url)
    {
        $material = (new Material())
            ->query()
            ->select()
            ->where('is_visible = 1 AND url = :url', [':url' => $url])
            ->limit()
            ->execute()
            ->one()
            ->getResult();

        if($material){
            $this->getCore()->title = $material->meta_title;
            $this->getCore()->meta_keywords = $material->meta_keywords;
            $this->getCore()->meta_description = $material->meta_description;

            $this->design->assign('material', $material);

            return Response::html($this->render(TPL . '/' . $this->getCore()->tpl_path .'/html/material.tpl'));
        }else{
            $error = '404';
            return Response::html((new ErrorController())->$error('Страница не существует'));
        }
    }
}