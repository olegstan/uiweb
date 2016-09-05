<?php
namespace app\system\controllers\ajax;

use app\layer\LayerController;
use app\models\badge\Badge;
use app\models\Brand;
use app\models\category\Category;
use app\models\group\GroupCategoryMain;
use app\models\group\GroupProductMain;
use app\models\modificator\Modificator;
use app\models\modificator\ModificatorOrder;
use app\models\product\Product;
use app\models\tag\TagGroup;
use app\models\tag\TagSet;
use core\helper\Response;

class CatalogController extends LayerController
{
    public function products()
    {
        $sort = $this->getCore()->request->request('sort');
        $sort_type = $this->getCore()->request->request('sort_type');

        $models = (new Product())
            ->query()
            ->with(['images', 'variants'])
            ->select()
            ->order($sort, $sort_type)
            ->paginate(null, 'id');

        return Response::json($models);
    }

    public function categories()
    {
        $models = (new Category())
            ->query()
            ->select([
                'mc_categories.id',
                'mc_categories.parent_id',
                'mc_categories.url',
                'mc_categories.name',
                'mc_categories.frontend_name',
                'mc_product_count.count'
            ])
            ->leftJoin('mc_product_count', 'mc_categories.id = mc_product_count.category_id AND mc_product_count.brand_id IS NULL')
            ->order('position')
            ->execute()
            ->all(null)
            ->getResult();

        return Response::json($models);
    }

    public function brands()
    {
        $sort = $this->getCore()->request->request('sort');
        $sort_type = $this->getCore()->request->request('sort_type');

        $models = (new Brand())
            ->query()
            ->select()
            ->order($sort, $sort_type)
            ->paginate();

        return Response::json($models);
    }

    public function badges()
    {
        $sort = $this->getCore()->request->request('sort');
        $sort_type = $this->getCore()->request->request('sort_type');

        $models = (new Badge())
            ->query()
            ->select()
            ->order($sort, $sort_type)
            ->paginate();

        return Response::json($models);
    }

    public function properties()
    {
        $sort = $this->getCore()->request->request('sort');
        $sort_type = $this->getCore()->request->request('sort_type');

        $models = (new TagGroup())
            ->query()
            ->select()
            ->order($sort, $sort_type)
            ->paginate();

        return Response::json($models);
    }

    public function modificators()
    {
        $sort = $this->getCore()->request->request('sort');
        $sort_type = $this->getCore()->request->request('sort_type');

        $models = (new Modificator())
            ->query()
            ->select()
            ->order($sort, $sort_type)
            ->paginate();

        return Response::json($models);
    }

    public function modificatorsOrders()
    {
        $models = (new ModificatorOrder())
            ->query()
            ->select()
            ->paginate();

        return Response::json($models);
    }

    public function filtersCategory()
    {
        $sort = $this->getCore()->request->request('sort');
        $sort_type = $this->getCore()->request->request('sort_type');

        $models = (new TagSet())
            ->query()
            ->select()
            ->order($sort, $sort_type)
            ->paginate();

        return Response::json($models);
    }

    public function productsGroups()
    {
        $models = (new GroupProductMain())
            ->query()
            ->select()
            ->paginate();

        return Response::json($models);
    }

    public function categoriesGroups()
    {
        $models = (new GroupCategoryMain())
            ->query()
            ->select()
            ->paginate();

        return Response::json($models);
    }
}