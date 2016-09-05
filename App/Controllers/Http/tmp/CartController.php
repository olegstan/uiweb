<?php
namespace app\controllers;

use app\layer\LayerController;

class CartController extends LayerController
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
                if (array_key_exists($x[0], $this->params_arr) && count($x)>1)
                {
                    $this->params_arr[$x[0]] = (array) $this->params_arr[$x[0]];
                    $this->params_arr[$x[0]][] = $x[1];
                }
                else
                {
                    $this->params_arr[$x[0]] = "";
                    if (count($x)>1)
                        $this->params_arr[$x[0]] = $x[1];
                }
            }
        }
    }

    private function recreate_product_autotags($product, $variants){
        //Проставим автотеги цены и наличия
        $this->db->query("SELECT id FROM __tags_groups WHERE name=? AND is_auto=?", "Цена", 1);
        $price_group_id = $this->db->result('id');
        $this->db->query("SELECT id FROM __tags_groups WHERE name=? AND is_auto=?", "Есть в наличии", 1);
        $stock_group_id = $this->db->result('id');

        $this->db->query("SELECT tp.tag_id
                FROM __tags_products tp
                INNER JOIN __tags t ON tp.tag_id = t.id
            WHERE tp.product_id=? AND t.is_auto=1 AND t.group_id in (?,?)", $product->id, $price_group_id, $stock_group_id);
        $autotags_ids = $this->db->results('tag_id');
        if (!empty($autotags_ids))
            $this->db->query("DELETE FROM __tags_products WHERE product_id=? AND tag_id IN (?@)", $product->id, $autotags_ids);

        $in_stock = false;
        $in_order = false;
        if(is_array($variants))
            foreach($variants as $variant)
            {
                if (!$variant->is_visible)
                    continue;
                if ($variant->stock > 0)
                    $in_stock = true;
                if ($variant->stock < 0)
                    $in_order = true;
                $tag_id = 0;
                if ($tag = $this->tags->get_tags(array('group_id'=>$price_group_id,'name'=>$this->currencies->convert($variant->price, empty($product->currency_id) ? $this->admin_currency->id : $product->currency_id, false))))
                {
                    $tag_id = intval(reset($tag)->id);
                }
                else
                {
                    $tag = new StdClass;
                    $tag->group_id = $price_group_id;
                    $tag->name = $this->currencies->convert($variant->price, empty($product->currency_id) ? $this->admin_currency->id : $product->currency_id, false);
                    $tag->is_enabled = 1;
                    $tag->is_auto = 1;
                    $tag->id = $this->tags->add_tag($tag);

                    $tag_id = $tag->id;
                }
                $this->tags->add_product_tag($product->id, $tag_id);
            }
        if ($in_stock)
            $in_stock_text = "да";
        else
            if ($in_order)
                $in_stock_text = "под заказ";
            else
                $in_stock_text = "нет";
        $tag_id = 0;
        if ($tag = $this->tags->get_tags(array('group_id'=>$stock_group_id,'name'=>$in_stock_text)))
            $tag_id = intval(reset($tag)->id);
        else
        {
            $tag = new StdClass;
            $tag->group_id = $stock_group_id;
            $tag->name = $in_stock_text;
            $tag->is_enabled = 1;
            $tag->is_auto = 1;
            $tag->id = $this->tags->add_tag($tag);

            $tag_id = $tag->id;
        }
        $this->tags->add_product_tag($product->id, $tag_id);
    }

    function fetch()
    {
        $variant_id = 0;
        $ajax = false;
        $action = "";
        $format = "";
        $ajax_additional_result = "";
        foreach($this->params_arr as $p=>$v)
        {
            switch ($p)
            {
                case "ajax":
                    $ajax = true;
                    unset($this->params_arr[$p]);
                    break;
                case "action":
                    $action = $this->params_arr[$p];
                    unset($this->params_arr[$p]);
                    break;
                case "variant_id":
                    $variant_id = intval($this->params_arr[$p]);
                    break;
                case "format":
                    $format = $this->params_arr[$p];
                    break;
            }
        }

        if ($this->request->method('post'))
        {
            //$variant_id = $this->request->post('variant_id', 'integer');
            $is_one_click = $this->request->post('oneclickbuy', 'integer');
            $phone = $this->request->post('phone_number');
            $match_res = preg_match("/^[^\(]+\(([^\)]+)\).(.+)$/", $phone, $matches);

            if ($match_res && count($matches) == 3 && /*$variant_id > 0 &&*/ $is_one_click)
            {
                $order = new stdClass();
                $datetime = new DateTime();

                $order->status_id = 1;
                $order->discount = 0;
                $order->date = $datetime->format('Y-m-d G:i');
                $order->email = '';
                $order->phone_code = $matches[1];
                $order->phone = str_replace("-","",$matches[2]);
                if ($this->settings->cart_one_click_show_comment)
                    $order->comment = $this->request->post('comment');
                else
                    $order->comment = '';
                $order->buy1click = 1;
                $order->user_id = 0;
                $order->ip = $_SERVER['REMOTE_ADDR'];

                $order->id = $this->orders->add_order($order);
                if ($order->id)
                {
                    $cart = $this->cart->get_cart();

                    foreach($cart->purchases as $purchase){
                        $this->orders->add_purchase(array('order_id' => $order->id, 'variant_id' => $purchase->variant->id, 'amount' => $purchase->amount, 'var_amount' => $purchase->var_amount, 'price' => $purchase->variant->price_for_one_pcs, 'price_for_mul' => $purchase->variant->price_for_mul, 'additional_sum' => $purchase->variant->additional_sum, 'modificators' => $purchase->modificators, 'modificators_count' => $purchase->modificators_count));

                        if ($purchase->variant->stock > 0 && !$purchase->variant->infinity){
                            if ($purchase->variant->stock - $purchase->amount == 0)
                            {
                                $this->variants->update_variant($purchase->variant->id, array('stock' => $this->settings->new_status_when_product_is_out_of_stock));

                                $tmp_product = $this->products->get_product($purchase->variant->product_id);
                                $tmp_variants = $this->variants->get_variants(array('product_id'=>$tmp_product->id));
                                $this->recreate_product_autotags($tmp_product, $tmp_variants);
                            }
                            else
                                $this->variants->update_variant($purchase->variant->id, array('stock' => ($purchase->variant->stock - $purchase->amount)));
                        }
                    }

                    //$this->orders->add_purchase(array('order_id'=>$order->id, 'variant_id'=>$variant_id, 'amount'=>1));

                    $this->cart->empty_cart();

                    //Отправляем письмо админу, т.к. у пользователя мы не знаем почту
                    $this->notify_email->email_new_order_admin($order->id);

                    //Отправляем смс пользователю
                    $this->notify_email->sms_new_order($order->id);
                    //Отправляем смс админу
                    $this->notify_email->sms_new_order_admin($order->id);

                    $order_module = $this->furl->get_module_by_name('OrderController');
                    header('Location: '.$this->config->root_url.$order_module->url.$order->url.'/');
                }
            }
        }

        switch($action){
            case "add_variant":
                $amount = floatval($this->params_arr['amount']);
                $var_amount = floatval($this->params_arr['var_amount']);
                $modificators = array();
                $modificators_count = array();
                if (array_key_exists('modificators', $this->params_arr))
                    $modificators = strval($this->params_arr['modificators']);
                if (array_key_exists('modificators_count', $this->params_arr))
                    $modificators_count = strval($this->params_arr['modificators_count']);
                if (!empty($modificators))
                    $modificators = explode(',', $modificators);
                if (!empty($modificators_count))
                    $modificators_count = explode(',', $modificators_count);
                if (!empty($variant_id) && !empty($amount))
                {
                    $variant = $this->variants->get_variant($variant_id);
                    $product = $this->products->get_product($variant->product_id);
                    $product->images = $this->image->get_images('products', $product->id);
                    $product->image = reset($product->images);

                    $this->design->assign('added_variant', $variant);
                    $this->design->assign('added_product', $product);

                    if ($this->cart->add_item($variant_id, $amount, $var_amount, $modificators, $modificators_count) == 0)
                    {
                        $cart = $this->cart->get_cart();
                        $this->design->assign('cart', $cart);

                        $ajax_additional_result = array(
                            "message" => "no_stock",
                            "max_stock" => $variant->stock > 0 ? $variant->stock : ($variant->stock == -1 ? 999 : 0),
                            "added_form" => $this->design->fetch($this->design->getTemplateDir('frontend').'cart-after-buy-form.tpl'),
                            "modal_header" => $amount . " " . $this->design->plural_modifier($amount, 'товар', 'товаров', 'товара') . " " . $this->design->plural_modifier($amount, 'добавлен', 'добавлено') . " в корзину");
                    }
                    else
                    {
                        $cart = $this->cart->get_cart();
                        $this->design->assign('cart', $cart);
                        $this->design->assign('added_purchase', $cart->purchases[count($cart->purchases)-1]);

                        $ajax_additional_result = array(
                            "added_form" => $this->design->fetch($this->design->getTemplateDir('frontend').'cart-after-buy-form.tpl'),
                            "modal_header" => $amount . " " . $this->design->plural_modifier($amount, 'товар', 'товаров', 'товара') . " " . $this->design->plural_modifier($amount, 'добавлен', 'добавлено') . " в корзину");
                    }
                }
                $ajax = true;
                $format = "info";
                break;
            case "remove_variant":
                if (isset($variant_id))
                    $this->cart->remove_item($variant_id);
                $ajax = true;
                $format = "info";
                break;
            case "get_payment_methods":
                $ajax = true;
                $format = "payment_methods";
                break;
            case "set_amount":
                $amount = intval($this->params_arr['amount']);
                if (isset($variant_id) && !empty($amount))
                    $this->cart->update_item($variant_id, $amount);
                $ajax = true;
                $format = "info";
                break;
            case "save_order":
                $order = new stdClass();
                $order->status_id = 1;
                $order->name = $this->request->post('name');
                $order->discount = isset($this->user) ? $this->user->discount : 0;
                $order->delivery_id = $this->request->post('delivery_id');
                $order->notify_order = $this->request->post('notify_order');
                $order->notify_news = $this->request->post('notify_news');
                $delivery = $this->deliveries->get_delivery($order->delivery_id);
                if ($delivery->delivery_type == 'paid')
                    $order->delivery_price = $delivery->price;
                else
                    $order->delivery_price = 0;
                $order->payment_method_id = $this->request->post('payment_method_id');
                $payment_method = $this->payment->get_payment_method($order->payment_method_id);
                if (!$payment_method->allow_payment)
                    $order->allow_payment = 0;

                $order->separate_delivery = 0;
                if ($delivery->delivery_type == 'separately')
                    $order->separate_delivery = 1;
                $order->paid = 0;

                $datetime = new DateTime();
                $order->date = $datetime->format('Y-m-d G:i');

                $order->email = $this->request->post('email');

                $phone = $this->request->post('phone');
                $match_res = preg_match("/^[^\(]+\(([^\)]+)\).(.+)$/", $phone, $matches);

                $phone2 = $this->request->post('phone2');
                $match2_res = preg_match("/^[^\(]+\(([^\)]+)\).(.+)$/", $phone2, $matches2);

                if ($match_res && count($matches) == 3)
                {
                    $order->phone_code = $matches[1];
                    $order->phone = str_replace("-","",$matches[2]);
                }

                if ($match2_res && count($matches2) == 3)
                {
                    $order->phone2_code = $matches2[1];
                    $order->phone2 = str_replace("-","",$matches2[2]);
                }

                if (!$delivery->is_pickup)
                    $order->address = $this->request->post('address');

                if (!empty($this->user->id) && empty($this->user->delivery_address) && !empty($order->address))
                    $this->users->update_user($this->user->id, array('delivery_address'=>$order->address));

                $order->comment = $this->request->post('comment');
                $order->note = '';
                $order->user_id = isset($this->user) ? $this->user->id : 0;
                $order->ip = $_SERVER['REMOTE_ADDR'];

                $order_modificators = array();
                $order_modificators_count = array();

                $order_modificators_str = $this->request->post('order_modificators');
                $order_modificators_count_str = $this->request->post('order_modificators_count');

                if (!empty($order_modificators_str))
                    $order_modificators = explode(',', $order_modificators_str);
                if (!empty($order_modificators_count_str))
                    $order_modificators_count = explode(',', $order_modificators_count_str);

                $error_message = "";

                if ($this->settings->cart_fio_required && empty($order->name))
                    $error_message = "ФИО не введено";
                if ($this->settings->cart_phone_required && (empty($order->phone_code) || empty($order->phone)))
                    $error_message = "Код или телефон не введены";
                if ($this->settings->cart_email_required && empty($order->email))
                    $error_message = "Email не введен";

                //$delivery_method = $this->deliveries->get_delivery($order->delivery_id);

                if ($this->settings->cart_address_required && empty($order->address) && !$delivery->is_pickup)
                    $error_message = "Адрес не введен";

                if (empty($error_message))
                {
                    $order->id = @$this->orders->add_order($order);

                    if ($order->id)
                    {
                        $total_price = 0;

                        $cart = $this->cart->get_cart();

                        $this->design->assign('message_success', 'added');
                        if ($order->id && $cart)
                        {
                            if (!empty($order_modificators))
                                foreach($order_modificators as $index=>$m){
                                    $m_tmp = $this->modificators->get_modificator_orders($m);
                                    $modificator = new stdClass;
                                    $modificator->order_id = $order->id;
                                    $modificator->modificator_id = $m_tmp->id;
                                    $modificator->modificator_name = $m_tmp->name;
                                    $modificator->modificator_type = $m_tmp->type;
                                    $modificator->modificator_value = $m_tmp->value;
                                    $modificator->modificator_amount = $order_modificators_count[$index];
                                    $modificator->modificator_multi_buy = $m_tmp->multi_buy;

                                    $modificator->id = $this->orders->add_order_modificator($modificator);
                                }

                            foreach($cart->purchases as $purchase){
                                $this->orders->add_purchase(array('order_id' => $order->id, 'variant_id' => $purchase->variant->id, 'amount' => $purchase->amount, 'var_amount' => $purchase->var_amount, 'price' => $purchase->variant->price_for_one_pcs, 'price_for_mul' => $purchase->variant->price_for_mul, 'additional_sum' => $purchase->variant->additional_sum, 'modificators' => $purchase->modificators, 'modificators_count' => $purchase->modificators_count));

                                if ($purchase->variant->stock > 0 && !$purchase->variant->infinity){
                                    if ($purchase->variant->stock - $purchase->amount == 0)
                                    {
                                        $this->variants->update_variant($purchase->variant->id, array('stock' => $this->settings->new_status_when_product_is_out_of_stock));

                                        $tmp_product = $this->products->get_product($purchase->variant->product_id);
                                        $tmp_variants = $this->variants->get_variants(array('product_id'=>$tmp_product->id));
                                        $this->recreate_product_autotags($tmp_product, $tmp_variants);
                                    }
                                    else
                                        $this->variants->update_variant($purchase->variant->id, array('stock' => ($purchase->variant->stock - $purchase->amount)));
                                }

                                $total_price += $purchase->variant->price * $purchase->amount;
                            }
                        }

                        /*if($order->id && $this->request->post('amounts'))
                            foreach($this->request->post('amounts') as $variant_id=>$amount)
                            {
                                $this->orders->add_purchase(array('order_id'=>$order->id, 'variant_id'=>$variant_id, 'amount'=>$amount));

                                $variant = $this->variants->get_variant($variant_id);
                                if (!empty($variant) && $variant->stock > 0)
                                {
                                    if ($variant->stock - $amount == 0)
                                        $this->variants->update_variant($variant_id, array('stock' => $this->settings->new_status_when_product_is_out_of_stock));
                                    else
                                        $this->variants->update_variant($variant_id, array('stock' => ($variant->stock - $amount)));
                                }
                                $total_price += $variant->price * $amount;
                            }*/

                        if ($delivery->delivery_type == 'paid' && intval($delivery->free_from)>0 && $total_price >= $delivery->free_from)
                            $this->orders->update_order($order->id, array('delivery_price' => 0));

                        $this->cart->empty_cart();

                        //Отправляем письма
                        $this->notify_email->email_new_order($order->id);
                        $this->notify_email->email_new_order_admin($order->id);

                        //Отправляем смс пользователю
                        $this->notify_email->sms_new_order($order->id);
                        //Отправляем смс админу
                        $this->notify_email->sms_new_order_admin($order->id);

                        $order_module = $this->furl->get_module_by_name('OrderController');
                        header('Location: '.$this->config->root_url.$order_module->url.$order->url.'/');
                    }
                    else
                    {
                        $this->design->assign('order', $order);
                        $this->design->assign('message_error', 'error');
                    }
                }
                else
                {
                    $this->design->assign('order', $order);
                    $this->design->assign('message_error', $error_message);
                }

                break;
        }

        $cart = $this->cart->get_cart();
        $this->design->assign('cart', $cart);
                           
        $deliveries = $this->deliveries->get_deliveries(array('is_enabled'=>1));
        $this->design->assign('deliveries', $deliveries);

        if ($deliveries)
        {
            $first_delivery = @reset($deliveries);
            $payment_methods = $this->payment->get_payment_methods(array('is_enabled'=>1, 'delivery_id'=>$first_delivery->id));
            foreach($payment_methods as &$method)
            {
                $images = $this->image->get_images('payment', $method->id);
                $method->image = reset($images);
            }
            $this->design->assign('payment_methods', $payment_methods);
        }

        ### MODIFICATORS
        $modificators = $this->modificators->get_modificators_orders(array('is_visible' => 1));
        $modificators_groups = array();

        $all_group = new stdClass;
        $all_group->name = '';
        $all_group->id = 0;
        $all_group->parent_id = null;
        $all_group->type = 'checkbox';
        $all_group->modificators = array();
        $modificators_groups[0] = $all_group;
        foreach($this->modificators->get_modificators_orders_groups(array('is_visible' => 1)) as $group){
            $group->modificators = array();
            $modificators_groups[$group->id] = $group;
        }

        foreach($modificators as $m){
            if (!isset($m->parent_id))
                $m->parent_id = 0;
            $m->images = $this->image->get_images('modificators-orders', $m->id);
            $m->image = @reset($m->images);
            $modificators_groups[$m->parent_id]->modificators[] = $m;
        }

        $keys = array_keys($modificators_groups);
        if (!empty($keys))
        foreach($keys as $key)
            if (count($modificators_groups[$key]->modificators) == 0)
                unset($modificators_groups[$key]);

        $this->design->assign('modificators_groups', $modificators_groups);

        if ($ajax)
        {
            $data = array('success'=>false, 'data'=>'', 'meta_title'=>'Корзина', 'meta_description'=>'Корзина', 'meta_keywords'=>'Корзина');
            switch($format){
                case "info":
                    $data['data'] = $this->design->fetch($this->design->getTemplateDir('frontend').'cart-informer.tpl');
                    $data['header'] = $this->design->fetch($this->design->getTemplateDir('frontend').'cart-informer-header.tpl');
                    $data['body'] = $this->design->fetch($this->design->getTemplateDir('frontend').'cart-informer-body.tpl');
                    $data['total_products'] = $cart->total_products;
                    $data['total_price'] = $cart->total_price;
                    $data['total_price_display'] = $this->currencies->convert($cart->total_price);
                    $data['additional_result'] = $ajax_additional_result;
                    $data['success'] = true;
                    break;
                case "oneclick":
                    $data['data'] = $this->design->fetch($this->design->getTemplateDir('frontend').'cart-oneclick-body.tpl');
                    $data['total_price'] = $cart->total_price;
                    $data['success'] = true;
                    break;
                case "payment_methods":
                    if (array_key_exists('delivery_id', $this->params_arr))
                    {
                        $data['success'] = true;
                        $data['data'] = array();
                        $payment_methods = $this->payment->get_payment_methods(array('is_enabled'=>1, 'delivery_id'=>intval($this->params_arr['delivery_id'])));
                        foreach($payment_methods as $method)
                        {
                            $images = $this->image->get_images('payment', $method->id);
                            $image = reset($images);
                            $image_url = "";
                            if ($image)
                                $image_url = $this->design->resize_modifier($image->filename, 'payment', 138, 48);
                            $data['data'][] =  array('id'=>$method->id, 'name'=>$method->name, 'description'=>$method->description, 'image'=>$image_url, 'operation_type'=>$method->operation_type, 'operation_value'=>$method->operation_value);
                        }
                    }
                    break;
                default:
                    $data['data'] = $this->design->fetch($this->design->getTemplateDir('frontend').'cart.tpl');
                    $data['success'] = true;
                    break;
            }
            header("Content-type: application/json; charset=UTF-8");
            header("Cache-Control: must-revalidate");
            header("Pragma: no-cache");
            header("Expires: -1");
            print json_encode($data);
            die();
        }

        $this->design->assign('meta_title', "Корзина");
        $this->design->assign('meta_keywords', "Корзина");
        $this->design->assign('meta_description', "Корзина");

        return $this->design->fetch($this->design->getTemplateDir('frontend').'cart.tpl');
    }
}