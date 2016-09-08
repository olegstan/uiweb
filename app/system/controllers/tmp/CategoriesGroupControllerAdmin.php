<?php
namespace app\controllers;

use core\Controller;

class CategoriesGroupControllerAdmin extends Controller
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

        $main_module =  $this->furl->get_module_by_name('CategoriesGroupsControllerAdmin');
        $edit_module = $this->furl->get_module_by_name('CategoriesGroupControllerAdmin');
        $this->design->assign('category_module', $this->furl->get_module_by_name('CategoryControllerAdmin'));
        $this->design->assign('main_module', $main_module);

        if ($this->request->method('post'))
        {
            $group_on_main = new stdClass();
            $group_on_main->id = $this->request->post('id', 'integer');
            $group_on_main->name = $this->request->post('name');
            $group_on_main->is_visible = $this->request->post('is_visible', 'boolean');
            $group_on_main->categories_count = $this->request->post('categories_count', 'integer');
            $group_on_main->css_class = $this->request->post('css_class');

            $related_ids = $this->request->post('related_ids');

            $close_after_save = $this->request->post('close_after_save', 'integer');
            $add_after_save = $this->request->post('add_after_save', 'integer');

            if (empty($group_on_main->name))
            {
                $this->design->assign('message_error', 'empty_name');
            }
            else
            {
                if(empty($group_on_main->id))
                {
                    $group_on_main->id = $this->blocks->add_categories_block($group_on_main);
                    $this->design->assign('message_success', 'added');
                }
                else
                {
                    $old_block = $this->blocks->get_categories_block($group_on_main->id);
                    $this->blocks->update_categories_block($group_on_main->id, $group_on_main);
                    $this->design->assign('message_success', 'updated');
                }

                // Сопутствующие товары
                $query = $this->db->placehold('DELETE FROM __groups_related_categories WHERE group_id=?', $group_on_main->id);
                $this->db->query($query);
                if(is_array($related_ids))
                {
                    $pos = 0;
                    foreach($related_ids  as $i=>$related_id)
                        $this->blocks->add_related_category($group_on_main->id, $related_id, $pos++);
                }

                $group_on_main = $this->blocks->get_categories_block(intval($group_on_main->id));

                if ($close_after_save && $main_module)
                {
                    $href = $this->config->root_url.$main_module->url;
                    header("Location: ".$href);
                }

                if ($add_after_save)
                {
                    $href = $this->config->root_url.$edit_module->url;
                    header("Location: ".$href);
                }
            }
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
                    $group_on_main = $this->blocks->get_categories_block($id);

                if (!empty($mode) && isset($group_on_main) && !empty($group_on_main))
                    switch($mode){
                        case "delete":
                            $this->blocks->delete_categories_block($id);
                            $response['success'] = true;
                            break;
                        case "toggle":
                            $this->blocks->update_categories_block($id, array('is_visible'=>1-$group_on_main->is_visible));
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
        
        if (isset($group_on_main))
        {
            $this->design->assign('group_on_main', $group_on_main);

            if ($group_on_main->id){
                //Сопутствующие категории
                $related_categories = $this->blocks->get_related_categories(array('group_id'=>$group_on_main->id));
                if ($related_categories)
                {
                    foreach($related_categories as $index=>$related)
                    {
                        $related_categories[$index] = $this->categories->get_category($related->category_id);
                        $related_categories[$index]->images = $this->image->get_images('categories', $related->category_id);
                        if (isset($related_categories[$index]->images[0]))
                            $related_categories[$index]->image = $related_categories[$index]->images[0];
                    }
                    $this->design->assign('related_categories', $related_categories);
                }
            }
        }

        $this->design->assign('current_params', $this->params_arr);

        return $this->design->fetch($this->design->getTemplateDir('admin').'categories-group.tpl');
        }
}