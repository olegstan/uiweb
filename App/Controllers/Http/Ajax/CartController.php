<?php
namespace App\Controllers\Http\Ajax;

use App\Layers\LayerAjaxController;
use Framework\Cart\Cart;
use Framework\Response\Types\JsonResponse;

class CartController extends LayerAjaxController
{
    public function index()
    {

        return new JsonResponse([
            'result' => 'success'
        ]);
    }

    public function get($id)
    {
//        if(){
//
//        }
        return new JsonResponse([
            'result' => 'success',
            'data' => [

            ]
        ]);
    }

    public function add($id)
    {
        Cart::add($id);
        return new JsonResponse(['result' => 'success']);
    }

    public function delete($id)
    {
        Cart::delete($id);
        return new JsonResponse(['result' => 'success']);
    }

    public function change($id, $count)
    {
        //TODO int $count, int id
        Cart::change($id, $count);
        return new JsonResponse(['result' => 'success']);
    }

    public function clear()
    {
        Cart::clear();
        return new JsonResponse(['result' => 'success']);
    }
}