<?php
namespace app\controllers;

use core\Controller;

class UserGroupControllerAdmin extends Controller
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

        $edit_module = $this->furl->get_module_by_name('UserGroupControllerAdmin');
        $main_module =  $this->furl->get_module_by_name('UserGroupsControllerAdmin');
        $this->design->assign('main_module', $main_module);

        if ($this->request->method('post')){
            $user_group = new stdClass();
            $user_group->id = $this->request->post('id', 'integer');
            $user_group->name = $this->request->post('name', 'string');
            $user_group->group_name = $this->request->post('group_name', 'string');
            $user_group->discount = $this->request->post('discount');
            $user_group->css_class = $this->request->post('css_class', 'string');

            $allow_modules = (array)$this->request->post('modules');

            $close_after_save = $this->request->post('close_after_save', 'integer');
            $add_after_save = $this->request->post('add_after_save', 'integer');

            if(empty($user_group->id))
            {
                $user_group->id = $this->users->add_group($user_group);
                $this->design->assign('message_success', 'added');
            }
            else
            {
                $this->users->update_group($user_group->id, $user_group);
                $this->design->assign('message_success', 'updated');
            }

            $user_group = $this->users->get_group(intval($user_group->id));

            //Сохраняем права доступа
            $this->db->query("DELETE FROM __access_permissions WHERE group_id=?", $user_group->id);
            foreach(array_keys($allow_modules) as $module_id)
            {
                $this->db->query("SELECT am.id FROM __menu_modules mm INNER JOIN __access_modules am ON mm.module=am.name WHERE mm.id=? AND am.section=?", $module_id, 'admin');
                $access_id = $this->db->result();
                if ($access_id)
                    $this->db->query("INSERT INTO __access_permissions(group_id, module_id, value) VALUES(?,?,?)", $user_group->id, $access_id->id, 1);
            }

            if ($close_after_save && $main_module)
                header("Location: ".$this->config->root_url.$main_module->url);
            if ($add_after_save)
                header("Location: ".$this->config->root_url.$edit_module->url);
        }
        else
        {
            $id = 0;
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
                $user_group = $this->users->get_group($id);

            if (!empty($mode) && $user_group)
                switch($mode){
                    case "delete":
                        $response['success'] = $this->users->delete_group($id);
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

        if (isset($user_group))
        {
            $this->design->assign('user_group', $user_group);
            $query = $this->db->placehold("SELECT am.name FROM __access_modules am INNER JOIN __access_permissions ap ON am.id=ap.module_id WHERE am.section=? AND ap.group_id=?", 'admin', $user_group->id);
            $this->db->query($query);
            $group_permissions = $this->db->results('name');
            $this->design->assign('group_permissions', $group_permissions);
        }

        $this->db->query("SELECT ma.*, mm.module module_name, mm.name menuitem_name, mm.id menuitem_module_id FROM __menu_admin ma LEFT JOIN __menu_modules mm ON ma.module_id=mm.id OR ma.use_module_id=mm.id ORDER BY ma.parent_id, ma.position");
        $menu_items = array();
        $items = $this->db->results();

        // Дерево категорий
        $tree = new stdClass();
        $tree->subitems = array();

        // Указатели на узлы дерева
        $pointers = array();
        $pointers[0] = &$tree;
        $pointers[0]->path = array();
        $finish = false;
        // Не кончаем, пока не кончатся категории, или пока ниодну из оставшихся некуда приткнуть
        while(!empty($items)  && !$finish)
        {
            $flag = false;
            // Проходим все выбранные категории
            foreach($items as $k=>$item)
            {
                if(isset($pointers[$item->parent_id]))
                {
                    // В дерево категорий (через указатель) добавляем текущую категорию
                    $pointers[$item->id] = $pointers[$item->parent_id]->subitems[] = $item;

                    // Путь к текущей категории
                    $curr = clone($pointers[$item->id]);
                    $pointers[$item->id]->path = array_merge((array)$pointers[$item->parent_id]->path, array($curr));

                    // Убираем использованную категорию из массива категорий
                    unset($items[$k]);
                    $flag = true;
                }
            }
            if(!$flag)
                $finish = true;
        }

        // Для каждой категории id всех ее деток узнаем
        $ids = array_reverse(array_keys($pointers));
        foreach($ids as $id)
        {
            if($id>0)
            {
                $pointers[$id]->children[] = $id;

                if(isset($pointers[$pointers[$id]->parent_id]->children))
                    $pointers[$pointers[$id]->parent_id]->children = array_merge($pointers[$id]->children, $pointers[$pointers[$id]->parent_id]->children);
                else
                    $pointers[$pointers[$id]->parent_id]->children = $pointers[$id]->children;
            }
        }
        unset($pointers[0]);

        $this->design->assign('menu_items', $tree->subitems);

        return $this->design->fetch($this->design->getTemplateDir('admin').'user-group.tpl');
    }
}