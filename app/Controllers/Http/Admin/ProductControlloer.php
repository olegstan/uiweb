<?php
namespace App\Controllers\Http\Admin;

use App\Layers\LayerAdminController;
use App\Models\Product\Product;
use Framework\Response\Types\HtmlResponse;
use Framework\View\View;

class ProductControlloer extends LayerAdminController
{
    public function index()
    {
        return HtmlResponse::html(new View('admin/product/index.php', [
            'products' => (new Product())->getQuery()->with(['images'])->select()->where('brand_id = :brand_id AND is_active = 0', ['brand_id' => 1])->execute()->all()->get()
        ]));
    }
}