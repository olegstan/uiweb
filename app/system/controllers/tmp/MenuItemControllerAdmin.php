<?php
namespace app\controllers;

use core\Controller;

class MenuItemControllerAdmin extends Controller
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

        $main_module =  $this->furl->get_module_by_name('MenuItemsControllerAdmin');
        $edit_module =  $this->furl->get_module_by_name('MenuItemControllerAdmin');
        $this->design->assign('main_module', $main_module);

        if ($this->request->method('post'))
        {
            $menu_item = new stdClass();
            $menu_item->id = $this->request->post('id', 'integer');
            $menu_item->menu_id = $this->request->post('menu_id', 'integer');
            $menu_id = $menu_item->menu_id;
            $menu_item->name = $this->request->post('name', 'string');
            $menu_item->parent_id = $this->request->post('parent_id', 'integer');
            $menu_item->css_class = $this->request->post('css_class', 'string');
            $menu_item->is_visible = $this->request->post('is_visible', 'boolean');
            $menu_item->use_default = $this->request->post('use_default', 'boolean');
            $menu_item->icon = $this->request->post('icon');
            $close_after_save = $this->request->post('close_after_save', 'integer');
            $add_after_save = $this->request->post('add_after_save', 'integer');

            if(empty($menu_item->id))
            {
                $menu_item->id = $this->menu->add_item($menu_item);
                $this->design->assign('message_success', 'added');
            }
            else
            {
                $this->menu->update_item($menu_item->id, $menu_item);
                $this->design->assign('message_success', 'updated');
            }
            $menu_item = $this->menu->get_item(intval($menu_item->id));

            if ($close_after_save && $main_module)
                header("Location: ".$this->config->root_url.$main_module->url.$menu_id."/");

            if ($add_after_save)
                header("Location: ".$this->config->root_url.$edit_module->url.'?menu_id='.$menu_item->menu_id);
        }
        else
        {
            $id = 0;
            $menu_id = 0;
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
                    case "menu_id":
                        if (is_numeric($v))
                            $menu_id = intval($v);
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
                $menu_item = $this->menu->get_item($id);

            if (!array_key_exists("menu_id", $this->params_arr) && !empty($menu_item))
                $menu_id = $menu_item->menu_id;

            $this->design->assign('menu_id', $menu_id);

            if (!empty($mode) && $menu_item)
                switch($mode){
                    case "delete":
                        foreach($menu_item->children as $c_id)
                            $this->menu->delete_item($c_id);
                        $response['success'] = true;
                        break;
                    case "toggle":
                        $this->menu->update_item($id, array('is_visible'=>1-$menu_item->is_visible));
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

        if (isset($menu_item))
            $this->design->assign('menu_item', $menu_item);
        $this->design->assign('menu_items', $this->menu->get_menu_tree($menu_id));
        return $this->design->fetch($this->design->getTemplateDir('admin').'menu-item.tpl');
    }
}