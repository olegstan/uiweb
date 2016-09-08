<?php
namespace app\controllers;

use core\Controller;

class MaterialsCategoriesControllerAdmin extends Controller
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

    private function calc_materials_count(&$node)
    {
        $node->materials_count = $this->materials->count_materials(array('category_id'=>$node->children));
        if (isset($node->subcategories))
            foreach($node->subcategories as $subnode)
                $this->calc_materials_count($subnode);
    }

    private function process_category($category, $level, &$new_array)
    {
        $new_array[] = array('id'=>$category->id, 'text'=>str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level).$category->name, 'class'=>'level'.$level);
        if (isset($category->subcategories))
            foreach($category->subcategories as $subcategory)
                $this->process_category($subcategory, $level+1, $new_array);
    }

    function fetch()
    {
        if (!(isset($_SESSION['admin']) && $_SESSION['admin']=='admin'))
            header("Location: http://".$_SERVER['SERVER_NAME']."/admin/login/");

        $filter = array();
        $filter['limit'] = 10000;
        $current_page = 1;
        $ajax = false;
        $output_format = "";

        $lazy_load = false;
        $lazy_update = false;
        $lazy_update_ids = array();
        $lazy_filter = array();

        foreach($this->params_arr as $p=>$v)
        {
            switch ($p)
            {
                case "save_positions":
                    $menu_items = $this->request->post('menu');
                    $collapsed = $this->request->post('collapsed');

                    $menu = array();
                    foreach($menu_items as $item)
                        $this->process_menu($item, 0, $menu);
                    foreach($menu as $parent_id=>$items)
                        foreach($items as $position=>$id)
                            $this->materials->update_category($id, array('parent_id'=>$parent_id, 'position'=>$position, 'collapsed'=>$collapsed[$id]));

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
                        $filter[$p] = $this->params_arr[$p];
                        $this->design->assign('keyword', $filter[$p]);
                    }
                    else
                        unset($this->params_arr[$p]);
                    break;
                case "page":
                    if (!empty($this->params_arr[$p]))
                        $current_page = intval($this->params_arr[$p]);
                    else
                        unset($this->params_arr[$p]);
                    break;
                case "lazy_load":
                    $lazy_load = true;
                    break;
                case "lazy_update":
                    $lazy_update = true;
                    break;
                case "ids":
                    $lazy_update_ids = explode(',', $v);
                    break;
                case "parent_id":
                    $lazy_filter['parent_id'] = $this->params_arr[$p];
                    break;
                case "is_visible":
                    $lazy_filter['is_visible'] = $this->params_arr[$p];
                    break;
                case "ajax":
                    $ajax = intval($this->params_arr[$p]);
                    unset($this->params_arr[$p]);
                    break;
                case "format":
                    $output_format = $this->params_arr[$p];
                    unset($this->params_arr[$p]);
                    break;
            }
        }
        if ($lazy_load)
        {
            $result = $this->materials->get_categories_lazy_load_filter($lazy_filter);
            header("Content-type: application/json; charset=UTF-8");
            header("Cache-Control: must-revalidate");
            header("Pragma: no-cache");
            header("Expires: -1");
            print json_encode($result);
            die();
        }

        if ($lazy_update)
        {
            $result = array();
            foreach($lazy_update_ids as $lid)
            {
                $tmp_cat = $this->materials->get_category($lid);
                if ($tmp_cat)
                    $result[] = array('id'=>$lid, 'value'=>$this->materials->count_materials(array('category_id'=>$tmp_cat->children)));
            }
            header("Content-type: application/json; charset=UTF-8");
            header("Cache-Control: must-revalidate");
            header("Pragma: no-cache");
            header("Expires: -1");
            print json_encode($result);
            die();
        }

        $this->design->assign('current_params', $this->params_arr);

        // Если страница не задана, то равна 1
        $current_page = max(1, $current_page);
        $this->design->assign('current_page_num', $current_page);

        $categories_filtered = $this->materials->get_categories_filter(array('limit'=>10000));
        $collapsed = array();
        foreach($categories_filtered as $c)
            $collapsed[$c->id] = $c->collapsed;
        $this->design->assign('collapsed', $collapsed);

        $categories_filtered = $this->materials->get_categories_filter($filter);
        $cats_ids = array();
        foreach($categories_filtered as $cf)
            $cats_ids[] = $cf->id;
        $this->design->assign('cats_filtered_ids', $cats_ids);

        $categories = $this->materials->get_categories_tree();

        if (!empty($filter['keyword']))
        {
            $categories_count = 0;
            foreach($categories as $category)
                if (count(array_intersect($category->children, $cats_ids))>0)
                    $categories_count++;
        }
        else
            $categories_count = count($categories);

        // Постраничная навигация
        if (array_key_exists('page', $this->params_arr) && $this->params_arr['page'] == 'all')
            $items_per_page = $categories_count;
        else
            $items_per_page = $this->settings->categories_num_admin;

        $new_tree = array();
        $skipped_count = 0;
        $added_count = 0;
        foreach($categories as $category)
        {
            if ($skipped_count < ($current_page-1)*$items_per_page)
            {
                if (count(array_intersect($category->children, $cats_ids))>0)
                    $skipped_count++;
                continue;
            }
            if (count(array_intersect($category->children, $cats_ids))>0)
            {
                $this->calc_materials_count($category);
                $new_tree[$category->id] = $category;
                $added_count++;
            }
            if ($added_count == $items_per_page)
                break;
        }

        if ($ajax)
        {
            header("Content-type: application/json; charset=UTF-8");
            header("Cache-Control: must-revalidate");
            header("Pragma: no-cache");
            header("Expires: -1");

            switch($output_format){
                case "select2":
                    $data = array('success'=>true, 'data'=>array());
                    foreach($categories as $c)
                        $this->process_category($c, 0, $data['data']);
                    break;
            }

            print json_encode($data);
            die();
        }

        $this->design->assign('categories_count', $categories_count);
        $pages_num = ceil($categories_count/$items_per_page);
        $this->design->assign('total_pages_num', $pages_num);

        $this->design->assign('categories', $new_tree);
        $this->design->assign('params_arr', $this->params_arr);
        $this->design->assign('edit_module', $this->furl->get_module_by_name('MaterialsCategoryControllerAdmin'));
        return $this->design->fetch($this->design->getTemplateDir('admin').'materials-categories.tpl');
    }
}