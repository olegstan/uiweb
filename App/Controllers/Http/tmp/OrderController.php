<?php
namespace app\controllers;

use app\layer\LayerController;

class OrderController extends LayerController
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
        $ajax = false;
        $mode = "get_order";

        $order = $this->orders->get_order_by_url($this->param_url);
        if (!$order)
            return false;

        foreach($this->params_arr as $p=>$v)
        {
            switch ($p)
            {
                case "ajax":
                    $ajax = intval($v);
                    unset($this->params_arr[$p]);
                    break;
                case "mode":
                    $mode = strval($v);
                    break;
            }
        }

        switch($mode){
            case "get_order":
                $this->design->assign('order', $order);
                $this->design->assign('order_status', $this->orders->get_status($order->status_id));
                $this->design->assign('delivery_method', $this->deliveries->get_delivery($order->delivery_id));
                $payment_method = $this->payment->get_payment_method($order->payment_method_id);
                if ($payment_method)
                {
                    $payment_method->images = $this->image->get_images('payment', $payment_method->id);
                    $payment_method->image = reset($payment_method->images);
                    $this->design->assign('payment_method', $payment_method);
                }

                if ($payment_method && $payment_method->payment_module_id)
                {
                    $this->db->query("SELECT id, controller, category, path, filename, is_enabled
                        FROM __external_modules WHERE is_visible AND category='payment' AND id=?", $payment_method->payment_module_id);
                    $m = $this->db->result();

                    include_once("modules/$m->path/controller/payment.php");
                    $ext_module_name = $m->path."_payment";
                    $ext_module = new $ext_module_name();
                    $ext_module_form = $ext_module->payment_form($order->id);
                    $this->design->assign('ext_module_form', $ext_module_form);
                }

                $purchases = $this->orders->get_purchases(array('order_id'=>$order->id));

                if ($purchases)
                {
                    // Покупки
                    foreach($purchases as $index=>$purchase)
                    {
                        $purchases[$index]->product = $this->products->get_product($purchase->product_id);
                        $purchases[$index]->variants = $this->variants->get_variants(array('product_id'=>$purchase->product_id));
                        $purchases[$index]->variant = $this->variants->get_variant($purchase->variant_id);
                        $purchases[$index]->images = $this->image->get_images('products', $purchase->product_id);
                        $purchases[$index]->image = @reset($purchases[$index]->images);
                        $purchases[$index]->modificators = $this->orders->get_purchases_modificators(array('purchase_id' => $purchase->id));

                        if (!empty($purchases[$index]->product))
                        {
                            $cats = $this->categories->get_product_categories($purchase->product_id);
                            if (!empty($cats))
                            {
                                $cat = $cats[0]->category_id;
                                $breadcrumbs = "";
                                $category = $this->categories->get_category($cat);
                                while(true)
                                {
                                    $breadcrumbs = $category->name . ' / ' . $breadcrumbs;
                                    if ($category->parent_id == 0)
                                        break;
                                    $category = $this->categories->get_category($category->parent_id);
                                }
                                $breadcrumbs =  trim($breadcrumbs, ' /');
                                $purchases[$index]->breadcrumbs = $breadcrumbs;
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

                $order_payments = $this->ypayments->get_payments(array('order_id' => $order->id, 'limit' => 1));
                if ($order_payments)
                    $this->design->assign('order_payment', $order_payments[0]);
                break;
            case "cancel_order":
                $status = $this->orders->get_status('Отменены');
                if ($status)
                {
                    $this->orders->update_order($order->id, array('status_id' => $status->id));
                    $this->notify_email->email_cancel_order($order->id);
                    $this->notify_email->email_change_order_status_admin($order->id);
                    return true;
                }
                else
                    return false;
                break;
            case "repeat_order":
                $cart_module = $this->furl->get_module_by_name('CartController');
                $this->cart->empty_cart();

                $purchases = $this->orders->get_purchases(array('order_id'=>$order->id));
                foreach($purchases as $purchase)
                {
                    $product = $this->products->get_product($purchase->product_id);
                    if (!$product)
                        continue;
                    $variant = $this->variants->get_variant($purchase->variant_id);
                    if (!$variant)
                        continue;

                    $add_amount = min($variant->stock, $purchase->amount);

                    if ($add_amount>0 || $add_amount == -1)
                        $this->cart->add_item($variant->id, $add_amount);
                }

                header("Location: ".$this->config->root_url . $cart_module->url);
                break;
        }

        if ($ajax)
        {
            $data = array('success'=>true, 'data'=>$this->design->fetch($this->design->getTemplateDir('frontend').'order.tpl'), 'meta_title'=>'Заказ №'.$order->id, 'meta_keywords'=>'Заказ №'.$order->id, 'meta_description'=>'Заказ №'.$order->id);
            header("Content-type: application/json; charset=UTF-8");
            header("Cache-Control: must-revalidate");
            header("Pragma: no-cache");
            header("Expires: -1");
            print json_encode($data);
            die();
        }

        if (!empty($order))
        {
            $this->design->assign('meta_title', 'Заказ №'.$order->id);
            $this->design->assign('meta_description', 'Заказ №'.$order->id);
            $this->design->assign('meta_keywords', 'Заказ №'.$order->id);
        }

        return $this->design->fetch($this->design->getTemplateDir('frontend').'order.tpl');
    }
}