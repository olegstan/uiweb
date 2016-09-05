<?php
namespace app\controllers;

use core\Controller;

class UserControllerAdmin extends Controller
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

        $edit_module = $this->furl->get_module_by_name('UserControllerAdmin');
        $main_module =  $this->furl->get_module_by_name('UsersControllerAdmin');
        $edit_order_module = $this->furl->get_module_by_name('OrderControllerAdmin');
        $this->design->assign('main_module', $main_module);
        $this->design->assign('edit_order_module', $edit_order_module);

        if ($this->request->method('post') && !isset($_FILES['uploaded-images']))
        {
            $user = new stdClass();
            $user->id = $this->request->post('id', 'integer');
            $user->group_id = $this->request->post('group_id');
            $user->name = $this->request->post('name');
            $pwd = $this->request->post('password');
            if (!empty($pwd))
                $user->password = $this->request->post('password');
            $user->email = $this->request->post('email');
            $user->is_enabled = $this->request->post('is_enabled', 'boolean');
            $user->mail_confirm = $this->request->post('mail_confirm', 'boolean');
            $user->sms_confirm = $this->request->post('sms_confirm', 'boolean');

            $phone = $this->request->post('phone');
            $match_res = preg_match("/^[^\(]+\(([^\)]+)\).(.+)$/", $phone, $matches);

            $phone2 = $this->request->post('phone2');
            $match2_res = preg_match("/^[^\(]+\(([^\)]+)\).(.+)$/", $phone2, $matches2);

            if ($match_res && count($matches) == 3)
            {
                $user->phone_code = $matches[1];
                $user->phone = str_replace("-","",$matches[2]);
            }

            if ($match2_res && count($matches2) == 3)
            {
                $user->phone2_code = $matches2[1];
                $user->phone2 = str_replace("-","",$matches2[2]);
            }

            /*$user->phone_code = $this->request->post('phone_code');
            $user->phone = $this->request->post('phone');
            $user->phone2_code = $this->request->post('phone2_code');
            $user->phone2 = $this->request->post('phone2');*/
            $user->delivery_address = $this->request->post('delivery_address');

            $close_after_save = $this->request->post('close_after_save', 'integer');
            $add_after_save = $this->request->post('add_after_save', 'integer');

            $phone_filter = "";
            $phone2_filter = "";
            if (!empty($user->phone_code) && !empty($user->phone))
                $phone_filter = $this->db->placehold("OR (phone_code=? AND phone=?)", $user->phone_code, $user->phone);
            if (!empty($user->phone2_code) && !empty($user->phone2))
                $phone2_filter = $this->db->placehold("OR (phone2_code=? AND phone2=?)", $user->phone2_code, $user->phone2);

            //Проверим нет ли уже такого пользователя
            $count_users_name = 0;
            /*if (!empty($user->name))
            {
                $query = $this->db->placehold("SELECT COUNT(id) as kol FROM __access_users WHERE name=? AND id<>?", $user->name, $user->id);
                $this->db->query($query);
                $count_users_name = $this->db->result('kol');
            }*/

            $count_users_email = 0;
            if (!empty($user->email))
            {
                $query = $this->db->placehold("SELECT COUNT(id) as kol FROM __access_users WHERE email=? AND id<>?", $user->email, $user->id);
                $this->db->query($query);
                $count_users_email = $this->db->result('kol');
            }

            $count_users_phone = 0;
            if (!empty($phone_filter) || !empty($phone2_filter))
            {
                $query = $this->db->placehold("SELECT COUNT(id) as kol FROM __access_users WHERE 0 $phone_filter $phone2_filter AND id<>?", $user->id);
                $this->db->query($query);
                $count_users_phone = $this->db->result('kol');
            }

             if(empty($user->id))
            {
                if (($count_users_name + $count_users_email + $count_users_phone) == 0)
                {
                    $user->id = @$this->users->add_user($user);
                    if ($user->id)
                        $this->design->assign('message_success', 'added');
                    else
                        $this->design->assign('message_error', 'error');
                }
                else
                {
                    $this->design->assign('message_error', 'user_already_exists');
                    $this->design->assign('user_item', $user);
                    $this->design->assign('count_users_name', $count_users_name);
                    $this->design->assign('count_users_email', $count_users_email);
                    $this->design->assign('count_users_phone', $count_users_phone);
                }
            }
            else
            {
                if (($count_users_name + $count_users_email + $count_users_phone) == 0)
                {
                    $this->users->update_user($user->id, $user);
                    $this->design->assign('message_success', 'updated');
                }
                else
                {
                    $this->design->assign('message_error', 'user_already_exists');
                    $this->design->assign('user_item', $user);
                    $this->design->assign('count_users_name', $count_users_name);
                    $this->design->assign('count_users_email', $count_users_email);
                    $this->design->assign('count_users_phone', $count_users_phone);
                }
            }
            $user = $this->users->get_user(intval($user->id));

            $return_group_id = $this->request->post('return_group_id');

            if ($close_after_save && $main_module)
                header("Location: ".$this->config->root_url.$main_module->url.$this->design->url_modifier(array('add'=>array('group_id'=>$return_group_id))));

            if ($add_after_save)
                header("Location: ".$this->config->root_url.$edit_module->url.$this->design->url_modifier(array('add'=>array('group_id'=>$return_group_id))));
        }
        else
        {
            $id = 0;
            $mode = "";
            $response['success'] = false;
            $json_answer = false;
            foreach($this->params_arr as $p=>$v)
            {
                switch ($p)
                {
                    case "id":
                        if (is_numeric($v))
                            $id = intval($v);
                        break;
                    case "mode":
                        $mode = strval($v);
                        break;
                    case "ajax":
                        $json_answer = true;
                        unset($this->params_arr[$p]);
                        break;
                    case "group_id":
                        $this->design->assign('group_id', intval($v));
                        break;
                }
            }

            if (!empty($id))
                $user = $this->users->get_user($id);

            if (!empty($mode) && $user)
                switch($mode){
                    case "delete":
                        $this->users->delete_user($id);
                        $response['success'] = true;
                        break;
                    case "toggle":
                        $this->users->update_user($id, array('is_enabled'=>1-$user->is_enabled));
                        $response['success'] = true;
                        break;
                }

            if ($json_answer)
            {
                header("Content-type: application/json; charset=UTF-8");
                header("Cache-Control: must-revalidate");
                header("Pragma: no-cache");
                header("Expires: -1");
                print json_encode($response);
                die();
            }
        }

        if (isset($user) && !empty($user))
        {
            $this->design->assign('user_item', $user);

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

            $orders_filter = array('user_id'=>$user->id);
            $current_page = 1;
            if (!empty($this->params_arr['page']))
                $current_page = intval($this->params_arr['page']);
            $current_page = max(1, $current_page);
            $this->design->assign('current_page_num', $current_page);

            $orders_count = $this->orders->count_orders($orders_filter);
            $this->design->assign('orders_count', $orders_count);
            // Постраничная навигация
            if (array_key_exists('page', $this->params_arr) && $this->params_arr['page'] == 'all')
                $items_per_page = $orders_count;
            else
                $items_per_page = $this->settings->products_num_admin;
            $pages_num = ceil($orders_count/$items_per_page);
            $this->design->assign('total_pages_num', $pages_num);

            $orders_filter['page'] = $current_page;
            $orders_filter['limit'] = $items_per_page;

            $orders = $this->orders->get_orders($orders_filter);
            foreach($orders as $index=>$order)
            {
                $orders[$index]->purchases = $this->orders->get_purchases(array('order_id'=>$order->id));

                $orders[$index]->total_price = 0;
                foreach($orders[$index]->purchases as $purchase)
                    $orders[$index]->total_price += $purchase->amount * $purchase->price;
                $orders[$index]->total_price = $orders[$index]->total_price - $orders[$index]->total_price * $orders[$index]->discount / 100;
                if (!$orders[$index]->separate_delivery)
                    $orders[$index]->total_price += $orders[$index]->delivery_price;

                if (date("Ymd", $orders[$index]->date) == $todayday->format("Ymd"))
                    $orders[$index]->day_str = "Сегодня";
                else
                    if (date("Ymd", $orders[$index]->date) == $yesterday->format("Ymd"))
                        $orders[$index]->day_str = "Вчера";
                    else
                        $orders[$index]->day_str = $days_arr[date("l",$orders[$index]->date)];
            }
            $this->design->assign('orders', $orders);
        }
        $this->design->assign('current_params', $this->params_arr);
        $this->design->assign('params_arr', $this->params_arr);
        $this->design->assign('user_groups', $this->users->get_groups());
        return $this->design->fetch($this->design->getTemplateDir('admin').'user.tpl');
    }
}