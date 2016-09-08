<?PHP

require_once('controllers/GlobalController.php');

class ModificatorsOrdersGroupsControllerAdmin extends GlobalController
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

        foreach($this->params_arr as $p=>$v)
        {
            switch ($p)
            {
                case "save_positions":
                    $menu_items = $this->request->post('menu');
                    foreach($menu_items as $position=>$mi)
                        $this->modificators->update_modificator_orders_group($mi['id'], array('position'=>$position));
                    header("Content-type: application/json; charset=UTF-8");
                    header("Cache-Control: must-revalidate");
                    header("Pragma: no-cache");
                    header("Expires: -1");
                    print json_encode(1);
                    die();
                    break;
            }
        }

        $this->design->assign('current_params', $this->params_arr);
        $this->design->assign('params_arr', $this->params_arr);

        $this->design->assign('modificators_groups', $this->modificators->get_modificators_orders_groups());
        $this->design->assign('modificators_groups_count', $this->modificators->count_modificators_orders_groups());

        $this->design->assign('edit_module', $this->furl->get_module_by_name('ModificatorsOrdersGroupControllerAdmin'));

        return $this->design->fetch($this->design->getTemplateDir('admin').'modificators-orders-groups.tpl');
    }
}