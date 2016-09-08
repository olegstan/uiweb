<?php
namespace app\system\controllers\ajax;

use app\layer\LayerController;
use app\models\material\MaterialCategory;
use core\helper\Response;

class PagesController extends LayerController
{
    public function categories()
    {
        $models = (new MaterialCategory())
            ->query()
            ->select()
            ->execute()
            ->all()
            ->getResult();

        return Response::json($models);
    }
}