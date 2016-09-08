<?PHP

/**
 * Minicart CMS
 *
 * Этот класс использует шаблон seo.tpl
 *
 */
 
require_once('controllers/GlobalController.php');

class BannersControllerAdmin extends Controller
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

        $filter = array();
        $filter['limit'] = 100;
        $system_filter = $filter;
        $filter['is_system'] = 0;
        $system_filter['is_system'] = 1;
        $current_page = 1;
        $ajax = false;

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
                            $this->banners->update_banner($id, array('position'=>$position));

                    header("Content-type: application/json; charset=UTF-8");
                    header("Cache-Control: must-revalidate");
                    header("Pragma: no-cache");
                    header("Expires: -1");
                    print json_encode(1);
                    die();
                    break;
                case "page":
                    if (!empty($v))
                        $current_page = intval($v);
                    else
                        unset($this->params_arr[$p]);
                    break;
                case "ajax":
                    $ajax = intval($v);
                    unset($this->params_arr[$p]);
                    break;
                case "sort":
                    if (!empty($this->params_arr[$p]))
                        $filter[$p] = $this->params_arr[$p];
                    else
                        unset($this->params_arr[$p]);
                    break;
                case "sort_type":
                    if (!empty($this->params_arr[$p]))
                    {
                        if (!array_key_exists('sort_type', $filter))
                            $filter[$p] = $this->params_arr[$p];
                    }
                    else
                        unset($this->params_arr[$p]);
                    break;
            }
        }

        /*Зададим сортировку по умолчанию*/
        if (!array_key_exists('sort', $this->params_arr))
        {
            $filter['sort'] = 'position';
        }
        if (!array_key_exists('sort_type', $this->params_arr))
        {
            $filter['sort_type'] = 'asc';
        }
        $this->design->assign('current_params', $this->params_arr);

        // Если страница не задана, то равна 1
        $current_page = max(1, $current_page);
        $this->design->assign('current_page_num', $current_page);

        $banners_count = $this->banners->count_banners($filter);
        $system_banners_count = $this->banners->count_banners($system_filter);

        // Постраничная навигация
        //if (array_key_exists('page', $this->params_arr) && $this->params_arr['page'] == 'all')
            $items_per_page = $banners_count;
        /*else
            $items_per_page = $this->settings->products_num_admin;*/

        $filter['page'] = $current_page;
        $filter['limit'] = $items_per_page;

        $banners = $this->banners->get_banners($filter);
        $system_banners = $this->banners->get_banners($system_filter);

        $this->design->assign('params_arr', $this->params_arr);
        $this->design->assign('banners_count', $banners_count);
        $this->design->assign('system_banners_count', $system_banners_count);
        $pages_num = ceil($banners_count/$items_per_page);
        $this->design->assign('total_pages_num', $pages_num);

        $this->design->assign('banners', $banners);
        $this->design->assign('system_banners', $system_banners);
        $this->design->assign('edit_module', $this->furl->get_module_by_name('BannerControllerAdmin'));

        return $this->design->fetch($this->design->getTemplateDir('admin').'banners.tpl');
    }
}