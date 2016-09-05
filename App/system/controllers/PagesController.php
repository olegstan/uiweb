<?php
namespace app\system\controllers;

use app\layer\LayerAdminController;
use core\helper\Response;

class PagesController extends LayerAdminController
{
    public function materials()
    {
        return Response::html($this->render(SYSTEM_TPL . '/system/html_new/pages/materials.tpl'));
    }

    public function categories()
    {
        return Response::html($this->render(SYSTEM_TPL . '/system/html_new/pages/categories.tpl'));
    }

    public function menuItems()
    {
        return Response::html($this->render(SYSTEM_TPL . '/system/html_new/pages/menu-items.tpl'));
    }

    public function menus()
    {
        return Response::html($this->render(SYSTEM_TPL . '/system/html_new/pages/menus.tpl'));
    }
}