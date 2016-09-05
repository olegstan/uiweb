<?php
namespace app\controllers;

use core\Controller;

class MaterialsMenuItemControllerAdmin extends Controller
{
    private $param_url, $params_arr, $options;

    /*public function set_params($url = null, $options = null){
        $this->param_url = urldecode(trim($url, '/'));
        $this->options = $options;

        $this->params_arr = array();
        foreach(explode("&", $this->param_url) as $p)
        {
            $x = explode("=", $p);
            $this->params_arr[$x[0]] = "";
            if (count($x)>1)
                $this->params_arr[$x[0]] = $x[1];
        }
    }*/
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

        $edit_module = $this->furl->get_module_by_name('MaterialsMenuItemControllerAdmin');
        $main_module =  $this->furl->get_module_by_name('MaterialsMenuItemsControllerAdmin');
        $this->design->assign('main_module', $main_module);

        $json_answer = false;
        $action = "";
        $id = 0;
        $response = array('success' => false);

        if ($this->request->method('post'))
        {
            $menu_item = new StdClass;
            $menu_item->id = $this->request->post('id');
            $menu_item->name = $this->request->post('name');
            $menu_item->is_visible = $this->request->post('is_visible', 'boolean');
            $menu_item->menu_id = $this->request->post('menu_id');
            $menu_item->parent_id = $this->request->post('parent_id');
            $menu_item->object_type = $this->request->post('object_type');
            $menu_item->object_text = $this->request->post('object_text');
            $menu_item->css_class = $this->request->post('css_class');
            $menu_item->is_main = $this->request->post('is_main', 'boolean');

            $object_id_select2 = $this->request->post('object_id_select2');
            $object_id_select = $this->request->post('object_id_select');

            $close_after_save = $this->request->post('close_after_save', 'integer');
            $add_after_save = $this->request->post('add_after_save', 'integer');

            switch($menu_item->object_type){
                case 'material':
                    $menu_item->object_id = $object_id_select2;
                    break;
                case 'material-category':
                    $menu_item->object_id = $object_id_select;
                    break;
                case 'product':
                    $menu_item->object_id = $object_id_select2;
                    break;
                case 'category':
                    $menu_item->object_id = $object_id_select2;
                    break;
                default:
                    $menu_item->object_id = 0;
                    break;
            }

            if (empty($menu_item->name))
            {
                $this->design->assign('message_error', 'empty_name');
            }
            else if ($menu_item->object_type == 'material' && empty($menu_item->object_id))
            {
                $this->design->assign('message_error', 'empty_material');
            }
            else if ($menu_item->object_type == 'material-category' && empty($menu_item->object_id))
            {
                $this->design->assign('message_error', 'empty_material_category');
            }
            else if ($menu_item->object_type == 'product' && empty($menu_item->object_id))
            {
                $this->design->assign('message_error', 'empty_product');
            }
            else if ($menu_item->object_type == 'category' && empty($menu_item->object_id))
            {
                $this->design->assign('message_error', 'empty_category');
            }
            else
            {
                if(empty($menu_item->id))
                {
                    $menu_item->id = $this->materials->add_menu_item($menu_item);
                    $this->design->assign('message_success', 'added');
                }
                else
                {
                    $this->materials->update_menu_item($menu_item->id, $menu_item);
                    $this->design->assign('message_success', 'updated');
                }

                $menu_item = $this->materials->get_menu_item($menu_item->id);
            }

            $posted_menu_id = $this->request->post('posted_menu_id');

            if ($close_after_save && $main_module)
                header("Location: ".$this->config->root_url.$main_module->url.$this->design->url_modifier(array('add'=>array('menu_id'=>$posted_menu_id))));

            if ($add_after_save)
                header("Location: ".$this->config->root_url.$edit_module->url.$this->design->url_modifier(array('add'=>array('menu_id'=>$posted_menu_id))));
        }
        else
            foreach($this->params_arr as $p=>$v)
            {
                switch ($p)
                {
                    case "id":
                        if (is_numeric($this->params_arr[$p]))
                            $id = intval($this->params_arr[$p]);
                        break;
                    case "action":
                        $action = $this->params_arr[$p];
                        break;
                    case "menu_id":
                        $this->design->assign('menu_id', $this->params_arr[$p]);
                        break;
                }
            }

        if (!empty($action))
        {
            $json_answer = true;
            switch($action){
                case "toggle":
                    $tmp_menu_item = $this->materials->get_menu_item($id);
                    if ($tmp_menu_item)
                    {
                        $this->materials->update_menu_item($id, array('is_visible'=>1-$tmp_menu_item->is_visible));
                        $response['success'] = true;
                    }
                    break;
                case "delete":
                    $this->materials->delete_menu_item($id);
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

        if (!empty($id))
            $menu_item = $this->materials->get_menu_item($id);

        if (isset($menu_item))
        {
            if ($menu_item->object_type == "material" && $menu_item->object_id>0)
            {
                $object = $this->materials->get_material($menu_item->object_id);
                if ($object)
                    $this->design->assign('select2_text', $object->name);
            }
            if ($menu_item->object_type == "product" && $menu_item->object_id>0)
            {
                $object = $this->products->get_product($menu_item->object_id);
                $this->design->assign('select2_text', $object->name);
            }
            if ($menu_item->object_type == "category" && $menu_item->object_id>0)
            {
                $object = $this->categories->get_category($menu_item->object_id);
                $this->design->assign('select2_text', $object->name);
            }
            $this->design->assign('pages_menu_item', $menu_item);
        }

        $main_module =  $this->furl->get_module_by_name('MaterialsMenuItemsControllerAdmin');
        $this->design->assign('main_module', $main_module);
        $this->design->assign('materials_module', $this->furl->get_module_by_name('MaterialsControllerAdmin'));
        $this->design->assign('materials_categories_module', $this->furl->get_module_by_name('MaterialsCategoriesControllerAdmin'));
        $this->design->assign('products_module', $this->furl->get_module_by_name('ProductsControllerAdmin'));
        $this->design->assign('categories_module', $this->furl->get_module_by_name('CategoriesControllerAdmin'));
        $this->design->assign('menu', $this->materials->get_menus());

        return $this->design->fetch($this->design->getTemplateDir('admin').'materials-menu-item.tpl');
    }
}