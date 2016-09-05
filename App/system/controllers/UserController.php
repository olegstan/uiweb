<?php
namespace app\system\controllers;

use app\layer\LayerAdminController;
use core\helper\Response;

class UserController extends LayerAdminController
{
    public function index()
    {
        return Response::html($this->render(SYSTEM_TPL . '/system/html/user/users.tpl'));
    }

    public function groups()
    {
        return Response::html($this->render(SYSTEM_TPL . '/system/html/user/groups.tpl'));
    }

    public function login()
    {
        return Response::html($this->render(SYSTEM_TPL . '/system/html/login.tpl', 'login.tpl'));
    }
}