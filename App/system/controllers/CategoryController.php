<?php
namespace app\system\controllers;

use app\layer\LayerController;
use app\models\category\Category;
use app\layer\LayerAdminController;
use app\models\product\Product;
use core\helper\Response;

class CategoryController extends LayerAdminController
{
    public function index()
    {
        return Response::html($this->render(SYSTEM_TPL . '/system/html/category/index.tpl'));
    }

    public function create()
    {
        return Response::html($this->render(SYSTEM_TPL . '/system/html/category/category.tpl'));
    }

    public function edit($id)
    {
        $model = (new Category())
            ->query()
            ->select()
            ->where('id = :id', [':id' => $id])
            ->limit()
            ->execute()
            ->one()
            ->getResult();

        $this->design->assign('category', $model);

        return Response::html($this->render(SYSTEM_TPL . '/system/html/category/category.tpl'));
    }


}