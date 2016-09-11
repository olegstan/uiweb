<?php
namespace Uiweb\Controller;

use Uiweb\Http\Header;
use Uiweb\Request\Types\HttpRequest;
use Uiweb\Request\Request;
use Uiweb\Response\Response;

abstract class ErrorController
{
    public static function responseError($status, $text)
    {
        switch(Request::getType()){
            case 'console':
                return Response::console($text, $status);
            case 'http':
                if(HttpRequest::isAjax()){
                    return Response::json($text, $status);
                }else{
                    return Response::html($text, $status);
                }
        }
    }
}