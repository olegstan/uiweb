<?php
namespace app\controllers;

use core\Controller;

class ContactsOrdersCallsControllerAdmin extends Controller
{
    private $param_url, $params_arr, $options;

    public function set_params($url = null, $options = null)
    {
        $this->options = $options;

        $url = urldecode(trim($url, '/'));
        $delim_pos = mb_strpos($url, '?', 0, 'utf-8');

        if ($delim_pos === false)
        {
            $this->param_url = $url;
            $this->params_arr = array();
        }
        else
        {
            $this->param_url = trim(mb_substr($url, 0, $delim_pos, 'utf-8'), '/');
            $url = mb_substr($url, $delim_pos+1, mb_strlen($url, 'utf-8')-($delim_pos+1), 'utf-8');
            $this->params_arr = array();
            foreach(explode("&", $url) as $p)
            {
                $x = explode("=", $p);
                $this->params_arr[$x[0]] = "";
                if (count($x)>1)
                    $this->params_arr[$x[0]] = $x[1];
            }
        }
    }

    function fetch()
    {
        if (!(isset($_SESSION['admin']) && $_SESSION['admin']=='admin'))
            header("Location: http://".$_SERVER['SERVER_NAME']."/admin/login/");

        $filter = array();
        $filter['limit'] = 10000;
        $current_page = 1;
        $id = 0;
        $mode = "";

        foreach($this->params_arr as $p=>$v)
        {
            switch ($p)
            {
                case "keyword":
                    if (!empty($v))
                    {
                        $filter[$p] = $v;
                        $this->design->assign('keyword', $filter[$p]);
                    }
                    else
                        unset($this->params_arr[$p]);
                    break;
                case "id":
                    if (is_numeric($v))
                        $id = intval($v);
                    break;
                case "mode":
                    $mode = strval($v);
                    break;
                case "page":
                    if (!empty($this->params_arr[$p]))
                        $current_page = intval($this->params_arr[$p]);
                    else
                        unset($this->params_arr[$p]);
                    break;
            }
        }

        if (!empty($id) && !empty($mode))
            switch($mode){
                case "delete":
                    $this->callbacks->delete_callback($id);
                    header("Content-type: application/json; charset=UTF-8");
                    header("Cache-Control: must-revalidate");
                    header("Pragma: no-cache");
                    header("Expires: -1");
                    print json_encode(array('success'=>true));
                    die();
                    break;
                case "toggle":
                    $callback = $this->callbacks->get_callback($id);
                    if ($callback)
                    {
                        $this->callbacks->update_callback($id, array('state'=>1 - $callback->state));
                        $response['success'] = true;
                        $response['state'] = 1 - $callback->state;
                    }
                    else
                        $response['success'] = false;
                    header("Content-type: application/json; charset=UTF-8");
                    header("Cache-Control: must-revalidate");
                    header("Pragma: no-cache");
                    header("Expires: -1");
                    print json_encode($response);
                    die();
                    break;
            }

        $this->design->assign('current_params', $this->params_arr);

        // Если страница не задана, то равна 1
        $current_page = max(1, $current_page);
        $this->design->assign('current_page_num', $current_page);

        $callbacks_count = $this->callbacks->count_callbacks($filter);

        // Постраничная навигация
        if (array_key_exists('page', $this->params_arr) && $this->params_arr['page'] == 'all')
            $items_per_page = $callbacks_count;
        else
            $items_per_page = $this->settings->products_num_admin;

        $filter['page'] = $current_page;
        $filter['limit'] = $items_per_page;

        $todayday = new DateTime();
        $yesterday = new DateTime();
        $yesterday->modify('-1 day');
        $days_arr = array('Monday' => 'Пн',
                            'Tuesday' => 'Вт',
                            'Wednesday' => 'Ср',
                            'Thursday' => 'Чт',
                            'Friday' => 'Пт',
                            'Saturday' => 'Сб',
                            'Sunday' => 'Вс');
        $months_arr = array(1 => 'января',
                            2 => 'февраля',
                            3 => 'марта',
                            4 => 'апреля',
                            5 => 'мая',
                            6 => 'июня',
                            7 => 'июля',
                            8 => 'августа',
                            9 => 'сентября',
                            10 => 'октября',
                            11 => 'ноября',
                            12 => 'декабря');

        $callbacks = $this->callbacks->get_callbacks($filter);
        foreach($callbacks as $index=>$callback)
        {
            if ($callback->user_id > 0)
                $callbacks[$index]->user = $this->users->get_user($callback->user_id);

            if (date("Ymd", $callbacks[$index]->created) == $todayday->format("Ymd"))
                $callbacks[$index]->day_str = "Сегодня";
            else
                if (date("Ymd", $callbacks[$index]->created) == $yesterday->format("Ymd"))
                    $callbacks[$index]->day_str = "Вчера";
                else
                    $callbacks[$index]->day_str = date("d",$callbacks[$index]->created) . " " . $months_arr[date("n",$callbacks[$index]->created)] . " " . date("Y",$callbacks[$index]->created);
        }

        $this->design->assign('params_arr', $this->params_arr);
        $this->design->assign('callbacks_count', $callbacks_count);
        $pages_num = $items_per_page>0 ? ceil($callbacks_count/$items_per_page): 0;
        $this->design->assign('total_pages_num', $pages_num);

        $this->design->assign('callbacks', $callbacks);
        $this->design->assign('user_module', $this->furl->get_module_by_name('UserControllerAdmin'));

        return $this->design->fetch($this->design->getTemplateDir('admin').'contacts-orders-calls.tpl');
    }
}