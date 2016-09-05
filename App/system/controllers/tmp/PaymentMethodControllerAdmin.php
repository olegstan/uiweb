<?php
namespace app\controllers;

use core\Controller;

class PaymentMethodControllerAdmin extends Controller
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

        $main_module =  $this->furl->get_module_by_name('PaymentMethodsControllerAdmin');
        $this->design->assign('main_module', $main_module);

        if ($this->request->method('post') && !isset($_FILES['uploaded-images']))
        {
            $payment_method = new stdClass();
            $payment_method->id = $this->request->post('id', 'integer');
            $payment_method->name = $this->request->post('name');
            $payment_method->description = $this->request->post('description');
            $payment_method->is_enabled = $this->request->post('is_enabled', 'boolean');
            $payment_method->currency_id = $this->request->post('currency_id');
            $payment_method->payment_module_id = $this->request->post('payment_module_id');
            $payment_method->operation_type = $this->request->post('operation_type');

            $value_fix_sum = $this->request->post('value_fix_sum');
            $value_percent = $this->request->post('value_percent');
            if ($payment_method->operation_type == 'plus_fix_sum' || $payment_method->operation_type == 'minus_fix_sum')
                $payment_method->operation_value = $value_fix_sum;
            elseif ($payment_method->operation_type == 'plus_percent' || $payment_method->operation_type == 'minus_percent')
                $payment_method->operation_value = $value_percent;

            $payment_method->allow_payment = $this->request->post('allow_payment', 'boolean');

            $images_position = $this->request->post('images_position');

            if(!$delivery_methods = $this->request->post('delivery_methods'))
                 $delivery_methods = array();
            else
                foreach($delivery_methods as $k=>$d)
                    if (empty($d))
                        unset($delivery_methods[$k]);

            $close_after_save = $this->request->post('close_after_save', 'integer');

            if(empty($payment_method->id))
            {
                $payment_method->id = $this->payment->add_payment_method($payment_method);
                $this->design->assign('message_success', 'added');

                $temp_id = $this->request->post('temp_id');
                if ($temp_id)
                {
                    $images = $this->image_temp->get_images($temp_id);
                    if (!empty($images)){
                        foreach($images as $i){
                            $fname = $this->config->root_dir . '/' . $this->config->original_tempimages_dir . $i->filename;
                            $this->image->add_internet_image('payment', $payment_method->id, $this->furl->generate_url($payment_method->name), $fname);
                            $this->image_temp->delete_image($i->temp_id, $i->id);
                        }
                    }
                }
            }
            else
            {
                $this->payment->update_payment_method($payment_method->id, $payment_method);
                $this->design->assign('message_success', 'updated');
            }

            if (!empty($images_position))
            {
                $ip_arr = explode(',', $images_position);
                foreach($ip_arr as $pos=>$id)
                    $this->image->update_image($id, array('position'=>$pos));
            }

            $this->payment->update_delivery_methods($payment_method->id, $delivery_methods);

            $payment_method = $this->payment->get_payment_method(intval($payment_method->id));

            if ($close_after_save && $main_module)
                header("Location: ".$this->config->root_url.$main_module->url);
        }
        else
            if ($this->request->method('post') && isset($_FILES['uploaded-images']))
            {
                $uploaded = $this->request->files('uploaded-images');
                $object_id = $this->request->post('object_id');

                if (is_numeric($object_id))
                {
                    $tmp_object = $this->payment->get_payment_method($object_id);
                    foreach($uploaded as $index=>$ufile)
                        $img = $this->image->add_image('payment', $object_id, $this->furl->generate_url($tmp_object->name), $ufile['name'], $ufile['tmp_name']);
                }
                else
                    foreach($uploaded as $index=>$ufile)
                        $img = $this->image_temp->add_image($object_id, $ufile['name'], $ufile['tmp_name']);

                header("Content-type: application/json; charset=UTF-8");
                header("Cache-Control: must-revalidate");
                header("Pragma: no-cache");
                header("Expires: -1");
                print json_encode(1);
                die();
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
                            else
                                $id = strval($v);
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
                    $payment_method = $this->payment->get_payment_method($id);
                else
                {
                    $temp_id = uniqid();
                    $this->design->assign('temp_id', $temp_id);

                    $images = $this->image_temp->get_images($temp_id);
                    if (!empty($images)){
                        foreach($images as $i){
                            $fname = $this->config->root_dir . '/' . $this->config->original_tempimages_dir . $i->filename;
                            $this->image_temp->delete_image($i->temp_id, $i->id);
                        }
                    }
                }

                if (!empty($mode) && ((isset($payment_method) && !empty($payment_method)) || !is_numeric($id)))
                    switch($mode){
                        case "delete":
                            $this->payment->delete_payment_method($id);
                            $response['success'] = true;
                            break;
                        case "toggle":
                            $this->payment->update_payment_method($id, array('is_enabled'=>1-$payment_method->is_enabled));
                            $response['success'] = true;
                            break;
                        case "get_images":
                            $this->design->assign('object', $payment_method);

                            if (is_numeric($id))
                                $images = $this->image->get_images('payment', $id);
                            else
                            {
                                $images = $this->image_temp->get_images($id);
                                $this->design->assign('temp_id', $id);
                            }

                            $this->design->assign('images', $images);
                            $this->design->assign('images_object_name', 'payment');
                            $response['success'] = true;
                            $response['data'] = $this->design->fetch($this->design->getTemplateDir('admin').'object-images.tpl');
                            break;
                        case "delete_image":
                            $image_id = intval($this->params_arr['image_id']);

                            if (is_numeric($id))
                                $this->image->delete_image('payment', $id, $image_id);
                            else
                                $this->image_temp->delete_image($id, $image_id);

                            $response['success'] = true;
                            break;
                        case "upload_internet_image":
                            $image_url = base64_decode($this->params_arr['image_url']);

                            if (is_numeric($id))
                                $this->image->add_internet_image('payment', $id, $this->furl->generate_url($payment_method->name), $image_url);
                            else
                                $this->image_temp->add_internet_image($id, $image_url);

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

        if (isset($payment_method))
        {
            $this->design->assign('payment_method', $payment_method);
            $delivery_methods = $this->payment->get_delivery_methods($payment_method->id);
            $this->design->assign('delivery_methods', $delivery_methods);
            $images = $this->image->get_images('payment', $payment_method->id);
            $this->design->assign('images', $images);
        }

        $currencies = $this->currencies->get_currencies();
        $this->design->assign('currencies', $currencies);

        $all_delivery_methods = $this->deliveries->get_deliveries();
        $this->design->assign('all_delivery_methods', $all_delivery_methods);

        $this->db->query("SELECT em.id, em.controller, em.category, em.path, em.filename, em.is_enabled, em.settings, sf.url
            FROM __external_modules em LEFT JOIN __system_furl sf ON em.controller=sf.module WHERE em.is_visible AND category='payment'");
        $external_modules = $this->db->results();
        foreach($external_modules as &$module)
        {
            $filename_path = $this->config->root_dir . '/modules/' . $module->path . '/module.xml';

            $module_settings = "";
            $handle = fopen($filename_path, "r");
            while (!feof($handle)) {
                $buffer = fgets($handle);
                $module_settings .= $buffer;
            }
            fclose($handle);

            $settings = new SimpleXMLElement($module_settings);
            $module->name = $settings->name;
            $module->description = $settings->description;
            $module->icon = $settings->icon;
            $module->version = $settings->version;
            $module->releaseDate = $settings->releaseDate;
            $module->is_settings_page = $settings->settings == "yes" ? 1 : 0;
            $module->openable = 0;
            foreach($settings->controllers->controller as $controller)
                if ($controller['branch'] == "admin")
                    $module->openable = $controller['openable'] == "yes" ? 1 : 0;
        }
        $this->design->assign('all_external_modules', $external_modules);

        return $this->design->fetch($this->design->getTemplateDir('admin').'payment-method.tpl');
    }
}