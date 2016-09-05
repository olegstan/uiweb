<?PHP

require_once('controllers/GlobalController.php');

class FilterForCategoryControllerAdmin extends Controller
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

        $edit_module = $this->furl->get_module_by_name('FilterForCategoryControllerAdmin');
        $main_module =  $this->furl->get_module_by_name('FiltersForCategoryControllerAdmin');
        $this->design->assign('main_module', $main_module);

        if ($this->request->method('post'))
        {
            $tag_set = new stdClass();
            $tag_set->id = $this->request->post('id', 'integer');
            $tag_set->name = $this->request->post('name', 'string');
            $tag_set->is_visible = $this->request->post('is_visible', 'boolean');

            $tags_groups = $this->request->post('tags_groups');
            $in_filter = $this->request->post('in_filter');
            $default_expand = $this->request->post('default_expand');

            //Проверим нет ли уже такого набора
            $count_sets = $this->tags->count_tags_sets(array('name'=>trim($tag_set->name)));

            if (!empty($tag_set->name))
            {
                $close_after_save = $this->request->post('close_after_save', 'integer');
                $add_after_save = $this->request->post('add_after_save', 'integer');

                if(empty($tag_set->id))
                {
                    if ($count_sets == 0)
                    {
                        $tag_set->id = $this->tags->add_tags_set($tag_set);
                        if ($tag_set->id)
                            $this->design->assign('message_success', 'added');
                        else
                            $this->design->assign('message_error', 'error');
                    }
                    else
                        $this->design->assign('message_error', 'Набор с таким названием уже есть');
                }
                else
                {
                    $this->tags->update_tags_set($tag_set->id, $tag_set);
                    $this->design->assign('message_success', 'updated');
                }

                $tag_set= $this->tags->get_tags_set(intval($tag_set->id));

                if (!empty($tag_set->id))
                {
                    $this->tags->delete_tags_set_tags($tag_set->id);
                    if (!empty($tags_groups))
                    {
                        $tags_groups = json_decode($tags_groups, true);
                        foreach($tags_groups as $idx=>$g)
                            $this->tags->add_tags_set_tag(array('set_id'=>$tag_set->id, 'tag_id'=>$g['id'], 'in_filter'=>$in_filter[$g['id']], 'position'=>$idx, 'default_expand'=>$default_expand[$g['id']]));
                    }

                    if ($close_after_save && $main_module)
                        header("Location: ".$this->config->root_url.$main_module->url);
                    if ($add_after_save)
                        header("Location: ".$this->config->root_url.$edit_module->url);
                }
            }
            else
                $this->design->assign('message_error', 'Название набора не может быть пустым');
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
                $tag_set = $this->tags->get_tags_set($id);

            if (!empty($mode) && $tag_set)
                switch($mode){
                    case "delete":
                        $this->tags->delete_tags_set($id);
                        $response['success'] = true;
                        break;
                    case "toggle":
                        $this->tags->update_tags_set($id, array('is_visible'=>1-$tag_set->is_visible));
                        $response['success'] = true;
                        break;
                    case "toggle_set_tag":
                        $tag_id = intval($this->params_arr['tag_id']);
                        $this->db->query("UPDATE __tags_sets_tags SET in_filter=1-in_filter WHERE set_id=? AND tag_id=?", $id, $tag_id);
                        $response['success'] = true;
                        break;
                    case "delete_set_tag":
                        $tag_id = intval($this->params_arr['tag_id']);
                        $this->db->query("DELETE FROM __tags_sets_tags WHERE set_id=? AND tag_id=?", $id, $tag_id);
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

        if (isset($tag_set))
        {
            $this->design->assign('tag_set', $tag_set);
            if (!empty($tag_set->id))
            {
                $tags_groups = $this->tags->get_tags_set_tags(array('set_id'=>$tag_set->id));
                $this->design->assign('tags_groups', $tags_groups);
            }
        }

        return $this->design->fetch($this->design->getTemplateDir('admin').'filter-for-category.tpl');
    }
}