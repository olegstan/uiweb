<?php
namespace app\controllers;

use core\Controller;

class MaterialsControllerAdmin extends Controller
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

    private function process_menu($item, $parent_id, &$menu){
        if (!array_key_exists($parent_id, $menu))
            $menu[$parent_id] = array();
        $menu[$parent_id][] = intval($item['id']);
        if (isset($item['children']))
            foreach($item['children'] as $i)
                $this->process_menu($i, intval($item['id']), $menu);
    }

    private function process_category($item, $level, &$categories_list){
        $categories_list[] = array('id'=>$item->id, 'level'=>str_repeat("&nbsp;&nbsp;", $level), 'name'=>$item->name);
        if (isset($item->subcategories))
            foreach($item->subcategories as $subitem)
                $this->process_category($subitem, $level+1, $categories_list);
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

        foreach($this->params_arr as $p=>$v)
        {
            switch ($p)
            {
                case "categories_list":
                    $tree = $this->materials->get_categories_tree();
                    $categories_list = array();
                    foreach($tree as $titem)
                        $this->process_category($titem, 0, $categories_list);
                    header("Content-type: application/json; charset=UTF-8");
                    header("Cache-Control: must-revalidate");
                    header("Pragma: no-cache");
                    header("Expires: -1");
                    print json_encode($categories_list);
                    die();
                    break;
                case "save_positions":
                    $menu_items = $this->request->post('menu');
                    $menu = array();
                    foreach($menu_items as $item)
                        $this->process_menu($item, 0, $menu);
                    foreach($menu as $parent_id=>$items)
                        foreach($items as $position=>$id)
                            $this->materials->update_material($id, array('position'=>$position));

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
                case "category_id":
                    if (!empty($this->params_arr[$p]))
                    {
                        $category = $this->materials->get_category(intval($this->params_arr[$p]));
                        $filter[$p] = $category->children;
                        $this->design->assign('category_id', $this->params_arr[$p]);
                        //Составим путь выборки категории, чтоб подгрузить ее во фронтенде
                        $category_path = array();
                        while($category->parent_id != 0)
                        {
                            $category = $this->materials->get_category(intval($category->parent_id));
                            $category_path[] = $category->id;
                        }
                        $category_path = join(",",array_reverse($category_path));
                        $this->design->assign('category_path', $category_path);
                    }
                    else
                        unset($this->params_arr[$p]);
                    break;
                case "ajax":
                    $ajax = intval($this->params_arr[$p]);
                    unset($this->params_arr[$p]);
                    break;
                case "format":
                    $output_format = $this->params_arr[$p];
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
                case "exception":
                    $filter[$p] = explode(",", $this->params_arr[$p]);
                    break;
            }
        }

        /*Зададим сортировку по умолчанию*/
        if (!array_key_exists('sort', $this->params_arr))
        {
            $filter['sort'] = 'position';
            $filter['sort_type'] = 'asc';
            $sort_type = isset($category)?$category->sort_type:'';
            switch($sort_type){
                case 'position':
                    $filter['sort'] = 'position';
                    $filter['sort_type'] = 'asc';
                    break;
                case 'newest_desc':
                    $filter['sort'] = $this->params_arr['sort'] = 'newest';
                    $filter['sort_type'] = $this->params_arr['sort_type'] = 'desc';
                    break;
            }
        }
        else
            if (!array_key_exists('sort_type', $this->params_arr))
            {
                /*$this->params_arr['sort_type'] = 'asc';*/
                $filter['sort_type'] = 'asc';
            }

        $this->design->assign('current_params', $this->params_arr);

        // Если страница не задана, то равна 1
        $current_page = max(1, $current_page);
        $this->design->assign('current_page_num', $current_page);

        $all_materials_count = $this->materials->count_materials();
        $materials_count = $this->materials->count_materials($filter);

        // Постраничная навигация
        if (array_key_exists('page', $this->params_arr) && $this->params_arr['page'] == 'all')
            $items_per_page = $materials_count;
        else
            $items_per_page = $this->settings->products_num_admin;

        $filter['page'] = $current_page;
        $filter['limit'] = $items_per_page;

        $materials = $this->materials->get_materials($filter);

        $this->db->query("SELECT object_id FROM __materials_menu_items where object_type='material' and is_main=1");
        $material_on_main_id = $this->db->result('object_id');

        foreach($materials as $index=>$material)
        {
            $materials[$index]->images = $this->image->get_images('materials', $material->id);
            $materials[$index]->image = reset($materials[$index]->images);
            $materials[$index]->parent_category = $this->materials->get_category($material->parent_id);
            $materials[$index]->use_main = $material->id == $material_on_main_id;
        }

        $this->design->assign('params_arr', $this->params_arr);
        $this->design->assign('all_materials_count', $all_materials_count);
        $this->design->assign('materials_count', $materials_count);
        $pages_num = ceil($materials_count/$items_per_page);
        $this->design->assign('total_pages_num', $pages_num);

        $this->design->assign('materials', $materials);
        $this->design->assign('edit_module', $this->furl->get_module_by_name('MaterialControllerAdmin'));
        $this->design->assign('categories_module', $this->furl->get_module_by_name('MaterialsCategoriesControllerAdmin'));

        if ($ajax)
        {
            header("Content-type: application/json; charset=UTF-8");
            header("Cache-Control: must-revalidate");
            header("Pragma: no-cache");
            header("Expires: -1");

            switch($output_format){
                case "select2":
                    $data = array('success'=>true, 'data'=>array());
                    foreach($materials as $m)
                        $data['data'][] = array('id'=>$m->id, 'text'=>$m->name);
                    break;
                case "":
                    $data = $this->design->fetch($this->design->getTemplateDir('admin').'materials-refreshpart.tpl');
                    break;
            }

            print json_encode($data);
            die();
        }

        return $this->design->fetch($this->design->getTemplateDir('admin').'materials.tpl');
    }
}