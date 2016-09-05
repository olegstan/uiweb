<?php
namespace app\controllers;

use core\Controller;

class PropertyControllerAdmin extends Controller
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

        $edit_module = $this->furl->get_module_by_name('PropertyControllerAdmin');
        $main_module =  $this->furl->get_module_by_name('PropertiesControllerAdmin');
        $this->design->assign('main_module', $main_module);

        if ($this->request->method('post'))
        {
            $tag_group = new stdClass();
            $tag_group->id = $this->request->post('id', 'integer');
            $tag_group->name = $this->request->post('name', 'string');
            $tag_group->is_enabled = $this->request->post('is_enabled', 'boolean');
            $tag_group->prefix = $this->request->post('prefix');
            $tag_group->postfix = $this->request->post('postfix');
            $tag_group->help_text = $this->request->post('help_text');
            $tag_group->mode = $this->request->post('mode');
            $tag_group->show_in_frontend = $this->request->post('show_in_frontend', 'boolean');
            $tag_group->numeric_sort = $this->request->post('numeric_sort', 'boolean');
            $tag_group->diapason_step = $this->request->post('diapason_step');
            $tag_group->show_prefix_in_frontend_filter = $this->request->post('show_prefix_in_frontend_filter', 'boolean');
            $tag_group->show_in_product_list = $this->request->post('show_in_product_list', 'boolean');
            $tag_group->export2yandex = $this->request->post('export2yandex', 'boolean');
            /*$tag_group->show_flag = $this->request->post('show_flag', 'boolean');
            $tag_group->is_range = $this->request->post('is_range', 'boolean');
            $tag_group->is_global = $this->request->post('is_global', 'boolean');*/

            $close_after_save = $this->request->post('close_after_save', 'integer');
            $add_after_save = $this->request->post('add_after_save', 'integer');

            if (empty($tag_group->name))
            {
                $this->design->assign('message_error', 'empty_name');

            }
            else
            {
                if(empty($tag_group->id))
                {
                    $tag_group->id = $this->tags->add_taggroup($tag_group);
                    $this->design->assign('message_success', 'added');

                    if ($tag_group->mode == "logical")
                    {
                        $this->tags->add_tag(array('group_id'=>$tag_group->id, 'name'=>'да', 'is_enabled'=>1));
                        $this->tags->add_tag(array('group_id'=>$tag_group->id, 'name'=>'нет', 'is_enabled'=>1));
                    }
                }
                else
                {
                    $old_taggroup = $this->tags->get_taggroup(intval($tag_group->id));
                    if ($old_taggroup->is_auto)
                    {
                        unset($tag_group->name);
                        unset($tag_group->is_enabled);
                    }

                    if ($old_taggroup->mode != $tag_group->mode && $tag_group->mode == "logical")
                    {
                        $tags = $this->tags->get_tags(array('group_id'=>$tag_group->id));
                        foreach($tags as $t)
                            $this->tags->delete_tag($t->id);
                        $this->tags->add_tag(array('group_id'=>$tag_group->id, 'name'=>'да', 'is_enabled'=>1));
                        $this->tags->add_tag(array('group_id'=>$tag_group->id, 'name'=>'нет', 'is_enabled'=>1));
                    }

                    $this->tags->update_taggroup($tag_group->id, $tag_group);
                    $this->design->assign('message_success', 'updated');
                }
            }

            $tag_group= $this->tags->get_taggroup(intval($tag_group->id));
            $return_mode = $this->request->post('return_mode', 'string');
            $page = $this->request->post('page');

            if ($close_after_save && $main_module)
                header("Location: ".$this->config->root_url.$main_module->url.$this->design->url_modifier(array('add'=>array('mode'=>$return_mode, 'page'=>$page))));
            if ($add_after_save)
                header("Location: ".$this->config->root_url.$edit_module->url.$this->design->url_modifier(array('add'=>array('mode'=>$return_mode, 'page'=>$page))));
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
                        case "return_mode":
                            $this->design->assign('return_mode', $v);
                            break;
                        case "page":
                            $this->design->assign('page', intval($v));
                            break;
                    }
                }

                if (!empty($id))
                    $tag_group = $this->tags->get_taggroup($id);

                if (!empty($mode) && isset($tag_group) && !empty($tag_group))
                    switch($mode){
                        case "delete":
                            $this->tags->delete_taggroup($id);
                            $response['success'] = true;
                            break;
                        case "toggle":
                            $this->tags->update_taggroup($id, array('is_enabled'=>1-$tag_group->is_enabled));
                            $response['success'] = true;
                            break;
                        case "flag":
                            $this->tags->update_taggroup($id, array('show_in_frontend'=>1-$tag_group->show_in_frontend));
                            $response['success'] = true;
                            break;
                        case "list":
                            $this->tags->update_taggroup($id, array('show_in_product_list'=>1-$tag_group->show_in_product_list));
                            $response['success'] = true;
                            break;
                        case "market":
                            $this->tags->update_taggroup($id, array('export2yandex'=>1-$tag_group->export2yandex));
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
        if (isset($tag_group))
            $this->design->assign('tag_group', $tag_group);

        return $this->design->fetch($this->design->getTemplateDir('admin').'property.tpl');
    }
}