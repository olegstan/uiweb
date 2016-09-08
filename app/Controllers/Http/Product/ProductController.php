<?php
namespace App\Controllers\Http\Product;

use App\Controllers\Http\Requests\ProductRequest;
use App\Layers\LayerHttpController;

class ProductController extends LayerHttpController
{
    public function view(ProductRequest $request)
    {
        echo '<pre>';
        var_dump($request->getRoute('category_url'));
        var_dump($request->getRoute('product_url'));
        echo '</pre>';
        die();
    }
}