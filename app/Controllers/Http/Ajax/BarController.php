<?php
namespace App\Controllers\Http\Ajax;

use Framework\Auth\Session;
use App\Layers\LayerAjaxController;
use Framework\Response\Types\JsonResponse;

class BarController extends LayerAjaxController
{
    public function open()
    {
        Session::set('bar', 1);
        return new JsonResponse(['result' => 'success']);
    }

    public function close()
    {
        Session::delete('bar');
        return new JsonResponse(['result' => 'success']);
    }
}