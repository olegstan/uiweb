<?php
namespace app\controllers;

use core\Controller;

class MaterialsMenuItemsControllerAdmin extends Controller
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

    private function process_menu($item, $parent_id, &$menu)
    {
        if (!array_key_exists($parent_id, $menu))
            $menu[$parent_id] = array();
        $menu[$parent_id][] = intval($item['id']);
        if (isset($item['children']))
            foreach($item['children'] as $i)
                $this->process_menu($i, intval($item['id']), $menu);
    }

    private function process_item($item, $menu_id, $level, &$new_array, $exclude)
    {
        if ($item->menu_id != $menu_id || in_array($item->id, $exclude))
            return;
        $new_array[] = array('id'=>$item->id, 'text'=>str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level).$item->name, 'class'=>'level'.$level);
        if (isset($item->subitems))
            foreach($item->subitems as $subitem)
                if ($subitem->menu_id == $menu_id && !in_array($subitem->id, $exclude))
                    $this->process_item($subitem, $menu_id, $level+1, $new_array, $exclude);
    }

    function fetch()
    {
        if (!(isset($_SESSION['admin']) && $_SESSION['admin']=='admin'))
            header("Location: http://".$_SERVER['SERVER_NAME']."/admin/login/");

        $json_answer = false;
        $action = "";
        $id = 0;
        $response = array('success' => false);
        $keyword = "";
        $filter = array();

        foreach($this->params_arr as $p=>$v)
        {
            switch ($p)
            {
                case "id":
                    $id = intval($this->params_arr[$p]);
                    break;
                case "menu_id":
                    $menu_id = intval($this->params_arr[$p]);
                    break;
                case "exclude":
                    $exclude = explode(',',$this->params_arr[$p]);
                    break;
                case "action":
                    $action = $this->params_arr[$p];
                    break;
                case "save_positions":
                    $menu_items = $this->request->post('menu');
                    $collapsed = $this->request->post('collapsed');

                    $menu = array();
                    foreach($menu_items as $item)
                        $this->process_menu($item, 0, $menu);
                    foreach($menu as $parent_id=>$items)
                        foreach($items as $position=>$id)
                            $this->materials->update_menu_item($id, array('parent_id'=>$parent_id, 'position'=>$position));

                    header("Content-type: application/json; charset=UTF-8");
                    header("Cache-Control: must-revalidate");
                    header("Pragma: no-cache");
                    header("Expires: -1");
                    print json_encode(1);
                    die();
                    break;
                case "keyword":
                    $filter[$p] = $this->params_arr[$p];
                    $this->design->assign('keyword', $filter[$p]);
                    break;
            }
        }

        if (!empty($action))
        {
            $json_answer = true;
            switch($action){
                case "get_menu_items":
                    $items_tree = $this->materials->get_menu_items_tree();
                    $new_items = array(array('id'=>0, 'text'=>'Корень', 'class'=>'level0'));

                    foreach($items_tree as $item)
                        $this->process_item($item, $menu_id, 0, $new_items, $exclude);

                    $response['success'] = true;
                    $response['data'] = $new_items;
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

        $menus = $this->materials->get_menus();

        if (!array_key_exists('menu_id', $this->params_arr))
        {
            $default_menu = @reset($menus);
            if ($default_menu)
                $this->params_arr['menu_id'] = $default_menu->id;
        }

        $menu_items_filtered = $this->materials->get_menu_items_filter($filter);
        $items_ids = array();
        foreach($menu_items_filtered as $cf)
            $items_ids[] = $cf->id;
        $this->design->assign('items_filtered_ids', $items_ids);

        $tree = $this->materials->get_menu_items_tree();
        $new_tree = array();
        foreach($tree as $t)
        {
            if ($t->menu_id != $this->params_arr['menu_id'])
                continue;

            if (count(array_intersect($t->children, $items_ids))==0)
                continue;

            $new_tree[] = $t;
        }

        $this->design->assign('menu_items', $new_tree);
        $this->design->assign('current_params', $this->params_arr);
        $this->design->assign('params_arr', $this->params_arr);
        $this->design->assign('menu', $menus);
        $this->design->assign('edit_module', $this->furl->get_module_by_name('MaterialsMenuItemControllerAdmin'));
        return $this->design->fetch($this->design->getTemplateDir('admin').'materials-menu-items.tpl');
    }
}