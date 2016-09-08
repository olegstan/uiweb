<?php
namespace app\controllers;

use core\Controller;

class BrandsControllerAdmin extends Controller
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

        $brands_filter = array();
        $current_page = 1;

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
                            $this->brands->update_brand($id, array('position'=>$position));

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
                        $brands_filter[$p] = $this->params_arr[$p];
                        $this->design->assign('keyword', $brands_filter['keyword']);
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
                        $brands_filter[$p] = $this->params_arr[$p];
                    else
                        unset($this->params_arr[$p]);
                    break;
                case "sort_type":
                    if (!empty($this->params_arr[$p]))
                    {
                        if (!array_key_exists('sort_type', $brands_filter))
                            $brands_filter[$p] = $this->params_arr[$p];
                    }
                    else
                        unset($this->params_arr[$p]);
                    break;
            }
        }

        /*Зададим сортировку по умолчанию*/
        if (!array_key_exists('sort', $this->params_arr))
        {
            //$this->params_arr['sort'] = 'name';
            //$this->params_arr['sort_type'] = 'asc';
            $brands_filter['sort'] = 'name';
            //$brands_filter['sort_type'] = 'asc';
        }
        //else
            if (!array_key_exists('sort_type', $this->params_arr))
            {
                //$this->params_arr['sort_type'] = 'asc';
                $brands_filter['sort_type'] = 'asc';
            }

        $this->design->assign('current_params', $this->params_arr);

        $brands_count = $this->brands->count_brands($brands_filter);

        // Постраничная навигация
        if (array_key_exists('page', $this->params_arr) && $this->params_arr['page'] == 'all')
            $items_per_page = $brands_count;
        else
            $items_per_page = $this->settings->brands_num_admin;

        $this->design->assign('brands_count', $brands_count);
        $pages_num = $items_per_page>0 ? ceil($brands_count/$items_per_page): 0;
        $this->design->assign('total_pages_num', $pages_num);

        // Если страница не задана, то равна 1
        $current_page = max(1, $current_page);
        $current_page = min($current_page, $pages_num);
        $this->design->assign('current_page_num', $current_page);

        $brands_filter['page'] = $current_page;
        $brands_filter['limit'] = $items_per_page;

        $brands = $this->brands->get_brands($brands_filter);
        foreach($brands as $index=>$brand)
            $brands[$index]->products_count = $this->products->count_products(array('brand_id'=>$brand->id));

        $this->design->assign('brands', $brands);
        $this->design->assign('params_arr', $this->params_arr);
        $this->design->assign('edit_module', $this->furl->get_module_by_name('BrandControllerAdmin'));
        return $this->design->fetch($this->design->getTemplateDir('admin').'brands.tpl');
    }
}