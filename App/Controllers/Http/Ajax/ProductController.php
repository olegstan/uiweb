<?php
namespace App\Controllers\Http\Ajax;

use App\Controllers\Http\Ajax\Requests\AjaxRequest;
use App\Layers\LayerAjaxController;
use App\Models\Product\Product;
use Framework\Response\Types\JsonResponse;

class ProductController extends LayerAjaxController
{
    public function setIsActive(AjaxRequest $request)
    {
        $product = (new Product())->getQuery()->select()->where('id = :id', [':id' => $request->getRoute('id')])->execute()->one()->get();
        $product->is_active = 1;
        $product->save();

        return new JsonResponse(['result' => 'success']);
    }
}