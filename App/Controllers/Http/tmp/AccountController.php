<?php
namespace app\controllers;

use app\layer\LayerController;

class AccountController extends LayerController
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
        if (!(isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])))
        {
            $login_module = $this->furl->get_module_by_name('LoginController');
            header("Location: ".$this->config->root_url.$login_module->url);
        }

        $ajax = false;
        $mode = "";
        $current_page = 1;
        foreach($this->params_arr as $p=>$v)
        {
            switch ($p)
            {
                case "mode":
                    $mode = $v;
                    break;
                case "ajax":
                    $ajax = true;
                    unset($v);
                    break;
                case "page":
                    $current_page = intval($v);
                    break;
            }
        }

        if (empty($mode))
        {
            $mode = 'account';
            $this->params_arr['mode'] = 'account';
        }
        $this->design->assign('mode', $mode);

        if ($this->request->method('post'))
        {
            switch($mode){
                case "delivery-address":
                    $address = $this->request->post('delivery_address');
                    $this->users->update_user($this->user->id, array('delivery_address'=>$address));
                    $this->user = $this->users->get_user($this->user->id);
                    $this->design->assign('user', $this->user);
                    break;
                case "personal-data":
                    $error_message = "";

                    $name = $this->request->post('name');
                    $email = $this->request->post('email');

                    $phone = $this->request->post('phone');
                    $match_res = preg_match("/^[^\(]+\(([^\)]+)\).(.+)$/", $phone, $matches);

                    $phone2 = $this->request->post('phone2');
                    $match2_res = preg_match("/^[^\(]+\(([^\)]+)\).(.+)$/", $phone2, $matches2);

                    $user_phone_code = "";
                    $user_phone = "";
                    $user_phone2_code = "";
                    $user_phone2 = "";

                    if ($match_res && count($matches) == 3)
                    {
                        $user_phone_code = $matches[1];
                        $user_phone = str_replace("-","",$matches[2]);
                    }

                    if ($match2_res && count($matches2) == 3)
                    {
                        $user_phone2_code = $matches2[1];
                        $user_phone2 = str_replace("-","",$matches2[2]);
                    }

                    $phone_filter = "";
                    $phone2_filter = "";
                    if (!empty($user_phone_code) && !empty($user_phone))
                        $phone_filter = $this->db->placehold("OR (phone_code=? AND phone=?) OR (phone2_code=? AND phone2=?)", $user_phone_code, $user_phone, $user_phone_code, $user_phone);
                    if (!empty($user_phone2_code) && !empty($user_phone2))
                        $phone2_filter = $this->db->placehold("OR (phone2_code=? AND phone2=?) OR (phone_code=? AND phone=?)", $user_phone2_code, $user_phone2, $user_phone2_code, $user_phone2);

                    //Проверим нет ли уже такого пользователя
                    $query = $this->db->placehold("SELECT COUNT(id) as kol FROM __access_users WHERE id<>? AND (email=? $phone_filter $phone2_filter)", $this->user->id, $email);
                    $this->db->query($query);
                    $count_users = $this->db->result('kol');

                    if (empty($email))
                        $error_message = "empty_email";
                    elseif (empty($name))
                        $error_message = "empty_name";
                    elseif ($count_users > 0)
                        $error_message = "user_exists";
                    else
                    {
                        $this->users->update_user($this->user->id, array('name'=>$name, 'email'=>$email, 'phone_code'=>$user_phone_code, 'phone'=>$user_phone, 'phone2_code'=>$user_phone2_code, 'phone2'=>$user_phone2));
                        $this->user = $this->users->get_user($this->user->id);
                        $this->design->assign('user', $this->user);
                    }

                    if (!empty($error_message))
                        $this->design->assign('error_message', $error_message);

                    break;
                case "change-password":
                    $error_message = "";
                    $success_message = "";
                    $old_password = $this->request->post('old_password');
                    $new_password1 = $this->request->post('new_password1');
                    $new_password2 = $this->request->post('new_password2');

                    if (!$this->users->check_password($this->user->email, $old_password))
                        $error_message = "old_password_error";
                    elseif(mb_strlen($new_password1, 'utf-8') < 6)
                        $error_message = "new_password_length";
                    elseif(empty($new_password1) || empty($new_password2) || $new_password1!=$new_password2)
                        $error_message = "new_password_error";
                    else
                    {
                        $this->users->update_user($this->user->id, array('password'=>$new_password1));
                        $success_message = "password_changed";
                    }
                    if (!empty($error_message))
                        $this->design->assign('error_message', $error_message);
                    if (!empty($success_message))
                        $this->design->assign('success_message', $success_message);
                    break;
                case "account-details":
                    $this->user->organization_name = $this->request->post('organization_name');
                    $this->user->yur_postcode = $this->request->post('yur_postcode');
                    $this->user->yur_city = $this->request->post('yur_city');
                    $this->user->yur_address = $this->request->post('yur_address');
                    $this->user->yur_inn = $this->request->post('yur_inn');
                    $this->user->yur_kpp = $this->request->post('yur_kpp');
                    $this->user->yur_bank_name = $this->request->post('yur_bank_name');
                    $this->user->yur_bank_city = $this->request->post('yur_bank_city');
                    $this->user->yur_bank_bik = $this->request->post('yur_bank_bik');
                    $this->user->yur_bank_corr_schet = $this->request->post('yur_bank_corr_schet');
                    $this->user->yur_bank_rasch_schet = $this->request->post('yur_bank_rasch_schet');
                    $this->users->update_user($this->user->id, array(
                        'organization_name'=>$this->user->organization_name,
                        'yur_postcode'=>$this->user->yur_postcode,
                        'yur_city'=>$this->user->yur_city,
                        'yur_address'=>$this->user->yur_address,
                        'yur_inn'=>$this->user->yur_inn,
                        'yur_kpp'=>$this->user->yur_kpp,
                        'yur_bank_name'=>$this->user->yur_bank_name,
                        'yur_bank_city'=>$this->user->yur_bank_city,
                        'yur_bank_bik'=>$this->user->yur_bank_bik,
                        'yur_bank_corr_schet'=>$this->user->yur_bank_corr_schet,
                        'yur_bank_rasch_schet'=>$this->user->yur_bank_rasch_schet,
                    ));
                    $this->user = $this->users->get_user($this->user->id);
                    $this->design->assign('user', $this->user);
                    break;
                case "notification-settings":
                    $this->users->update_user($this->user->id, array(
                        'mail_confirm'=>$this->request->post('mail_confirm'),
                        'sms_confirm'=>$this->request->post('sms_confirm')
                    ));
                    $this->user = $this->users->get_user($this->user->id);
                    $this->design->assign('user', $this->user);
                    break;
            }
        }
        else
            switch($mode){
                case "account":
                    $cancel_status_order = $this->orders->get_status('Отменены');

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

                    $orders = $this->orders->get_orders(array('user_id'=>$this->user->id, 'status_type'=>'processed'));
                    foreach($orders as $index=>$order)
                    {
                        $orders[$index]->day_str = date("d",$orders[$index]->date) . " " . $months_arr[date("n",$orders[$index]->date)] . " " . date("Y",$orders[$index]->date);
                        $orders[$index]->status = $this->orders->get_status($orders[$index]->status_id);
                        $orders[$index]->purchases = $this->orders->get_purchases(array('order_id'=>$order->id));
                        $orders[$index]->delivery_method = $this->deliveries->get_delivery($order->delivery_id);
                        $orders[$index]->payment_method = $this->payment->get_payment_method($order->payment_method_id);

                        if ($orders[$index]->purchases)
                        {
                            // Покупки
                            foreach($orders[$index]->purchases as $index2=>$purchase)
                            {
                                $orders[$index]->purchases[$index2]->product = $this->products->get_product($purchase->product_id);
                                $orders[$index]->purchases[$index2]->variants = $this->variants->get_variants(array('product_id'=>$purchase->product_id));
                                $orders[$index]->purchases[$index2]->variant = $this->variants->get_variant($purchase->variant_id);
                                $orders[$index]->purchases[$index2]->images = $this->image->get_images('products', $purchase->product_id);
                                $orders[$index]->purchases[$index2]->image = @reset($orders[$index]->purchases[$index2]->images);
                            }
                            $purchases_total = 0;
                            $purchases_count = 0;
                            foreach($orders[$index]->purchases as $purchase)
                            {
                                $purchases_total += $purchase->price*$purchase->amount;
                                $purchases_count += $purchase->amount;
                            }
                        }

                        $orders[$index]->total_price = 0;
                        foreach($orders[$index]->purchases as $purchase)
                            $orders[$index]->total_price += $purchase->amount * $purchase->price;
                        $orders[$index]->total_price = $orders[$index]->total_price - $orders[$index]->total_price * $orders[$index]->discount / 100;
                        if (!$orders[$index]->separate_delivery)
                            $orders[$index]->total_price += $orders[$index]->delivery_price;
                    }
                    $this->design->assign('orders', $orders);
                    break;
                case "my-orders":
                    $orders_filter = array('user_id'=>$this->user->id);
                    // Если не задана, то равна 1
                    $current_page = max(1, $current_page);
                    $this->design->assign('current_page_num', $current_page);

                    $orders_count = $this->orders->count_orders($orders_filter);

                    // Постраничная навигация
                    if (array_key_exists('page', $this->params_arr) && $this->params_arr['page'] == 'all')
                        $items_per_page = $orders_count;
                    else
                        $items_per_page = $this->settings->products_num_admin;
                    $this->design->assign('orders_count', $orders_count);
                    $pages_num = ceil($orders_count/$items_per_page);
                    $this->design->assign('total_pages_num', $pages_num);

                    $orders_filter['page'] = $current_page;
                    $orders_filter['limit'] = $items_per_page;

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

                    $orders = $this->orders->get_orders($orders_filter);
                    foreach($orders as $index=>$order)
                    {
                        $orders[$index]->day_str = date("d",$orders[$index]->date) . " " . $months_arr[date("n",$orders[$index]->date)] . " " . date("Y",$orders[$index]->date);
                        $orders[$index]->status = $this->orders->get_status($orders[$index]->status_id);
                        $orders[$index]->purchases = $this->orders->get_purchases(array('order_id'=>$order->id));
                        $orders[$index]->delivery_method = $this->deliveries->get_delivery($order->delivery_id);
                        $orders[$index]->payment_method = $this->payment->get_payment_method($order->payment_method_id);

                        if ($orders[$index]->purchases)
                        {
                            // Покупки
                            foreach($orders[$index]->purchases as $index2=>$purchase)
                            {
                                $orders[$index]->purchases[$index2]->product = $this->products->get_product($purchase->product_id);
                                $orders[$index]->purchases[$index2]->variants = $this->variants->get_variants(array('product_id'=>$purchase->product_id));
                                $orders[$index]->purchases[$index2]->variant = $this->variants->get_variant($purchase->variant_id);
                                $orders[$index]->purchases[$index2]->images = $this->image->get_images('products', $purchase->product_id);
                                $orders[$index]->purchases[$index2]->image = @reset($orders[$index]->purchases[$index2]->images);
                            }
                            $purchases_total = 0;
                            $purchases_count = 0;
                            foreach($orders[$index]->purchases as $purchase)
                            {
                                $purchases_total += $purchase->price*$purchase->amount;
                                $purchases_count += $purchase->amount;
                            }
                        }

                        $orders[$index]->total_price = 0;
                        foreach($orders[$index]->purchases as $purchase)
                            $orders[$index]->total_price += $purchase->amount * $purchase->price;
                        $orders[$index]->total_price = $orders[$index]->total_price - $orders[$index]->total_price * $orders[$index]->discount / 100;
                        if (!$orders[$index]->separate_delivery)
                            $orders[$index]->total_price += $orders[$index]->delivery_price;
                    }

                    $this->design->assign('orders', $orders);
                    $this->design->assign('data_type', 'account_orders');
                    break;
                case "my-reviews":
                    $reviews_filter = array('user_id'=>$this->user->id, 'sort'=>'date');
                    // Если не задана, то равна 1
                    $current_page = max(1, $current_page);
                    $this->design->assign('current_page_num', $current_page);

                    $reviews_count = $this->reviews->count_reviews(array('user_id'=>$this->user->id));
                    $this->design->assign('reviews_count', $reviews_count);

                    // Постраничная навигация
                    if (array_key_exists('page', $this->params_arr) && $this->params_arr['page'] == 'all')
                        $items_per_page = $orders_count;
                    else
                        $items_per_page = 10;//$this->settings->products_num_admin;
                    $pages_num = ceil($reviews_count/$items_per_page);
                    $this->design->assign('total_pages_num', $pages_num);

                    $reviews_filter['page'] = $current_page;
                    $reviews_filter['limit'] = $items_per_page;

                    $reviews = $this->reviews->get_reviews($reviews_filter);

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
                    foreach($reviews as $index=>$review)
                    {
                        if (date("Ymd", $reviews[$index]->datetime) == $todayday->format("Ymd"))
                            $reviews[$index]->day_str = "Сегодня в " . date("H:i", $reviews[$index]->datetime);
                        else
                            if (date("Ymd", $reviews[$index]->datetime) == $yesterday->format("Ymd"))
                                $reviews[$index]->day_str = "Вчера в " . date("H:i", $reviews[$index]->datetime);
                            else
                                if (date("Y", $reviews[$index]->datetime) == date("Y"))
                                    $reviews[$index]->day_str = date("d",$reviews[$index]->datetime) . " " . $months_arr[date("n",$reviews[$index]->datetime)] . " в " . date("H:i", $reviews[$index]->datetime);
                                else
                                    $reviews[$index]->day_str = date("d",$reviews[$index]->datetime) . " " . $months_arr[date("n",$reviews[$index]->datetime)] . " " . date("Y",$reviews[$index]->datetime) . " в " . date("H:i", $reviews[$index]->datetime);

                        $reviews[$index]->product = $this->products->get_product($review->product_id);
                        $reviews[$index]->product_rating = $this->reviews->calc_product_rating($review->product_id);
                        $reviews[$index]->product_images = $this->image->get_images('products', $review->product_id);
                        $reviews[$index]->product_image = @reset($reviews[$index]->product_images);
                        $reviews[$index]->images = $this->image->get_images('reviews', $review->id);
                    }

                    $this->design->assign('reviews', $reviews);
                    $this->design->assign('data_type', 'account_reviews');
                    break;
                case "delete-review":
                    $id = intval($this->params_arr['id']);
                    $result = false;
                    if (!empty($id)){
                        $review = $this->reviews->get_review($id);
                        if ($review->user_id == $this->user->id){
                            $this->reviews->delete_review($id);
                            $result = true;
                        }
                    }
                    header("Content-type: application/json; charset=UTF-8");
                    header("Cache-Control: must-revalidate");
                    header("Pragma: no-cache");
                    header("Expires: -1");
                    print json_encode(array('success'=>$result));
                    die();
                    break;
                case "my-favorites":
                    $filter['id'] = $this->products->get_favorites_products($this->user->id);
                    $filter['is_visible'] = 1;
                    $filter['limit'] = 10000;
                    if ($this->settings->catalog_show_all_products)
                        $filter['in_stock'] = 1;

                    $products = array();
                    $categories = array();
                    if (!empty($filter['id']))
                    {
                        $products = $this->products->get_products($filter);
                        $products = $this->products->get_data_for_frontend_products($products);

                        foreach($products as $product)
                        {
                            $pcats = $this->categories->get_product_categories($product->id);
                            $category_id = $pcats[0]->category_id;
                            if (!array_key_exists($category_id, $categories))
                            {
                                $categories[$category_id] = $this->categories->get_category($category_id);
                                $categories[$category_id]->products = array();
                            }
                            $categories[$category_id]->products[] = $product;
                        }
                    }
                    $this->design->assign('products', $products);
                    $this->design->assign('categories', $categories);
                    $this->design->assign('show_mode', $this->settings->default_show_mode);
                    break;
            }

        if ($ajax)
        {
            $response = array('success'=>true, 'data'=>$this->design->fetch($this->design->getTemplateDir('frontend').'account/'.$mode.'.tpl'));
            header("Content-type: application/json; charset=UTF-8");
            header("Cache-Control: must-revalidate");
            header("Pragma: no-cache");
            header("Expires: -1");
            print json_encode($response);
            die();
        }

        $this->design->assign('all_orders_count', $this->orders->count_orders(array('user_id'=>$this->user->id)));

        $this->design->assign('current_params', $this->params_arr);
        $this->design->assign('params_arr', $this->params_arr);

        $title = "";
        switch($mode){
            case "account":
                $title = "Личный кабинет";
                break;
            case "my-orders":
                $title = "Мои заказы";
                break;
            case "notification-settings":
                $title = "E-mail и SMS оповещения";
                break;
            case "personal-data":
                $title = "Личные данные";
                break;
            case "delivery-address":
                $title = "Адреса доставки";
                break;
            case "account-details":
                $title = "Реквизиты для счетов";
                break;
            case "change-password":
                $title = "Смена пароля";
                break;
            case "my-favorites":
                $title = "Избранные товары (".$this->products->count_favorites_products($this->user->id).")";
                break;
        }

        $this->design->assign('meta_title', $title);
        $this->design->assign('meta_description', $title);
        $this->design->assign('meta_keywords', $title);

        return $this->design->fetch($this->design->getTemplateDir('frontend').'account.tpl');
    }
}