<?php
namespace app\controllers;

use core\Controller;

class DeliveryMethodControllerAdmin extends Controller
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

        $main_module =  $this->furl->get_module_by_name('DeliveryMethodsControllerAdmin');
        $this->design->assign('main_module', $main_module);

        if ($this->request->method('post'))
        {
            $delivery = new stdClass();
            $delivery->id = $this->request->post('id', 'integer');
            $delivery->name = $this->request->post('name');
            $delivery->description = $this->request->post('description');
            $delivery->free_from = $this->request->post('free_from');
            $delivery->price = $this->request->post('price');
            $delivery->is_enabled = $this->request->post('is_enabled', 'boolean');
            //$delivery->separate_payment = $this->request->post('separate_payment');
            $delivery->is_pickup = $this->request->post('is_pickup', 'boolean');
            $delivery->delivery_type = $this->request->post('delivery_type', 'string');

            $delivery_payments = array();
            if($this->request->post('delivery_payments'))
            {
                foreach($this->request->post('delivery_payments') as $delivery_method_id=>$value){
                    $value = intval($value);
                    if (!empty($value))
                        $delivery_payments[] = $delivery_method_id;
                }
            }

            $close_after_save = $this->request->post('close_after_save', 'integer');

            if(empty($delivery->id))
            {
                $delivery->id = $this->deliveries->add_delivery($delivery);
                $this->design->assign('message_success', 'added');
            }
            else
            {
                $this->deliveries->update_delivery($delivery->id, $delivery);
                $this->design->assign('message_success', 'updated');
            }

            $this->deliveries->update_delivery_payments($delivery->id, $delivery_payments);

            $delivery = $this->deliveries->get_delivery(intval($delivery->id));

            if ($close_after_save && $main_module)
                header("Location: ".$this->config->root_url.$main_module->url);
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
                }
            }

            if (!empty($id))
                $delivery = $this->deliveries->get_delivery($id);

            if (!empty($mode) && $delivery)
                switch($mode){
                    case "delete":
                        $this->deliveries->delete_delivery($id);
                        $response['success'] = true;
                        break;
                    case "toggle":
                        $this->deliveries->update_delivery($id, array('is_enabled'=>1-$delivery->is_enabled));
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

        if (isset($delivery))
        {
            $this->design->assign('delivery', $delivery);
            $delivery_payments = $this->deliveries->get_delivery_payments($delivery->id);
            $this->design->assign('delivery_payments', $delivery_payments);
        }

        $payment_methods = $this->payment->get_payment_methods();
        $this->design->assign('payment_methods', $payment_methods);

        return $this->design->fetch($this->design->getTemplateDir('admin').'delivery-method.tpl');
    }
}