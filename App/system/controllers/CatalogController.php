<?php
namespace app\system\controllers;

use app\layer\LayerController;
use app\models\category\Category;
use app\layer\LayerAdminController;
use app\models\product\Product;
use core\helper\Response;

class CatalogController extends LayerAdminController
{
    public function brands()
    {
        return Response::html($this->render(SYSTEM_TPL . '/system/html/catalog/brands.tpl'));
    }

    public function badges()
    {
        return Response::html($this->render(SYSTEM_TPL . '/system/html/catalog/badges.tpl'));
    }

    public function filtersCategory()
    {
        return Response::html($this->render(SYSTEM_TPL . '/system/html/catalog/filters-category.tpl'));
    }

    public function properties()
    {
        return Response::html($this->render(SYSTEM_TPL . '/system/html/catalog/properties.tpl'));
    }

    public function modificatorsGroups()
    {
        return Response::html($this->render(SYSTEM_TPL . '/system/html/catalog/modificators.tpl'));
    }

    public function modificators()
    {
        return Response::html($this->render(SYSTEM_TPL . '/system/html/catalog/modificators.tpl'));
    }

    public function modificatorsOrders()
    {
        return Response::html($this->render(SYSTEM_TPL . '/system/html/catalog/modificators-orders.tpl'));
    }

    public function productsGroups()
    {
        return Response::html($this->render(SYSTEM_TPL . '/system/html/catalog/products-groups.tpl'));
    }

    public function categoriesGroups()
    {
        return Response::html($this->render(SYSTEM_TPL . '/system/html/catalog/categories-groups.tpl'));
    }
}