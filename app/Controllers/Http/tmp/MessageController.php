<?php
namespace app\controllers;

use app\layer\LayerController;

class MessageController extends LayerController
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

        if(!($template = $this->notify_email->get_template('new-order')) ||
            !$template->is_enabled)
            return false;

        $order_module = $this->furl->get_module_by_name('OrderController');
        $all_currencies = $this->currencies->get_currencies(array('is_enabled'=>1));
        $currency = reset($all_currencies);
        $status = $this->orders->get_status($order->status_id);

        $delivery_method = $this->deliveries->get_delivery($order->delivery_id);

        $this->db->query("SELECT * FROM __currencies WHERE is_enabled = 1 AND use_main = 1");
        $main_currency = $this->db->result();

        $purchases = $this->orders->get_purchases(array('order_id'=>$order->id));

        //Замена в заголовке
        $template->message_header = str_replace('$shop_name$', $this->settings->site_name, $template->message_header);
        $template->message_header = str_replace('$id$', $order->id, $template->message_header);
        $template->message_header = str_replace('$site_url$', $this->config->root_url, $template->message_header);

        //Замена в тексте письма
        $template->message_text = str_replace('$shop_name$', $this->settings->site_name, $template->message_text);
        $template->message_text = str_replace('$id$', $order->id, $template->message_text);
        $template->message_text = str_replace('$name$', $order->name, $template->message_text);
        $template->message_text = str_replace('$mail$', $order->email, $template->message_text);
        $template->message_text = str_replace('$phone$', '+7 ('.$order->phone_code.') '.$this->design->phone_mask_modifier($order->phone), $template->message_text);
        $template->message_text = str_replace('$delivery_method$', empty($delivery_method) ? '' : $delivery_method->name, $template->message_text);
        $template->message_text = str_replace('$address$', $order->address, $template->message_text);
        $template->message_text = str_replace('$order_link$', $this->config->root_url.$order_module->url.$order->url.'/', $template->message_text);
        $template->message_text = str_replace('$order_sum$', $this->currencies->convert($order->total_price).' '.$main_currency->sign, $template->message_text);
        $template->message_text = str_replace('$order_discount$', $order->discount.' %', $template->message_text);
        $template->message_text = str_replace('$order_discount_sum$', $this->currencies->convert($order->total_price_wo_discount-$order->total_price).' '.$main_currency->sign, $template->message_text);
        $template->message_text = str_replace('$order_status$', $status->group_name, $template->message_text);
        $template->message_text = str_replace('$order_date$', $this->design->date_modifier($order->date), $template->message_text);
        $template->message_text = str_replace('$order_time$', $this->design->time_modifier($order->date), $template->message_text);
        $template->message_text = str_replace('$products$', $this->notify_email->make_purchases_list_table($purchases), $template->message_text);
        $template->message_text = str_replace('$site_url$', $this->config->root_url, $template->message_text);
        $template->message_text = str_replace('$order_comment$', $order->comment, $template->message_text);

        $delivery_price = "";
        if (!empty($delivery_method))
        {
            if ($delivery_method->delivery_type == "separately")
                $delivery_price = "Оплачивается отдельно";
            else
                if (($order->delivery_price > 0 && $delivery_method->free_from == 0) || ($delivery_method->free_from > 0 && $delivery_method->free_from > $order->total_price))
                    $delivery_price = $this->currencies->convert($order->delivery_price).' '.$main_currency->sign;
                else
                    $delivery_price = "Бесплатно";
        }
        $template->message_text = str_replace('$delivery_price$', $delivery_price, $template->message_text);

        echo $template->message_text;
        die();
    }
}