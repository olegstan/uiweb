<?php
namespace Uiweb\Controller;

use Uiweb\Config;
use Uiweb\Http\Header;
use Uiweb\Request\Types\HttpRequest;
use Uiweb\Response\Types\HtmlResponse;
use Uiweb\Response\Types\JsonResponse;
use Uiweb\View\View;

class HttpController
{
    /**
     * @param $status
     * @param $text
     * @return HtmlResponse|JsonResponse
     */
    public static function responseError($status, $text)
    {
        if(HttpRequest::isAjax()){
            return new JsonResponse(['error' => $text], $status);
        }else{
            $class_name = Config::get('error.view');
            return new HtmlResponse(new $class_name('errors/' . $status . '.php', [
                'text' => $text
            ]), $status);
        }
    }

    /**
     * @param $status
     * @param $params
     * @return HtmlResponse|JsonResponse
     */
    public function __call($status, $params){
        $status = isset(Header::$statuses[$status]) ? $status : '500';
        $text = isset($params[0]) ? $params[0] : '';

        return self::responseError($status, $text);
    }

    /**
     * @param $status
     * @param $params
     * @return HtmlResponse|JsonResponse
     */
    public static function __callStatic($status, $params)
    {
        $status = isset(Header::$statuses[$status]) ? $status : '500';
        $text = isset($params[0]) ? $params[0] : '';

        return self::responseError($status, $text);
    }
}