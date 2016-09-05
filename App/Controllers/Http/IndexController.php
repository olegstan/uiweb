<?php
namespace App\Controllers\Http;

use App\Layers\LayerHttpController;
use App\Models\Product\Product;
use Framework\Request\Types\HttpRequest;
use Framework\Response\Types\HtmlResponse;
use App\Layers\LayerView as View;

class IndexController extends LayerHttpController
{
    function index()
    {
        return HtmlResponse::html(new View('main.php', [
            'title' => 'Главная страница Praset.ru',
            'meta_keywords' => 'Главная страница Praset.ru',
            'meta_description' => 'Главная страница Praset.ru',
            'products' => (new Product())
                ->getQuery()
                ->with(['images'])
                ->select(['p.id', 'p.price', 'p.ipc2u_price', 'p.insat_price', 'p.url AS product_url', 'p.name', 'pc.url AS category_url'])
                ->from('products AS p')
                ->leftJoin('products_categories AS pc', 'p.category_id = pc.id')
                ->where('p.brand_id = :brand_id AND p.is_active = 1', [':brand_id' => 1])/*->limit(0, 30)*/
                ->execute()
                ->all()
                ->get(),
        ]));
    }

    public function about()
    {
        return HtmlResponse::html(new View('about.php', [
            'title' => 'О компании Praset.ru',
            'meta_keywords' => 'О компании Praset.ru',
            'meta_description' => 'О компании Praset.ru',
        ]));
    }

    public function typography()
    {
        return Response::html($this->render(TPL . '/' . $this->getCore()->tpl_path .'/html/typography.tpl'));
    }

    public function callback()
    {
        /*$user_id = isset($this->user) ? $this->user->id : 0;
        $phone = array_key_exists('phone_number', $this->params_arr) ? $this->params_arr['phone_number'] : '';
        $match_res = preg_match("/^[^\(]+\(([^\)]+)\).(.+)$/", $phone, $matches);
        $user_name = array_key_exists('user_name', $this->params_arr) ? $this->params_arr['user_name'] : '';
        $call_time = array_key_exists('call_time', $this->params_arr) ? $this->params_arr['call_time'] : '';
        $message = array_key_exists('message', $this->params_arr) ? $this->params_arr['message'] : '';
        $result = false;

        if ($match_res && count($matches) == 3) {
            $callback = new StdClass;
            $callback->user_id = $user_id;
            $callback->user_name = $user_name;
            $callback->phone_code = $matches[1];
            $callback->phone = str_replace("-", "", $matches[2]);
            $callback->call_time = $call_time;
            $callback->message = $message;
            $callback->ip = $_SERVER['REMOTE_ADDR'];

            $callback->id = $this->callbacks->add_callback($callback);

            //Отправляем письмо админку, т.к. у пользователя мы не знаем почту
            $this->notify_email->email_callback($callback->id);
            //Отправляем смс админу
            $this->notify_email->sms_callback($callback->id);

            //$this->db->query("INSERT INTO __callbacks(user_id, phone_code, phone, call_time, message) VALUES(?,?,?,?,?)", $user_id, $matches[1], str_replace("-","",$matches[2]), $call_time, $message);
            $result = true;
        }

        return Response::json($result);*/
    }
}