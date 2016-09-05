<?php
namespace app\system\controllers\ajax;

use app\controllers\ajax;
use app\layer\LayerController;
use app\models\category\Category;
use core\helper\Response;
use app\controllers\ErrorController;

class CategoryController extends LayerController
{
    public function updatePosition()
    {
        $category_id = $this->getCore()->request->get('category_id');
        $parent_id = $this->getCore()->request->get('parent_id');
        $position = $this->getCore()->request->get('position');

        $model = (new Category())
            ->query()
            ->select()
            ->where('id = :id', [':id' => $category_id])
            ->limit()
            ->execute()
            ->one()
            ->getResult();

        if($model){
            $model->parent_id = $parent_id;
            $model->position = $position;
            $model->update();
        }else{
            $error = '404';
            return Response::json((new ErrorController())->$error());
        }
    }

    public function updateCollapse()
    {
        $category_id = $this->getCore()->request->get('category_id');
        $is_collapsed = filter_var($this->getCore()->request->get('is_collapsed'), FILTER_VALIDATE_BOOLEAN) ? 1 : 0;

        $category = (new Category())
            ->query()
            ->select()
            ->where('id = :id', [':id' => $category_id])
            ->limit()
            ->execute()
            ->one()
            ->getResult();

        if($category){
            $category->is_collapsed = $is_collapsed;
            $category->update();
        }else{
            $error = '404';
            return Response::json((new ErrorController())->$error());
        }
    }

    public function updateCollapseAll()
    {
        (new Category())
            ->query()
            ->update(['is_collapsed' => 0])
            ->execute();
    }

    public function updateExpandAll()
    {
        (new Category())
            ->query()
            ->update(['is_collapsed' => 1])
            ->execute();
    }
}