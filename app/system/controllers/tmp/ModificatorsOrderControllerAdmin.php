<?php
namespace app\controllers;

use core\Controller;

class ModificatorsOrderControllerAdmin extends Controller
{
    private $param_url, $params_arr, $options;

    public function set_params($url = null, $options = null){
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

        $edit_module = $this->furl->get_module_by_name('ModificatorOrdersControllerAdmin');
        $main_module =  $this->furl->get_module_by_name('ModificatorsOrdersControllerAdmin');
        $this->design->assign('main_module', $main_module);

        if ($this->request->method('post') && !isset($_FILES['uploaded-images']))
        {
            $modificator = new stdClass();
            $modificator->id = $this->request->post('id', 'integer');
            $modificator->parent_id = $this->request->post('parent_id', 'integer');
            if ($modificator->parent_id == 0)
                $modificator->parent_id = null;
            $modificator->name = $this->request->post('name');
            $modificator->is_visible = $this->request->post('is_visible', 'boolean');
            $modificator->type = $this->request->post('type');

            $value_fix_sum = $this->request->post('value_fix_sum');
            $value_percent = $this->request->post('value_percent');
            if ($modificator->type == 'plus_fix_sum' || $modificator->type == 'minus_fix_sum')
                $modificator->value = $value_fix_sum;
            elseif ($modificator->type == 'plus_percent' || $modificator->type == 'minus_percent')
                $modificator->value = $value_percent;

            $modificator->description = $this->request->post('description');
            $modificator->multi_buy = $this->request->post('multi_buy', 'boolean');
            $modificator->multi_buy_min = $this->request->post('multi_buy_min');
            $modificator->multi_buy_max = $this->request->post('multi_buy_max');

            $close_after_save = $this->request->post('close_after_save', 'integer');
            $add_after_save = $this->request->post('add_after_save', 'integer');

            if(empty($modificator->id))
            {
                $modificator->id = $this->modificators->add_modificator_orders($modificator);
                $this->design->assign('message_success', 'added');

                $temp_id = $this->request->post('temp_id');
                if ($temp_id)
                {
                    $images = $this->image_temp->get_images($temp_id);
                    if (!empty($images)){
                        foreach($images as $i){
                            $fname = $this->config->root_dir . '/' . $this->config->original_tempimages_dir . $i->filename;
                            $this->image->add_internet_image('modificators-orders', $modificator->id, $this->furl->generate_url($modificator->name), $fname);
                            $this->image_temp->delete_image($i->temp_id, $i->id);
                        }
                    }
                }
            }
            else
            {
                $this->modificators->update_modificator_orders($modificator->id, $modificator);
                $this->design->assign('message_success', 'updated');
            }

            $modificator = $this->modificators->get_modificator_orders(intval($modificator->id));

            $return_parent_id = $this->request->post('return_parent_id');

            if ($close_after_save && $main_module)
                header("Location: ".$this->config->root_url.$main_module->url.($return_parent_id>0?'?parent_id='.$return_parent_id:''));

            if ($add_after_save)
                header("Location: ".$this->config->root_url.$edit_module->url.($return_parent_id>0?'?parent_id='.$return_parent_id:''));
        }
        else
            if ($this->request->method('post') && isset($_FILES['uploaded-images']))
            {
                $uploaded = $this->request->files('uploaded-images');
                $object_id = $this->request->post('object_id');

                if (is_numeric($object_id))
                {
                    $tmp_object = $this->modificators->get_modificator_orders($object_id);
                    foreach($uploaded as $index=>$ufile)
                        $img = $this->image->add_image('modificators-orders', $object_id, $this->furl->generate_url($tmp_object->name), $ufile['name'], $ufile['tmp_name']);
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
                        case "parent_id":
                            $this->design->assign('parent_id', intval($v));
                            unset($this->params_arr[$p]);
                            break;
                    }
                }

                if (!empty($id))
                    $modificator = $this->modificators->get_modificator_orders($id);
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

                if (!empty($mode) && ((isset($modificator) && !empty($modificator)) || !is_numeric($id)))
                    switch($mode){
                        case "delete":
                            $this->modificators->delete_modificator_orders($id);
                            $response['success'] = true;
                            break;
                        case "toggle":
                            $this->modificators->update_modificator_orders($id, array('is_visible' => 1-$modificator->is_visible));
                            $response['success'] = true;
                            break;
                        case "get_images":
                            $this->design->assign('object', $modificator);

                            if (is_numeric($id))
                                $images = $this->image->get_images('modificators-orders', $id);
                            else
                            {
                                $images = $this->image_temp->get_images($id);
                                $this->design->assign('temp_id', $id);
                            }

                            $this->design->assign('images', $images);
                            $this->design->assign('images_object_name', 'modificators-orders');
                            $response['success'] = true;
                            $response['data'] = $this->design->fetch($this->design->getTemplateDir('admin').'object-images.tpl');
                            break;
                        case "delete_image":
                            $image_id = intval($this->params_arr['image_id']);

                            if (is_numeric($id))
                                $this->image->delete_image('modificators-orders', $id, $image_id);
                            else
                                $this->image_temp->delete_image($id, $image_id);

                            $response['success'] = true;
                            break;
                        case "upload_internet_image":
                            $image_url = base64_decode($this->params_arr['image_url']);

                            if (is_numeric($id))
                                $this->image->add_internet_image('modificators-orders', $id, $this->furl->generate_url($modificator->name), $image_url);
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

        if (isset($modificator)){
            $this->design->assign('modificator', $modificator);
            $images = $this->image->get_images('modificators-orders', $modificator->id);
            $this->design->assign('images', $images);
        }

        $this->design->assign('current_params', $this->params_arr);
        $this->design->assign('params_arr', $this->params_arr);

        $modificator_groups = $this->modificators->get_modificators_orders_groups();
        $this->design->assign('modificator_groups', $modificator_groups);

        return $this->design->fetch($this->design->getTemplateDir('admin').'modificators-order.tpl');
    }
}