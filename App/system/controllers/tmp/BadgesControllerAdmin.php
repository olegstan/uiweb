<?php
namespace app\controllers;

use core\Controller;

class BadgesControllerAdmin extends GlobalController
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

        $filter = array();
        $filter['limit'] = 100;
        $current_page = 1;
        $ajax = false;

        foreach($this->params_arr as $p=>$v)
        {
            switch ($p)
            {
                case "save_positions":
                    $menu_items = $this->request->post('menu');
                    foreach($menu_items as $position=>$mi)
                        $this->badges->update_badge($mi['id'], array('position'=>$position));
                    header("Content-type: application/json; charset=UTF-8");
                    header("Cache-Control: must-revalidate");
                    header("Pragma: no-cache");
                    header("Expires: -1");
                    print json_encode(1);
                    die();
                    break;
                case "page":
                    if (!empty($this->params_arr[$p]))
                        $current_page = intval($this->params_arr[$p]);
                    else
                        unset($this->params_arr[$p]);
                    break;
                case "ajax":
                    $ajax = intval($this->params_arr[$p]);
                    unset($this->params_arr[$p]);
                    break;
            }
        }

        $this->design->assign('current_params', $this->params_arr);

        // Если страница не задана, то равна 1
        $current_page = max(1, $current_page);
        $this->design->assign('current_page_num', $current_page);

        $badges_count = $this->badges->count_badges($filter);

        // Постраничная навигация
        if (array_key_exists('page', $this->params_arr) && $this->params_arr['page'] == 'all')
            $items_per_page = $badges_count;
        else
            $items_per_page = $this->settings->products_num_admin;

        $filter['page'] = $current_page;
        $filter['limit'] = $items_per_page;

        $badges = $this->badges->get_badges($filter);

        $this->design->assign('params_arr', $this->params_arr);
        $this->design->assign('badges_count', $badges_count);
        $pages_num = ceil($badges_count/$items_per_page);
        $this->design->assign('total_pages_num', $pages_num);

        $this->design->assign('badges', $badges);
        $this->design->assign('edit_module', $this->furl->get_module_by_name('BadgeControllerAdmin'));
        return $this->design->fetch($this->design->getTemplateDir('admin').'badges.tpl');
    }
}