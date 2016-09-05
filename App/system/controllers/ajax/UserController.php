<?php
namespace app\system\controllers\ajax;

use app\layer\LayerController;
use app\models\user\User;
use app\models\user\LoginAdminForm;
use app\models\user\UserGroup;
use core\helper\Response;

class UserController extends LayerController
{
    public function login()
    {
        return (new LoginAdminForm())->login();
    }

    public function groups()
    {
        $models = (new UserGroup())
            ->query()
            ->select()
            ->order('position')
            ->execute()
            ->all()
            ->getResult();

        return Response::json($models);
    }
}