<?php
namespace app\controllers;

use core\Controller;

class AccountSettingsControllerAdmin extends Controller
{
    private $param_url, $params_arr, $options;

    public function set_params($url = null, $options = null)
    {
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
    }

    private function process_menu($item, $parent_id, &$menu)
    {
        if (!array_key_exists($parent_id, $menu))
            $menu[$parent_id] = array();
        $menu[$parent_id][] = intval($item['id']);
        if (isset($item['children']))
            foreach($item['children'] as $i)
                $this->process_menu($i, intval($item['id']), $menu);
    }

    function fetch()
    {
        if (!(isset($_SESSION['admin']) && $_SESSION['admin']=='admin'))
            header("Location: http://".$_SERVER['SERVER_NAME']."/admin/login/");

        $this->design->assign('params_arr', $this->params_arr);
        //$this->design->assign('edit_module', $this->furl->get_module_by_name('UserGroupControllerAdmin'));
        return $this->design->fetch($this->design->getTemplateDir('admin').'account-settings.tpl');
    }
}