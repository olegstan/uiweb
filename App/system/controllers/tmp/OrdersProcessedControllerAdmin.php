<?php
namespace app\controllers;

use core\Controller;

class OrdersProcessedControllerAdmin extends Controller
{
    private $param_url, $params_arr, $options;

    public function set_params($url = null, $options = null)
    {
        $this->options = $options;

        //$url = urldecode(trim($url, '/'));
        $url = trim($url, '/');
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

    private function process_menu($item, $parent_id, &$menu)
    {
        if (!array_key_exists($parent_id, $menu))
            $menu[$parent_id] = array();
        $menu[$parent_id][] = intval($item['id']);
        if (isset($item['children']))
            foreach($item['children'] as $i)
                $this->process_menu($i, intval($item['id']), $menu);
    }

    function fetch()
    {
        if (!(isset($_SESSION['admin']) && $_SESSION['admin']=='admin'))
            header("Location: http://".$_SERVER['SERVER_NAME']."/admin/login/");

        $orders_filter = array('status_type' => 'processed');
        $current_page = 1;

        foreach($this->params_arr as $p=>$v)
        {
            switch ($p)
            {
                case "status_id":
                    if (!empty($this->params_arr[$p]))
                        $orders_filter[$p] = $this->params_arr[$p];
                    else
                        unset($this->params_arr[$p]);
                    break;
                case "keyword":
                    if (!empty($this->params_arr[$p]))
                    {
                        $orders_filter[$p] = $this->params_arr[$p];
                        $this->design->assign('keyword', $orders_filter['keyword']);
                    }
                    else
                        unset($this->params_arr[$p]);
                    break;
                case "page":
                    if (!empty($this->params_arr[$p]))
                        $current_page = intval($this->params_arr['page']);
                    else
                        unset($this->params_arr[$p]);
                    break;
            }
        }

        $orders_statuses = $this->orders->get_statuses(array('status_type'=>'processed'));
        foreach($orders_statuses as &$status)
            $status->orders_count = $this->orders->count_orders(array('status_id'=>$status->id, 'status_type'=>'processed'));

        /*Зададим статус по умолчанию*/
        /*if (!array_key_exists('status_id', $this->params_arr))
        {
            $this->params_arr['status_id'] = reset($orders_statuses)->id;
            $orders_filter['status_id'] = reset($orders_statuses)->id;
        }*/


        $this->design->assign('current_params', $this->params_arr);

        $all_orders_count = $this->orders->count_orders(array('status_type'=>'processed'));
        $orders_count = $this->orders->count_orders($orders_filter);

        // Постраничная навигация
        if (array_key_exists('page', $this->params_arr) && $this->params_arr['page'] == 'all')
            $items_per_page = $orders_count;
        else
            $items_per_page = $this->settings->products_num_admin;

        $this->design->assign('all_orders_count', $all_orders_count);
        $this->design->assign('orders_count', $orders_count);
        $pages_num = $items_per_page>0 ? ceil($orders_count/$items_per_page): 0;
        $this->design->assign('total_pages_num', $pages_num);

        // Если не задана, то равна 1
        $current_page = max(1, $current_page);
        $current_page = min($current_page, $pages_num);
        $this->design->assign('current_page_num', $current_page);

        $orders_filter['page'] = $current_page;
        $orders_filter['limit'] = $items_per_page;

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
        $lastweek = $todayday;
        $day_of_week = date("w");
        if ($day_of_week == 0)
            $day_of_week = 7;
        $lastweek->modify('-'.$day_of_week.' day');
        $lastweek->modify('-1 week');

        $orders = $this->orders->get_orders($orders_filter);
        foreach($orders as $index=>$order)
        {
            $orders[$index]->purchases = $this->orders->get_purchases(array('order_id'=>$order->id));
            $orders[$index]->user = $this->users->get_user($order->user_id);
            $orders[$index]->status = $this->orders->get_status($order->status_id);
            $orders[$index]->payment_method = $this->payment->get_payment_method($order->payment_method_id);

            $orders[$index]->total_price = 0;
            foreach($orders[$index]->purchases as $purchase)
                $orders[$index]->total_price += $purchase->amount * $purchase->price;
            if ($orders[$index]->discount_type == 0)
                $orders[$index]->total_price = $orders[$index]->total_price - $orders[$index]->total_price * $orders[$index]->discount / 100;
            else
                $orders[$index]->total_price = $orders[$index]->total_price - $orders[$index]->discount;
            if (!$orders[$index]->separate_delivery)
                $orders[$index]->total_price += $orders[$index]->delivery_price;

            if (date("Ymd", $orders[$index]->date) == $todayday->format("Ymd"))
                $orders[$index]->day_str = "Сегодня в " . date("H:i", $orders[$index]->date);
            else
                if (date("Ymd", $orders[$index]->date) == $yesterday->format("Ymd"))
                    $orders[$index]->day_str = "Вчера в " . date("H:i", $orders[$index]->date);
                else
                    if (date("Y", $orders[$index]->date) == date("Y"))
                    {
                        $order_date = new DateTime();
                        $order_date->setDate(date("Y", $orders[$index]->date), date("m", $orders[$index]->date), date("d", $orders[$index]->date));
                        if ($order_date > $lastweek)
                            $orders[$index]->day_str = $days_arr[date("l",$orders[$index]->date)] . " " . date("d.m", $orders[$index]->date) . " в " . date("H:i", $orders[$index]->date);
                        else
                            $orders[$index]->day_str = date("d.m", $orders[$index]->date) . " в " . date("H:i", $orders[$index]->date);
                    }
                    else
                        $orders[$index]->day_str = date("d.m.Y", $orders[$index]->date);
        }

        $this->design->assign('orders', $orders);
        $this->design->assign('orders_statuses', $orders_statuses);

        $this->design->assign('all_orders_statuses', $this->orders->get_statuses());

        $this->design->assign('params_arr', $this->params_arr);
        $this->design->assign('edit_module', $this->furl->get_module_by_name('OrderProcessedControllerAdmin'));
        $this->design->assign('product_module', $this->furl->get_module_by_name('ProductControllerAdmin'));

        return $this->design->fetch($this->design->getTemplateDir('admin').'orders.tpl');
    }
}