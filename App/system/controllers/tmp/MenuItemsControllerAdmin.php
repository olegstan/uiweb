<?php
namespace app\controllers;

use core\Controller;

class MenuItemsControllerAdmin extends Controller
{
    private $param_url, $options;

    public function set_params($url = null, $options = null)
    {
        $this->param_url = $url;
        $this->options = $options;
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

        $menu_id = 0;

        $this->param_url = trim($this->param_url, '/');

        if ($this->param_url == "save_positions")
        {
            $menu_items = $this->request->post('menu');
            $menu = array();
            foreach($menu_items as $item)
                $this->process_menu($item, 0, $menu);
            foreach($menu as $parent_id=>$items)
                foreach($items as $position=>$id)
                    $this->menu->update_item($id, array('parent_id'=>$parent_id, 'position'=>$position));

            header("Content-type: application/json; charset=UTF-8");
            header("Cache-Control: must-revalidate");
            header("Pragma: no-cache");
            header("Expires: -1");
            print json_encode(1);
            die();
        }
        elseif (!empty($this->param_url))
            $menu_id = intval($this->param_url);

        $this->design->assign('menu_id', $menu_id);
        $this->design->assign('menu_items', $this->menu->get_menu_tree($menu_id));
        $this->design->assign('edit_module', $this->furl->get_module_by_name('MenuItemControllerAdmin'));
        return $this->design->fetch($this->design->getTemplateDir('admin').'menu-items.tpl');
    }
}