<?php
namespace App\Controllers\Http;

use App\Layers\LayerHttpController;
use App\Layers\LayerView as View;
use App\Models\Product\Product;
use Framework\Cart\Cart;
use Framework\Response\Types\HtmlResponse;

class AccountController extends LayerHttpController
{
    public function cart()
    {
        return new HtmlResponse(new View('account/cart.php', [
            'products' => Cart::getAll(),
            'title' => 'Корзина Praset.ru',
            'meta_keywords' => 'Корзина Praset.ru',
            'meta_description' => 'Корзина Praset.ru',
        ]));
    }

    public function info()
    {
        return new HtmlResponse(new View('account/info.php', [

            'title' => 'Личный кабинет Praset.ru',
            'meta_keywords' => 'Личный кабинет Praset.ru',
            'meta_description' => 'Личный кабинет Praset.ru',
        ]));
    }
}