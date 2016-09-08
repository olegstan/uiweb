<?php
namespace app\controllers;

use core\Controller;

class MaterialsMenuControllerAdmin extends Controller
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

        $json_answer = false;
        $action = "";
        $id = 0;
        $response = array('success' => false);

        if ($this->request->method('post'))
        {
            $menu = new StdClass;
            $menu->id = $this->request->post('id');
            $menu->name = $this->request->post('name');
            $menu->is_visible = $this->request->post('is_visible', 'boolean');
            $menu->css_class = $this->request->post('css_class');

            if (empty($menu->name))
            {
                $this->design->assign('message_error', 'empty_name');
            }
            else
            {
                if(empty($menu->id))
                {
                    $menu->id = $this->materials->add_menu($menu);
                    $this->design->assign('message_success', 'added');
                }
                else
                {
                    $this->materials->update_menu($menu->id, $menu);
                    $this->design->assign('message_success', 'updated');
                }

                $menu = $this->materials->get_menu($menu->id);
            }
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
                }
            }

        if (!empty($id))
            $menu = $this->materials->get_menu($id);

        if (!empty($action))
        {
            $json_answer = true;
            switch($action){
                case "delete":
                    if ($menu){
                        $this->materials->delete_menu($id);
                        $response['success'] = true;
                    }
                    break;
                case "toggle":
                    if ($menu){
                        $this->materials->update_menu($id, array('is_visible'=>1-$menu->is_visible));
                        $response['success'] = true;
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

        if (isset($menu) && $menu)
            $this->design->assign('menu', $menu);

        $main_module =  $this->furl->get_module_by_name('MaterialsMenusControllerAdmin');
        $this->design->assign('main_module', $main_module);

        return $this->design->fetch($this->design->getTemplateDir('admin').'materials-menu.tpl');
    }
}