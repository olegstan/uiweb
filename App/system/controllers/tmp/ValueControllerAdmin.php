<?php
namespace app\controllers;

use core\Controller;

class ValueControllerAdmin extends Controller
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

        $edit_module = $this->furl->get_module_by_name('ValueControllerAdmin');
        $main_module =  $this->furl->get_module_by_name('ValuesControllerAdmin');
        $this->design->assign('main_module', $main_module);

        if ($this->request->method('post') && !isset($_FILES['uploaded-images']))
        {
            $tag = new stdClass();
            $tag->id = $this->request->post('id', 'integer');
            $tag->group_id = $this->request->post('group_id');
            $tag->name = $this->request->post('name');
            $tag->is_enabled = $this->request->post('is_enabled', 'boolean');
            //$tag->css_class = $this->request->post('css_class');
            //$tag->show_flag = $this->request->post('show_flag', 'boolean');
            //$tag->is_state_tag = $this->request->post('is_state_tag', 'boolean');
            //$tag->header_text = $this->request->post('header_text');
            //$tag->link_text = $this->request->post('link_text');
            //$tag->is_auto = $this->request->post('is_auto', 'boolean');

            $avalues = $this->request->post('alternative_values');
            $close_after_save = $this->request->post('close_after_save', 'integer');
            $add_after_save = $this->request->post('add_after_save', 'integer');

            //Проверим нет ли уже такого тега
            $count_tags = $this->tags->count_tags(array('name'=>trim($tag->name), 'group_id'=>$tag->group_id));

            if(empty($tag->id))
            {
                if ($count_tags == 0)
                {
                    $tag->id = @$this->tags->add_tag($tag);
                    if ($tag->id)
                    {
                        $this->design->assign('message_success', 'added');

                        $temp_id = $this->request->post('temp_id');
                        if ($temp_id)
                        {
                            $images = $this->image_temp->get_images($temp_id);
                            if (!empty($images)){
                                foreach($images as $i){
                                    $fname = $this->config->root_dir . '/' . $this->config->original_tempimages_dir . $i->filename;
                                    $this->image->add_internet_image('tags', $tag->id, $this->furl->generate_url($tag->name), $fname);
                                    $this->image_temp->delete_image($i->temp_id, $i->id);
                                }
                            }
                        }
                    }
                    else
                        $this->design->assign('message_error', 'error');
                }
                else
                    $this->design->assign('message_error', 'Тег с таким названием уже есть');
            }
            else
            {
                $this->tags->update_tag($tag->id, $tag);
                $this->design->assign('message_success', 'updated');
            }
            $tag = $this->tags->get_tag(intval($tag->id));

            if ($avalues)
            {
                $alternative_values = array();
                foreach($avalues as $field=>$av)
                    foreach($av as $tid=>$v)
                        $alternative_values[$tid]->$field = $v;

                foreach($alternative_values as $aid=>$av)
                {
                    if($av->id)
                        $this->tags->update_alternative_value($variant->id, $variant);
                    else
                    {
                        $this->tags->add_alternative_value(array('tag_id'=>$tag->id, 'name'=>$av->name));
                    }
                }
            }

            $group_id = $this->request->post('group_id');

            if ($close_after_save && $main_module)
                header("Location: ".$this->config->root_url.$main_module->url.$this->design->url_modifier(array('add'=>array('group_id'=>$group_id))));

            if ($add_after_save)
                header("Location: ".$this->config->root_url.$edit_module->url.$this->design->url_modifier(array('add'=>array('group_id'=>$group_id))));
        }
        else
            if ($this->request->method('post') && isset($_FILES['uploaded-images']))
            {
                $uploaded = $this->request->files('uploaded-images');
                $object_id = $this->request->post('object_id');

                if (is_numeric($object_id))
                {
                    $tmp_object = $this->tags->get_tag($object_id);
                    foreach($uploaded as $index=>$ufile)
                        $img = $this->image->add_image('tags', $object_id, $this->furl->generate_url($tmp_object->name), $ufile['name'], $ufile['tmp_name']);
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
                        case "group_id":
                            $this->design->assign('group_id', intval($v));
                            break;
                    }
                }

                if (!empty($id))
                {
                    $tag = $this->tags->get_tag($id);
                    if (!empty($tag) && $tag->is_auto && $mode != "toggle_popular")
                    {
                        unset($tag);
                        $id = 0;
                        unset($this->params_arr['id']);
                    }
                }
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

                if (!empty($mode) && ((isset($tag) && !empty($tag)) || !is_numeric($id)))
                    switch($mode){
                        case "delete":
                            $this->tags->delete_tag($id);
                            $response['success'] = true;
                            break;
                        case "toggle":
                            $this->tags->update_tag($id, array('is_enabled'=>1-$tag->is_enabled));
                            $response['success'] = true;
                            break;
                        case "toggle_popular":
                            $this->tags->update_tag($id, array('is_popular'=>1-$tag->is_popular));
                            $response['success'] = true;
                            break;
                        case "delete_alternative":
                            $alternative_id = intval($this->params_arr['alternative_id']);
                            $this->tags->delete_alternative_value($alternative_id);
                            $response['success'] = true;
                            break;
                        case "get_images":
                            $this->design->assign('object', $tag);

                            if (is_numeric($id))
                                $images = $this->image->get_images('tags', $id);
                            else
                            {
                                $images = $this->image_temp->get_images($id);
                                $this->design->assign('temp_id', $id);
                            }

                            $this->design->assign('images', $images);
                            $this->design->assign('images_object_name', 'tags');
                            $response['success'] = true;
                            $response['data'] = $this->design->fetch($this->design->getTemplateDir('admin').'object-images.tpl');
                            break;
                        case "add_alternative":
                            $name = $this->params_arr['name'];
                            $response['id'] = $this->tags->add_alternative_value(array('tag_id'=>$id, 'name'=>$name));
                            if ($response['id'])
                                $response['success'] = true;
                            break;
                        case "delete_image":
                            $image_id = intval($this->params_arr['image_id']);

                            if (is_numeric($id))
                                $this->image->delete_image('tags', $id, $image_id);
                            else
                                $this->image_temp->delete_image($id, $image_id);

                            $response['success'] = true;
                            break;
                        case "upload_internet_image":
                            $image_url = base64_decode($this->params_arr['image_url']);

                            if (is_numeric($id))
                                $this->image->add_internet_image('tags', $id, $this->furl->generate_url($tag->name), $image_url);
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
        if (isset($tag))
        {
            $this->design->assign('tag', $tag);
            if (!empty($tag->id))
            {
                $this->design->assign('alternative_values', $this->tags->get_alternative_values(array('tag_id'=>$tag->id)));
                $images = $this->image->get_images('tags', $tag->id);
                $this->design->assign('images', $images);
            }
        }
        $this->design->assign('tags_groups', $this->tags->get_taggroups(array('is_auto'=>0)));
        return $this->design->fetch($this->design->getTemplateDir('admin').'value.tpl');
    }
}