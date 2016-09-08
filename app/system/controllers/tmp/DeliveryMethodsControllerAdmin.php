<?php
namespace app\controllers;

use core\Controller;

class DeliveryMethodsControllerAdmin extends Controller
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

        if (trim($this->param_url, '/') == "save_positions")
        {
            $menu_items = $this->request->post('menu');
            $menu = array();
            foreach($menu_items as $item)
                $this->process_menu($item, 0, $menu);
            foreach($menu as $parent_id=>$items)
                foreach($items as $position=>$id)
                    $this->deliveries->update_delivery($id, array('position'=>$position));

            header("Content-type: application/json; charset=UTF-8");
            header("Cache-Control: must-revalidate");
            header("Pragma: no-cache");
            header("Expires: -1");
            print json_encode(1);
            die();
        }

        $this->design->assign('deliveries', $this->deliveries->get_deliveries());
        $this->design->assign('edit_module', $this->furl->get_module_by_name('DeliveryMethodControllerAdmin'));
        return $this->design->fetch($this->design->getTemplateDir('admin').'delivery-methods.tpl');
    }
}