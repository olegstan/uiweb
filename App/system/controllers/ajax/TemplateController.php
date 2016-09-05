<?php
namespace app\system\controllers\ajax;

use app\layer\LayerAdminController;
use app\models\category\Category;
use app\models\tag\TagGroup;
use core\helper\Response;
use \Exception;

class TemplateController extends LayerAdminController
{
    public function getTemplate()
    {
        $path = $this->getCore()->request->request('path');
        $variables = $this->getCore()->request->request('variables');
        $value = $this->getCore()->request->request('value');

        $key = $this->getCore()->request->request('key');
        $this->design->assign('key', $key);

        if(isset($variables)){
            if(in_array('all_categories', $variables)){
                $all_categories = (new Category())
                    ->query()
                    ->with(['image'])
                    ->select()
                    ->execute()
                    ->all()
                    ->toTree('parent_id')
                    ->getResult();

                $this->design->assign('all_categories', $all_categories);
            }

            if(in_array('group', $variables)){
                $group = (new TagGroup())
                    ->query()
                    ->with(['tags'])
                    ->select()
                    ->where('id = :id', [':id' => $value])
                    ->limit()
                    ->execute()
                    ->one()
                    ->getResult();

                $this->design->assign('group', $group);
            }
        }

        try {
            return Response::json(['result' => 'success', 'template' => $this->design->fetch(SYSTEM_TPL . $path)]);
        }catch(Exception $e){
            return Response::json(['result' => 'error']);
        }
    }
}