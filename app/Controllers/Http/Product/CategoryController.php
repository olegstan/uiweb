<?php
namespace App\Controllers\Http\Product;

use App\Controllers\Http\Requests\ProductRequest;
use App\Layers\LayerHttpController;
use App\Models\Product\Category;

class CategoryController extends LayerHttpController
{
    public function view(ProductRequest $request)
    {
        $category = (new Category())->findByField('url', $request->getRoute('category_url'));

        echo '<pre>';
        var_dump($category);
        echo '</pre>';
        die();
    }
}