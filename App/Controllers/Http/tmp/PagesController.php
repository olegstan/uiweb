<?php
namespace app\controllers;

use app\layer\LayerController;

class PagesController extends LayerController
{
    private $param_url, $options;

    /*public function set_params($url = null, $options = null)
    {
        $this->param_url = urldecode(trim($url, '/'));
        $this->options = $options;
    }*/
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
        $ajax = false;
        if (!empty($this->param_url))
        {
            $str = trim($this->param_url, '/');
            $params = explode('/', $str);
            $current_page = 1;

            $element = end($params);
            //if ($element == "ajax")
            if (array_key_exists("ajax", $this->params_arr))
            {
                $ajax = true;
                //unset($params[count($params)-1]);
                //$element = end($params);
            }

            //if (mb_strpos($element, 'page=', 0, 'utf-8') !== false)
            if (array_key_exists("page", $this->params_arr))
            {
                $current_page = intval($this->params_arr["page"]);//mb_substr($element, 5, mb_strlen($element, 'utf-8')-5, 'utf-8');
                //unset($params[count($params)-1]);
                //$element = end($params);
            }

            $this->design->assign('current_params', array());

            $category_css_class = "";
            $material_css_class = "";

            if (strpos($element, '.html') !== false)
            {
                $main_menu_item = null;
                $menu_items = $this->materials->get_menu_items_filter(array('is_main'=>1));
                if ($menu_items)
                    $main_menu_item = reset($menu_items);

                $element = str_replace('.html', '', $element);
                $material = $this->materials->get_material($element);
                if (!$material || !$material->is_visible)
                    return false;

                if (!empty($main_menu_item))
                    if ($main_menu_item->object_type == "material" && $main_menu_item->object_id == $material->id)
                    {
                        header("Location: " . $this->config->root_url);
                        exit;
                    }

                $images_gallery = $this->image->get_images('materials-gallery', $material->id);
                $this->design->assign('images_gallery', $images_gallery);

                $attachments = $this->attachments->get_attachments('materials', $material->id);
                $this->design->assign('attachments', $attachments);

                $category = $this->materials->get_category($material->parent_id);
                $this->design->assign('material', $material);
                $this->design->assign('category', $category);
                $template = $this->design->fetch($this->design->getTemplateDir('frontend').'material.tpl');
                $category_css_class = $category ? $category->css_class : '';
                $material_css_class = $material->css_class;

                $meta_title = $material->meta_title;
                $meta_description = $material->meta_description;
                $meta_keywords = $material->meta_keywords;
            }
            else
            {
                $category = $this->materials->get_category($element);
                if (!$category)
                    return false;

                $filter = array();
                $filter['is_visible'] = 1;
                $filter['limit'] = 10000;
                $filter['category_id'] = $category->children;

                switch($category->sort_type){
                    case 'position':
                        $filter['sort'] = 'position';
                        break;
                    case 'newest_desc':
                        $filter['sort'] = 'newest';
                        $filter['sort_type'] = 'desc';
                        break;
                }

                $materials_count = $this->materials->count_materials($filter);

                // Постраничная навигация
                if ($current_page == 'all')
                {
                    $items_per_page = $materials_count;
                    $this->design->assign('current_page_all', 1);
                }
                else
                    $items_per_page = $this->settings->materials_num;

                $current_page = max(1, $current_page);
                $this->design->assign('current_page_num', $current_page);

                $filter['page'] = $current_page;
                $filter['limit'] = $items_per_page;

                $materials = $this->materials->get_materials($filter);
                foreach($materials as $index=>$material)
                {
                    $materials[$index]->images = $this->image->get_images('materials', $material->id);
                    $materials[$index]->image = reset($materials[$index]->images);
                }

                $this->design->assign('materials_count', $materials_count);
                $pages_num = ceil($materials_count/$items_per_page);
                $this->design->assign('total_pages_num', $pages_num);

                $module_url = $this->materials->makeurl_short((object)array('object_id'=>$category->id, 'object_type'=>'material-category'));
                $this->design->assign('module_url', $module_url);

                $this->design->assign('params_arr', $this->params_arr);
                $this->design->assign('data_type', 'material-category');

                $this->design->assign('category', $category);
                $this->design->assign('materials', $materials);
                $template = $this->design->fetch($this->design->getTemplateDir('frontend').'material-category.tpl');
                $category_css_class = $category ? $category->css_class : '';

                $meta_title = $category->meta_title;
                $meta_description = $category->meta_description;
                $meta_keywords = $category->meta_keywords;
            }

            if ($ajax)
            {
                $data = array('success'=>true, 'body_category_css'=>$category_css_class, 'body_material_css'=>$material_css_class, 'data'=>$template, 'meta_title'=>$meta_title, 'meta_keywords'=>$meta_keywords, 'meta_description'=>$meta_description);
                header("Content-type: application/json; charset=UTF-8");
                header("Cache-Control: must-revalidate");
                header("Pragma: no-cache");
                header("Expires: -1");
                print json_encode($data);
                die();
            }
            else
            {
                $this->design->assign('meta_title', $meta_title);
                $this->design->assign('meta_keywords', $meta_keywords);
                $this->design->assign('meta_description', $meta_description);
                return $template;
            }
        }
        else
            if (array_key_exists("material_id", $this->params_arr)){
                $result = array('success' => false, 'description' => '');

                $material = $this->materials->get_material($this->params_arr['material_id']);
                if ($material){
                    $result['success'] = true;
                    $result['description'] = $material->description;
                }
                header("Content-type: application/json; charset=UTF-8");
                header("Cache-Control: must-revalidate");
                header("Pragma: no-cache");
                header("Expires: -1");
                print json_encode($result);
                die();
            }
            else
                return false;
    }
}