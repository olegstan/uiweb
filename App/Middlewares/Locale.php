<?php
namespace App\Middlewares;

use Framework\Route\Middleware;
use Framework\Request\Interfaces\Requestable;
use Closure;

class Locale extends Middleware
{
    public function __handle(Requestable $request, Closure $callback)
    {
//        if($set_locale_res = setlocale(LC_ALL, 'ru_RU.UTF8')){
////            php_locale_collate = ru_RU;
////            php_locale_ctype = ru_RU;
////            php_locale_monetary = ru_RU;
////            php_locale_numeric = ru_RU;
////            php_locale_time = ru_RU;
//            return $request;
//        }else{
//            echo 'locale error';
//        }
    }
}