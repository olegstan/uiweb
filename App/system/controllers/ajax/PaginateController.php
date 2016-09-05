<?php
namespace app\system\controllers\ajax;

use app\layer\LayerController;
use app\models\badge\Badge;
use app\models\Brand;
use app\models\category\Category;
use app\models\group\GroupCategoryMain;
use app\models\group\GroupProductMain;
use app\models\material\Material;
use app\models\material\MaterialMenu;
use app\models\material\MaterialMenuItem;
use app\models\modificator\Modificator;
use app\models\modificator\ModificatorOrder;
use app\models\product\Product;
use app\models\tag\TagGroup;
use app\models\tag\TagSet;
use app\models\user\User;
use app\models\user\UserGroup;
use core\helper\Response;

class PaginateController extends LayerController
{
    public function products()
    {
        $sort = $this->getCore()->request->request('sort');
        $sort_type = $this->getCore()->request->request('sort_type');
        $category_id = $this->getCore()->request->request('category_id');

        $condition = '';
        $bind = [];

        if(isset($category_id) && !empty($category_id)){
            $condition = 'mc_products_categories.category_id = :category_id';
            $bind[':category_id'] = $category_id;
        }

        $models = (new Product())
            ->query()
            ->with(['images', 'variants'])
            ->select()
            ->leftJoin('mc_products_categories', 'mc_products.id = mc_products_categories.product_id')
            ->where($condition, $bind)
            ->order('mc_products.'.$sort, $sort_type)
            ->paginate(['folder' => 'products', 'resize' => ['width' => 50, 'height' => 50]]);

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



    public function users()
    {
        $group_id = $this->getCore()->request->request('group_id');

        $query = (new User())
            ->query()
            ->with(['groups'])
            ->select();

        if($group_id){
            $query = $query->where('group_id = :group_id', [':group_id' => $group_id]);
        }

        $models = $query->paginate();

        return Response::json($models);
    }

    public function groups()
    {
        $models = (new UserGroup())
            ->query()
            ->select()
            ->paginate();

        return Response::json($models);
    }

    public function materials()
    {
        $sort = $this->getCore()->request->request('sort');
        $sort_type = $this->getCore()->request->request('sort_type');

        $models = (new Material())
            ->query()
            ->select()
            ->order($sort, $sort_type)
            ->paginate();

        return Response::json($models);
    }

    public function materialsCategories()
    {

    }

    public function materialsMenuItems()
    {
        $models = (new MaterialMenuItem())
            ->query()
            ->select()
            ->paginate();

        return Response::json($models);
    }

    public function materialsMenus()
    {
        $models = (new MaterialMenu())
            ->query()
            ->select()
            ->paginate();

        return Response::json($models);
    }


}