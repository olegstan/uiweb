<?php
namespace Framework\Controller;

use Framework\Config;
use Framework\Http\Header;
use Framework\Request\Types\HttpRequest;
use Framework\Response\Types\HtmlResponse;
use Framework\Response\Types\JsonResponse;
use Framework\View\View;

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
            $class_name = Config::get('error', 'view');
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