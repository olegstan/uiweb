<?php
namespace app\controllers;

use core\Controller;

class OrderProcessedControllerAdmin extends Controller
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

        $edit_module = $this->furl->get_module_by_name('OrderProcessedControllerAdmin');
        $main_module =  $this->furl->get_module_by_name('OrdersProcessedControllerAdmin');
        $order_module = $this->furl->get_module_by_name('OrderController');
        $this->design->assign('main_module', $main_module);
        $this->design->assign('order_module', $order_module);

        if ($this->request->method('post'))
        {
            $order = new stdClass();
            $order->id = $this->request->post('id', 'integer');
            $order->status_id = $this->request->post('status_id');
            $order->name = $this->request->post('name');
            $order->discount_type = $this->request->post('discount_type');
            $order->discount = $this->request->post('discount');
            $order->delivery_id = $this->request->post('delivery_id');
            $order->delivery_price = $this->request->post('delivery_price');
            $order->separate_delivery = $this->request->post('separate_delivery', 'boolean');
            $order->payment_method_id = $this->request->post('payment_method_id');
            $order->paid = $this->request->post('paid', 'boolean');
            $order->allow_payment = $this->request->post('allow_payment', 'boolean');

            $order->notify_order = $this->request->post('notify_order', 'boolean');
            $order->notify_news = $this->request->post('notify_news', 'boolean');

            $order_date = $this->request->post('date_date');
            $order_time = $this->request->post('date_time');
            $datetime = DateTime::createFromFormat('d.m.Y G:i', $order_date." ".$order_time);
            $order->date = $datetime->format('Y-m-d G:i');

            $order->email = $this->request->post('email');

            $phone = $this->request->post('phone');
            $match_res = preg_match("/^[^\(]+\(([^\)]+)\).(.+)$/", $phone, $matches);

            $phone2 = $this->request->post('phone2');
            $match2_res = preg_match("/^[^\(]+\(([^\)]+)\).(.+)$/", $phone2, $matches2);

            $order->phone_code = "";
            $order->phone = "";
            if ($match_res && count($matches) == 3)
            {
                $order->phone_code = $matches[1];
                $order->phone = str_replace("-","",$matches[2]);
            }

            $order->phone2_code = "";
            $order->phone2 = "";
            if ($match2_res && count($matches2) == 3)
            {
                $order->phone2_code = $matches2[1];
                $order->phone2 = str_replace("-","",$matches2[2]);
            }


            /*$order->phone_code = $this->request->post('phone_code');
            $order->phone = $this->request->post('phone');
            $order->phone2_code = $this->request->post('phone2_code');
            $order->phone2 = $this->request->post('phone2');*/
            $order->address = $this->request->post('address');
            $order->comment = $this->request->post('comment');
            $order->note = $this->request->post('note');
            $order->user_id = $this->request->post('user_id');

            $purchases = array();
            if($this->request->post('purchases'))
                foreach($this->request->post('purchases') as $n=>$va)
                    foreach($va as $i=>$v)
                    {
                        if (!array_key_exists($i, $purchases))
                            $purchases[$i] = new stdClass;
                        $purchases[$i]->$n = @$v;
                    }

            $close_after_save = $this->request->post('close_after_save', 'integer');
            $add_after_save = $this->request->post('add_after_save', 'integer');

            if (empty($purchases))
                $this->design->assign('message_error', 'empty_purchases');
            else
                if(empty($order->id))
                {
                    $order->moderated = 0;
                    $order->id = @$this->orders->add_order($order);
                    if ($order->id)
                        $this->design->assign('message_success', 'added');
                    else
                        $this->design->assign('message_error', 'error');
                }
                else
                {
                    $old_order = $this->orders->get_order($order->id);
                    $this->orders->update_order($order->id, $order);
                    $this->design->assign('message_success', 'updated');
                }
            if (!empty($order->id))
                $order = $this->orders->get_order(intval($order->id));

            //если изменился статус заказа оповестим пользователя и админа
            if (!empty($old_order) && $old_order->status_id != $order->status_id)
            {
                $this->notify_email->email_change_order_status($order->id);
                $this->notify_email->email_change_order_status_admin($order->id);
                $this->notify_email->sms_change_order_status($order->id);
            }

            //если разрешили оплату заказа оповестим пользователя
            if (!empty($old_order) && $old_order->allow_payment != $order->allow_payment && $order->allow_payment)
            {
                $this->notify_email->email_allow_order_payment($order->id);
            }

            if(is_array($purchases) && !empty($purchases))
            {
                $purchases_ids = array();
                foreach($purchases as $index=>&$purchase)
                {
                    if(isset($purchase->id))
                        $this->orders->update_purchase($purchase->id, $purchase);
                    else
                    {
                        $purchase->order_id = $order->id;
                        $purchase->id = $this->orders->add_purchase($purchase);
                    }
                    $purchase = $this->orders->get_purchase($purchase->id);

                    $purchases_ids[] = $purchase->id;
                }

                // Удалить непереданные покупки
                $current_purchases = $this->orders->get_purchases(array('order_id'=>$order->id));
                foreach($current_purchases as $current_purchase)
                    if(!in_array($current_purchase->id, $purchases_ids))
                        $this->orders->delete_purchase($current_purchase->id);
            }

            $new_order_status = $this->orders->get_status('Новые');
            if ($new_order_status)
            {
                $count_new_orders = $this->orders->count_orders(array('status_id'=>$new_order_status->id));
                $this->design->assign('count_new_orders', $count_new_orders);
            }

            $return_status_id = $this->request->post('return_status_id');

            if ($close_after_save && $main_module)
                header("Location: ".$this->config->root_url.$main_module->url.$this->design->url_modifier(array('add'=>array('status_id'=>$return_status_id))));

            if ($add_after_save)
                header("Location: ".$this->config->root_url.$edit_module->url.$this->design->url_modifier(array('add'=>array('status_id'=>$return_status_id))));
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
                    case "status_id":
                        $this->design->assign('status_id', intval($v));
                        break;
                }
            }

            if (!empty($id))
                $order = $this->orders->get_order($id);

            if (!empty($mode) && (!empty($order) || $mode == 'purchase-modificators' || $mode == 'purchase-save-modificators'))
                switch($mode){
                    case "delete":
                        $this->orders->delete_order($id);
                        $response['success'] = true;
                        break;
                    case "get_purchases":
                        $purchases = $this->orders->get_purchases(array('order_id'=>$id));
                        $response['success'] = true;
                        $response['data'] = array();
                        foreach($purchases as $purchase)
                        {
                            $row = array();
                            $row['product_name'] = $purchase->product_name;
                            $row['variant_name'] = $purchase->variant_name;
                            $row['amount'] = $purchase->amount;
                            $row['sku'] = $purchase->sku;
                            $row['price'] = $this->currencies->convert($purchase->price, null, false);
                            $row['format_price'] = $this->currencies->convert($purchase->price, null, true);
                            $row['modificators'] = $this->orders->get_purchases_modificators(array('purchase_id'=>$purchase->id));
                            $row['show_modificators'] = false;
                            $product = $this->products->get_product($purchase->product_id);

                            if ($product)
                            {
                                $product->images = $this->image->get_images('products', $product->id);
                                $product->image = @reset($product->images);
                                $row['image'] = empty($product->image)?"":$this->design->resize_modifier($product->image->filename, 'products', 40, 40);
                                $row['product_id'] = $purchase->product_id;

                                $product_categories = $this->categories->get_product_categories($purchase->product_id);
                                $product_category = @reset($product_categories);
                                $product_category = $this->categories->get_category(intval($product_category));
                                if (!empty($product_category))
                                    $row['show_modificators'] = !empty($product_category->modificators) || !empty($product_category->modificators_groups);
                            }
                            else
                            {
                                $row['product_id'] = 0;
                                $row['image'] = "";
                            }

                            $response['data'][] = $row;
                        }
                        break;
                    case "purchase-modificators":
                        $purchase_id = @intval($this->params_arr['purchase_id']);
                        if ($purchase_id > 0)
                        {
                            $purchase = $this->orders->get_purchase($purchase_id);
                            $purchase->product = $this->products->get_product($purchase->product_id);
                            $purchase->variants = $this->variants->get_variants(array('product_id'=>$purchase->product_id, 'is_visible'=>1));
                            $purchase->variant = $this->variants->get_variant($purchase->variant_id);
                            $purchase->images = $this->image->get_images('products', $purchase->product_id);
                            $purchase->image = @reset($purchase->images);
                            $purchase->modificators = $this->orders->get_purchases_modificators(array('purchase_id'=>$purchase->id));

                            $purchase->in_stock = false;
                            $purchase->in_order = false;
                            foreach($purchase->variants as $pv)
                                if ($pv->stock > 0)
                                    $purchase->in_stock = true;
                                else
                                    if ($pv->stock < 0)
                                        $purchase->in_order = true;

                            if (!empty($purchase->product))
                            {
                                $product_categories = $this->categories->get_product_categories($purchase->product->id);
                                if ($product_categories)
                                {
                                    $product_category = reset($product_categories);
                                    $purchase->category = $this->categories->get_category($product_category->category_id);
                                }
                            }

                            $this->design->assign('purchase', $purchase);

                        ############
                        ############
                            $modificators = $this->modificators->get_modificators(array('is_visible' => 1));
                            $modificators_groups = array();

                            $all_group = new stdClass;
                            $all_group->name = '';
                            $all_group->id = 0;
                            $all_group->parent_id = null;
                            $all_group->type = 'checkbox';
                            $all_group->modificators = array();
                            $modificators_groups[0] = $all_group;
                            foreach($this->modificators->get_modificators_groups(array('is_visible' => 1)) as $group){
                                $group->modificators = array();
                                $modificators_groups[$group->id] = $group;
                            }

                            foreach($modificators as $m){
                                if (!isset($m->parent_id))
                                    $m->parent_id = 0;
                                $m->images = $this->image->get_images('modificators', $m->id);
                                $m->image = @reset($m->images);
                                $modificators_groups[$m->parent_id]->modificators[] = $m;
                            }
                            $this->design->assign('modificators_groups', $modificators_groups);
                        ##########
                        ##########

                            $response['success'] = true;
                            $response['data'] = $this->design->fetch($this->design->getTemplateDir('admin').'order-purchase-edit-modificators.tpl');
                        }
                        break;
                    case "order-modificators":
                        $order_id = @intval($this->params_arr['order_id']);
                        if ($order_id > 0)
                        {
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

                            $order = $this->orders->get_order($id);
                            $order->modificators = $this->orders->get_order_modificators(array('order_id'=>$order->id));
                            $this->design->assign('order', $order);

                            $response['success'] = true;
                            $response['data'] = $this->design->fetch($this->design->getTemplateDir('admin').'order-edit-modificators.tpl');
                        }
                        break;
                    case "purchase-save-modificators":
                        $purchase_id = @intval($this->params_arr['purchase_id']);
                        $modificators = array();
                        $modificators_count = array();
                        $var_amount = 1;
                        if (array_key_exists('modificators', $this->params_arr))
                            $modificators = strval($this->params_arr['modificators']);
                        if (array_key_exists('modificators_count', $this->params_arr))
                            $modificators_count = strval($this->params_arr['modificators_count']);
                        if (!empty($modificators))
                            $modificators = explode(',', $modificators);
                        if (!empty($modificators_count))
                            $modificators_count = explode(',', $modificators_count);
                        if (array_key_exists('var_amount', $this->params_arr))
                            $var_amount = floatval($this->params_arr['var_amount']);
                        $purchase = new stdClass;
                        $purchase->id = $purchase_id;
                        $purchase->modificators = array();
                        $purchase->modificators_count = array();
                        $purchase->var_amount = $var_amount;

                        if (!empty($modificators))
                            foreach($modificators as $idx=>$m_id){
                                $m = $this->modificators->get_modificator(intval($m_id));
                                if ($m){
                                    $purchase->modificators[] = $m;
                                    if (array_key_exists($idx, $modificators_count))
                                        $purchase->modificators_count[] = intval($modificators_count[$idx]);
                                    else
                                        $purchase->modificators_count[] = 1;
                                }
                            }

                        //изменим цену варианта в соответствии с модификаторами
                        $tmp_purchase = $this->orders->get_purchase($purchase_id);
                        $purchase->product_id = $tmp_purchase->product_id;
                        $purchase->variant_id = $tmp_purchase->variant_id;
                        $tmp_purchase->product = $this->products->get_product($tmp_purchase->product_id);
                        $tmp_purchase->variants = $this->variants->get_variants(array('product_id'=>$tmp_purchase->product_id, 'is_visible'=>1));
                        $tmp_purchase->variant = $this->variants->get_variant($tmp_purchase->variant_id);
                        if (!empty($tmp_purchase->variant))
                            $tmp_price = $tmp_purchase->variant->price;
                        else
                            $tmp_price = $tmp_purchase->price;
                        $purchase->price = $tmp_price * $var_amount;
                        $purchase->amount = $tmp_purchase->amount;
                        if (!empty($purchase->modificators))
                            foreach($purchase->modificators as $idx=>$m){
                                $v = $m->value;
                                $count = $purchase->modificators_count[$idx];
                                if ($m->multi_apply == 0 && $purchase->amount > 1)
                                    continue;
                                if ($m->type == 'plus_fix_sum')
                                    $purchase->price += $v * $count;
                                if ($m->type == 'minus_fix_sum')
                                    $purchase->price -= $v * $count;
                                if ($m->type == 'plus_percent')
                                    $purchase->price += $tmp_price * $v * $count / 100;
                                if ($m->type == 'minus_percent')
                                    $purchase->price -= $tmp_price * $v * $count / 100;
                            }

                        $purchase->price_for_mul = $purchase->price;

                        if (!empty($purchase->modificators))
                            foreach($purchase->modificators as $idx=>$m){
                                $v = $m->value;
                                $count = $purchase->modificators_count[$idx];
                                if (!($m->multi_apply == 0 && $purchase->amount > 1))
                                    continue;
                                if ($m->type == 'plus_fix_sum')
                                    $purchase->price += $v * $count;
                                if ($m->type == 'minus_fix_sum')
                                    $purchase->price -= $v * $count;
                                if ($m->type == 'plus_percent')
                                    $purchase->price += $tmp_price * $v * $count / 100;
                                if ($m->type == 'minus_percent')
                                    $purchase->price -= $tmp_price * $v * $count / 100;
                            }

                        $purchase->additional_sum = $purchase->price - $purchase->price_for_mul;

                        $response['success'] = $this->orders->update_purchase($purchase->id, $purchase) > 0;

                        $purchase = $this->orders->get_purchase($purchase->id);
                        $order = $this->orders->get_order($purchase->order_id);
                        if (!empty($order->id))
                            $purchases = $this->orders->get_purchases(array('order_id'=>$order->id));

                        if (isset($purchases) && !empty($purchases))
                        {
                            // Покупки
                            foreach($purchases as $index=>$purchase)
                            {
                                $purchases[$index]->product = $this->products->get_product($purchase->product_id);
                                $purchases[$index]->variants = $this->variants->get_variants(array('product_id'=>$purchase->product_id, 'is_visible'=>1));
                                $purchases[$index]->variant = $this->variants->get_variant($purchase->variant_id);
                                $purchases[$index]->images = $this->image->get_images('products', $purchase->product_id);
                                $purchases[$index]->image = @reset($purchases[$index]->images);
                                $purchases[$index]->modificators = $this->orders->get_purchases_modificators(array('purchase_id'=>$purchase->id));

                                $purchases[$index]->in_stock = false;
                                $purchases[$index]->in_order = false;
                                foreach($purchases[$index]->variants as $pv)
                                    if ($pv->stock > 0)
                                        $purchases[$index]->in_stock = true;
                                    else
                                        if ($pv->stock < 0)
                                            $purchases[$index]->in_order = true;

                                if (!empty($purchases[$index]->product))
                                {
                                    $product_categories = $this->categories->get_product_categories($purchases[$index]->product->id);
                                    if ($product_categories)
                                    {
                                        $product_category = reset($product_categories);
                                        $purchases[$index]->category = $this->categories->get_category($product_category->category_id);
                                    }
                                }
                            }
                            $purchases_total = 0;
                            $purchases_count = 0;
                            foreach($purchases as $purchase)
                            {
                                $purchases_total += $purchase->price*$purchase->amount;
                                $purchases_count += $purchase->amount;
                            }
                            $this->design->assign('purchases', $purchases);
                            $this->design->assign('purchases_total', $purchases_total);
                            $this->design->assign('purchases_count', $purchases_count);
                            $this->design->assign('edit_order_mode', 1);
                            $response['data'] = $this->design->fetch($this->design->getTemplateDir('admin').'order-purchases.tpl');
                        }
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

        if (isset($order))
        {
            if (isset($order->moderated) && !$order->moderated)
                $this->orders->update_order($order->id, array('moderated'=>1));
            $this->design->assign('order', $order);
            if (!empty($order->id))
                $purchases = $this->orders->get_purchases(array('order_id'=>$order->id));

            if (isset($purchases) && !empty($purchases))
            {
                // Покупки
                foreach($purchases as $index=>$purchase)
                {
                    $purchases[$index]->product = $this->products->get_product($purchase->product_id);
                    $purchases[$index]->show_modificators = false;
                    $product_categories = $this->categories->get_product_categories($purchase->product_id);
                    $product_category = @$product_categories[0];
                    if (!empty($product_category))
                        $product_category = $this->categories->get_category(intval($product_category->category_id));
                    if (!empty($product_category))
                        $purchases[$index]->show_modificators = !empty($product_category->modificators) || !empty($product_category->modificators_groups);
                    $purchases[$index]->variants = $this->variants->get_variants(array('product_id'=>$purchase->product_id, 'is_visible'=>1));
                    $purchases[$index]->variant = $this->variants->get_variant($purchase->variant_id);
                    $purchases[$index]->images = $this->image->get_images('products', $purchase->product_id);
                    $purchases[$index]->image = @reset($purchases[$index]->images);
                    $purchases[$index]->modificators = $this->orders->get_purchases_modificators(array('purchase_id'=>$purchase->id));

                    $purchases[$index]->in_stock = false;
                    $purchases[$index]->in_order = false;
                    foreach($purchases[$index]->variants as $pv)
                        if ($pv->stock > 0)
                            $purchases[$index]->in_stock = true;
                        else
                            if ($pv->stock < 0)
                                $purchases[$index]->in_order = true;

                    if (!empty($purchases[$index]->product))
                    {
                        $product_categories = $this->categories->get_product_categories($purchases[$index]->product->id);
                        if ($product_categories)
                        {
                            $product_category = reset($product_categories);
                            $purchases[$index]->category = $this->categories->get_category($product_category->category_id);
                        }
                    }
                }
                $purchases_total = 0;
                $purchases_count = 0;
                foreach($purchases as $purchase)
                {
                    $purchases_total += $purchase->price*$purchase->amount;
                    $purchases_count += $purchase->amount;
                }
                $this->design->assign('purchases', $purchases);
                $this->design->assign('purchases_total', $purchases_total);
                $this->design->assign('purchases_count', $purchases_count);
            }

            $modificators = $this->orders->get_order_modificators(array('order_id' => $order->id));
            $this->design->assign('modificators', $modificators);
        }
        $this->design->assign('orders_statuses', $this->orders->get_statuses());
        $this->design->assign('deliveries', $this->deliveries->get_deliveries());
        if (isset($order) && !empty($order))
            $this->design->assign('payment_methods', $this->payment->get_payment_methods(array('is_enabled'=>1, 'delivery_id'=>$order->delivery_id)));
        else
            $this->design->assign('payment_methods', $this->payment->get_payment_methods(array('is_enabled'=>1)));
        $this->design->assign('products_module', $this->furl->get_module_by_name('ProductsControllerAdmin'));
        $this->design->assign('product_module', $this->furl->get_module_by_name('ProductControllerAdmin'));
        $this->design->assign('user_module', $this->furl->get_module_by_name('UserControllerAdmin'));

        $this->design->assign('all_users', $this->users->get_users());

        return $this->design->fetch($this->design->getTemplateDir('admin').'order.tpl');
    }
}