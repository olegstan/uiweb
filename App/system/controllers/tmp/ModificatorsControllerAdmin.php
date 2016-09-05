<?php
namespace app\controllers;

use core\Controller;

class ModificatorsControllerAdmin extends Controller
{
    private $param_url, $params_arr, $options;

    public function set_params($url = null, $options = null){
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
        $current_page = 1;
        $ajax = false;

        foreach($this->params_arr as $p=>$v)
        {
            switch ($p)
            {
                case "save_positions":
                    $menu_items = $this->request->post('menu');
                    foreach($menu_items as $position=>$mi)
                        $this->modificators->update_modificator($mi['id'], array('position'=>$position));
                    header("Content-type: application/json; charset=UTF-8");
                    header("Cache-Control: must-revalidate");
                    header("Pragma: no-cache");
                    header("Expires: -1");
                    print json_encode(1);
                    die();
                    break;
                case "ajax":
                    $ajax = intval($this->params_arr[$p]);
                    unset($this->params_arr[$p]);
                    break;
                case "parent_id":
                    $parent_id = intval($v);
                    if ($this->modificators->get_modificators_group($parent_id))
                        $filter['parent_id'] = intval($v);
                    else
                        unset($this->params_arr[$p]);
                    break;
            }
        }

        $this->design->assign('current_params', $this->params_arr);
        $modificators_count = $this->modificators->count_modificators();
        $modificators = $this->modificators->get_modificators($filter);

        foreach($modificators as $index=>$modificator)
        {
            $modificators[$index]->images = $this->image->get_images('modificators', $modificator->id);
            $modificators[$index]->image = reset($modificators[$index]->images);
        }

        $this->design->assign('params_arr', $this->params_arr);
        $this->design->assign('modificators_count', $modificators_count);
        $this->design->assign('modificators', $modificators);

        $this->design->assign('modificators_groups', $this->modificators->get_modificators_groups());

        $this->design->assign('edit_module', $this->furl->get_module_by_name('ModificatorControllerAdmin'));
        return $this->design->fetch($this->design->getTemplateDir('admin').'modificators.tpl');
    }
}