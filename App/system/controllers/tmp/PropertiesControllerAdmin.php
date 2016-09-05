<?php
namespace app\controllers;

use core\Controller;

class PropertiesControllerAdmin extends Controller
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

    function fetch()
    {
        if (!(isset($_SESSION['admin']) && $_SESSION['admin']=='admin'))
            header("Location: http://".$_SERVER['SERVER_NAME']."/admin/login/");

        $groups_filter = array();
        $current_page = 1;
        $mode = "user-properties";

        foreach($this->params_arr as $p=>$v)
        {
            switch ($p)
            {
                case "save_positions":
                    $menu_items = $this->request->post('menu');
                    $menu = array();
                    foreach($menu_items as $item)
                        $this->process_menu($item, 0, $menu);
                    foreach($menu as $parent_id=>$items)
                        foreach($items as $position=>$id)
                            $this->tags->update_taggroup($id, array('position'=>$position));

                    header("Content-type: application/json; charset=UTF-8");
                    header("Cache-Control: must-revalidate");
                    header("Pragma: no-cache");
                    header("Expires: -1");
                    print json_encode(1);
                    die();
                    break;
                case "keyword":
                    if (!empty($this->params_arr[$p]))
                    {
                        $groups_filter[$p] = $this->params_arr[$p];
                        $this->design->assign('keyword', $groups_filter['keyword']);
                    }
                    else
                        unset($this->params_arr[$p]);
                    break;
                case "page":
                    if (!empty($this->params_arr[$p]))
                        $current_page = intval($this->params_arr['page']);
                    else
                        unset($this->params_arr[$p]);
                    break;
                case "sort":
                    if (!empty($this->params_arr[$p]))
                        $groups_filter[$p] = $this->params_arr[$p];
                    else
                        unset($this->params_arr[$p]);
                    break;
                case "sort_type":
                    if (!empty($this->params_arr[$p]))
                    {
                        if (!array_key_exists('sort_type', $groups_filter))
                            $groups_filter[$p] = $this->params_arr[$p];
                    }
                    else
                        unset($this->params_arr[$p]);
                    break;
                case "mode":
                    if (in_array($v, array('user-properties','auto-properties')))
                        $mode = $v;
                    break;
            }
        }

        /*Зададим сортировку по умолчанию*/
        if (!array_key_exists('sort', $this->params_arr))
        {
            $this->params_arr['sort'] = 'position';
            $this->params_arr['sort_type'] = 'asc';
            $groups_filter['sort'] = 'position';
            $groups_filter['sort_type'] = 'asc';
        }
        else
            if (!array_key_exists('sort_type', $this->params_arr))
            {
                $this->params_arr['sort_type'] = 'asc';
                $groups_filter['sort_type'] = 'asc';
            }

        $this->design->assign('current_params', $this->params_arr);

        // Если страница не задана, то равна 1
        $current_page = max(1, $current_page);
        $this->design->assign('current_page_num', $current_page);

        if ($mode == 'user-properties')
            $groups_filter['is_auto'] = 0;
        if ($mode == 'auto-properties')
            $groups_filter['is_auto'] = 1;
        $this->design->assign('mode', $mode);

        $groups_count = $this->tags->count_taggroups($groups_filter);

        // Постраничная навигация
        if (array_key_exists('page', $this->params_arr) && $this->params_arr['page'] == 'all')
            $items_per_page = $tags_count;
        else
            $items_per_page = $this->settings->properties_num_admin;

        $this->design->assign('groups_count', $groups_count);
        $pages_num = ceil($groups_count/$items_per_page);
        $this->design->assign('total_pages_num', $pages_num);

        $groups_filter['page'] = $current_page;
        $groups_filter['limit'] = $items_per_page;

        $this->design->assign('tags_groups', $this->tags->get_taggroups($groups_filter));
        $this->design->assign('params_arr', $this->params_arr);
        $this->design->assign('edit_module', $this->furl->get_module_by_name('PropertyControllerAdmin'));
        return $this->design->fetch($this->design->getTemplateDir('admin').'properties.tpl');
    }
}