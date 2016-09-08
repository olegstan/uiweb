<?php
namespace Framework\Controller;

use Framework\Http\Header;
use Framework\Request\Types\HttpRequest;
use Framework\Request\Request;
use Framework\Response\Response;

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