<?
namespace app\controllers\ajax;

use app\layer\LayerController;
use core\helper\Response;

class ErrorController extends LayerController
{
    public function __construct()
    {
        parent::__construct();
    }

    public $errors = [
        '401' => [
            'header' => 'Unauthorized'
        ],
        '403' => [
            'header' => 'Forbidden'
        ],
        '404' => [
            'header' => 'Not Found'
        ],
        '500' => [
            'header' => 'Internal Server Error'
        ],
    ];

    /**
     * @param $name
     * @param $params
     *
     * в качестве первого параметра можно
     * передать дополнительный тектс
     * вторым параметром передаётся title страницы
     *
     */

    public function __call($name, $params){
        $name = isset($this->errors[$name]) ? $name : '500';
        $this->getCore()->action_uri = $name;
        $this->getCore()->title = 'Ошибка ' . $name;

        header('HTTP/1.1 ' . $name . ' ' . $this->errors[$name]['header']);
        header('Status: ' . $name . ' ' . $this->errors[$name]['header']);

        $text = isset($params[0]) ? $params[0] : '';
        $this->getCore()->title = isset($params[1]) ? $params[1] : 'Ошибка ' . $name;

//        echo '<!-- ' . $text . ' -->';
//        echo '<!-- ' . TPL . '/modern/html/' . $name . '.tpl -->';

        return ['error' => $name];
    }
}