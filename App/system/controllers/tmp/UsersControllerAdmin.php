<?php
namespace app\controllers;

use core\Controller;

class UsersControllerAdmin extends Controller
{
    private $param_url, $params_arr, $options;

    public function set_params($url = null, $options = null)
    {
        $this->options = $options;
        $url = trim($url, '/');
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

        $ajax = false;
        $users_filter = array();
        $current_page = 1;

        foreach($this->params_arr as $p=>$v)
        {
            switch ($p)
            {
                case "group_id":
                    if (!empty($this->params_arr[$p]))
                        $users_filter[$p] = $v;
                    else
                        unset($this->params_arr[$p]);
                    break;
                case "keyword":
                    if (!empty($this->params_arr[$p]))
                    {
                        $users_filter[$p] = urldecode($v);
                        $this->design->assign('keyword', $users_filter['keyword']);
                    }
                    else
                        unset($this->params_arr[$p]);
                    break;
                case "page":
                    if (!empty($this->params_arr[$p]))
                        $current_page = intval($v);
                    else
                        unset($this->params_arr[$p]);
                    break;
                case "ajax":
                    $ajax = intval($v);
                    unset($this->params_arr[$p]);
                    break;
            }
        }

        $this->design->assign('current_params', $this->params_arr);

        // Если не задана, то равна 1
        $current_page = max(1, $current_page);
        $this->design->assign('current_page_num', $current_page);

        $all_users_count = $this->users->count_users();
        $users_count = $this->users->count_users($users_filter);

        // Постраничная навигация
        if (array_key_exists('page', $this->params_arr) && $this->params_arr['page'] == 'all')
            $items_per_page = $users_count;
        else
            $items_per_page = $this->settings->products_num_admin;

        $this->design->assign('all_users_count', $all_users_count);
        $this->design->assign('users_count', $users_count);
        $pages_num = ceil($users_count/$items_per_page);
        $this->design->assign('total_pages_num', $pages_num);

        $users_filter['page'] = $current_page;
        $users_filter['limit'] = $items_per_page;

        $user_groups = $this->users->get_groups();
        foreach($user_groups as $index=>$group)
            $user_groups[$index]->users_count = $this->users->count_users(array('group_id'=>$group->id));
        $this->design->assign('user_groups', $user_groups);

        $users = $this->users->get_users($users_filter);
        foreach($users as $index=>$user)
            $users[$index]->group = $this->users->get_group($user->group_id);
        $this->design->assign('users', $users);

        $this->design->assign('params_arr', $this->params_arr);
        $this->design->assign('edit_module', $this->furl->get_module_by_name('UserControllerAdmin'));

        if ($ajax)
        {
            $data['users'] = $this->design->fetch($this->design->getTemplateDir('admin').'users-refreshpart.tpl');
            header("Content-type: application/json; charset=UTF-8");
            header("Cache-Control: must-revalidate");
            header("Pragma: no-cache");
            header("Expires: -1");
            print json_encode($data);
            die();
        }

        return $this->design->fetch($this->design->getTemplateDir('admin').'users.tpl');
    }
}