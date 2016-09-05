<?php
namespace app\controllers;

use core\Controller;

class CategoryControllerAdmin extends Controller
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

        $edit_module = $this->furl->get_module_by_name('CategoryControllerAdmin');
        $main_module =  $this->furl->get_module_by_name('CategoriesControllerAdmin');
        $category_frontend_module = $this->furl->get_module_by_name('ProductsController');
        $this->design->assign('main_module', $main_module);
        $this->design->assign('category_frontend_module', $category_frontend_module);
        $this->design->assign('current_params', $this->params_arr);

        $this->db->query("SELECT id FROM __tags_groups WHERE name=? AND is_auto=?", "Теги категорий", 1);
        $category_tags_group = $this->db->result('id');
        $this->design->assign('category_tags_group', $category_tags_group);

        if ($this->request->method('post') && !isset($_FILES['uploaded-images']) && !isset($_FILES['gallery-uploaded-images']))
        {
            $category = new stdClass();
            $category->id = $this->request->post('id', 'integer');
            $category->parent_id = $this->request->post('parent_id', 'integer');
            $category->url = $this->request->post('url');
            $category->name = $this->request->post('name');
            $category->frontend_name = $this->request->post('frontend_name');

            $category->meta_title = $this->request->post('meta_title');
            $category->meta_keywords = $this->request->post('meta_keywords');
            $category->meta_description = $this->request->post('meta_description');

            $category->description = $this->request->post('description');
            $category->description2 = $this->request->post('description2');

            $category->is_visible = $this->request->post('is_visible', 'boolean');
            $category->set_id = $this->request->post('set_id', 'integer');

            $category->css_class = $this->request->post('css_class');
            $category->show_mode = $this->request->post('show_mode');
            $category->sort_type = $this->request->post('sort_type');

            if ($this->request->post('menu_level1_type'))
            {
                $category->menu_level1_type = $this->request->post('menu_level1_type');
                if ($category->menu_level1_type == 2)
                {
                    $category->menu_level1_columns =  $this->request->post('menu_level1_columns');
                    $category->menu_level1_width = $this->request->post('menu_level1_width');
                    $category->menu_level1_align = $this->request->post('menu_level1_align');
                    $category->menu_level1_use_banner = $this->request->post('menu_level1_use_banner', 'boolean');
                    $category->menu_level1_banner_id = $this->request->post('menu_level1_banner_id');
                    $category->menu_level1_column1_width = $this->request->post('menu_level1_column1_width');
                    $category->menu_level1_column2_width = $this->request->post('menu_level1_column2_width');
                    $category->menu_level1_column3_width = $this->request->post('menu_level1_column3_width');
                    $category->menu_level1_column4_width = $this->request->post('menu_level1_column4_width');
                    $category->menu_level1_column5_width = $this->request->post('menu_level1_column5_width');
                    $category->menu_level1_column6_width = $this->request->post('menu_level1_column6_width');
                }
            }
            if ($this->request->post('menu_level2_columns'))
                $category->menu_level2_columns = $this->request->post('menu_level2_columns');
            if ($this->request->post('menu_level2_column'))
                $category->menu_level2_column = $this->request->post('menu_level2_column');

            $category->collapsed = $this->request->post('collapsed', 'boolean');
            $images_position = $this->request->post('images_position');

            $modificators = $this->request->post('modificators');
            $modificators_groups = $this->request->post('modificators_groups');

            $category->modificators = !empty($modificators) ? join(',', $modificators) : '';
            $category->modificators_groups = !empty($modificators_groups) ? join(',', $modificators_groups) : '';

            $close_after_save = $this->request->post('close_after_save', 'integer');
            $add_after_save = $this->request->post('add_after_save', 'integer');
            $recreate_seo = $this->request->post('recreate_seo', 'integer');

            if ((empty($category->meta_keywords) && (!empty($category->description) || !empty($category->description2))) || $recreate_seo)
            {
                if (!empty($category->description))
                    $str = mb_strtolower(html_entity_decode($category->description, ENT_COMPAT, 'utf-8'), 'utf-8');
                else
                    $str = mb_strtolower(html_entity_decode($category->description2, ENT_COMPAT, 'utf-8'), 'utf-8');
                $str = preg_replace("/[^a-zа-я0-9\s]/u", " ", $str);
                $str = preg_replace("/\s+/u", "  ", $str);                //заменим пробелы на двойные пробелы, чтоб следующая регулярка работала, иначе между словами будет общий пробел и условие не пройдет
                $str = preg_replace("/(\s[^\s]{1,3}\s)+/u", " ", $str);    //remove words with length<=3
                $str = preg_replace("/(\s\s)+/u", " ", $str);            //remove double spaces
                $str = trim($str, 'p ');
                $str = preg_replace("/\s+/u", ", ", $str);
                if (mb_strlen($str, 'utf-8') <= 200)
                    $str = mb_substr($str, 0, 200, 'utf-8');
                else
                {
                    $space_pos = mb_strpos($str, ' ', 200, 'utf-8');
                    if ($space_pos !== false)
                        $str = mb_substr($str, 0, $space_pos, 'utf-8');
                }
                $category->meta_keywords = $str;
            }

            if ((empty($category->meta_description) && (!empty($category->description) || !empty($category->description2))) || $recreate_seo)
            {
                if (!empty($category->description))
                    $str = preg_replace("/[^a-zA-Zа-яА-Я0-9\s]/u", " ", html_entity_decode($category->description, ENT_COMPAT, 'utf-8'));
                else
                    $str = preg_replace("/[^a-zA-Zа-яА-Я0-9\s]/u", " ", html_entity_decode($category->description2, ENT_COMPAT, 'utf-8'));
                $str = preg_replace("/\s\s+/u", " ", $str);
                $str = trim($str, 'p ');
                if (mb_strlen($str, 'utf-8') <= 200)
                    $str = mb_substr($str, 0, 200, 'utf-8');
                else
                {
                    $space_pos = mb_strpos($str, ' ', 200, 'utf-8');
                    if ($space_pos !== false)
                        $str = mb_substr($str, 0, $space_pos, 'utf-8');
                }
                $category->meta_description = $str;
            }

            if ((empty($category->meta_title) && (!empty($category->name) || !empty($category->frontend_name))) || $recreate_seo)
                if (!empty($category->frontend_name))
                    $category->meta_title = $category->frontend_name;
                else
                    $category->meta_title = $category->name;

            if ($recreate_seo)
                $category->url = '';

            if (empty($category->name))
            {
                $this->design->assign('message_error', 'empty_name');

                if (empty($product->id))
                {
                    $temp_id = uniqid();
                    $temp_id_gallery = uniqid();
                    $this->design->assign('temp_id', $temp_id);
                    $this->design->assign('temp_id_gallery', $temp_id_gallery);

                    $images = $this->image_temp->get_images($temp_id);
                    if (!empty($images)){
                        foreach($images as $i){
                            $fname = $this->config->root_dir . '/' . $this->config->original_tempimages_dir . $i->filename;
                            $this->image_temp->delete_image($i->temp_id, $i->id);
                        }
                    }

                    $images_gallery = $this->image_temp->get_images($temp_id_gallery);
                    if (!empty($images_gallery)){
                        foreach($images_gallery as $i){
                            $fname = $this->config->root_dir . '/' . $this->config->original_tempimages_dir . $i->filename;
                            $this->image_temp->delete_image($i->temp_id, $i->id);
                        }
                    }
                }
            }
            else
            {
                if(empty($category->id))
                {
                    $category->id = $this->categories->add_category($category);
                    $this->design->assign('message_success', 'added');

                    $temp_id = $this->request->post('temp_id');
                    if ($temp_id)
                    {
                        $images = $this->image_temp->get_images($temp_id);
                        if (!empty($images)){
                            foreach($images as $i){
                                $fname = $this->config->root_dir . '/' . $this->config->original_tempimages_dir . $i->filename;
                                $this->image->add_internet_image('categories', $category->id, $this->furl->generate_url($category->name), $fname);
                                $this->image_temp->delete_image($i->temp_id, $i->id);
                            }
                        }
                    }

                    $temp_id_gallery = $this->request->post('temp_id_gallery');
                    if ($temp_id_gallery)
                    {
                        $images_gallery = $this->image_temp->get_images($temp_id_gallery);
                        if (!empty($images_gallery)){
                            foreach($images_gallery as $i){
                                $fname = $this->config->root_dir . '/' . $this->config->original_tempimages_dir . $i->filename;
                                $this->image->add_internet_image('categories-gallery', $category->id, $this->furl->generate_url($category->name), $fname);
                                $this->image_temp->delete_image($i->temp_id, $i->id);
                            }
                        }
                    }
                }
                else
                {
                    $this->categories->update_category($category->id, $category);
                    $this->design->assign('message_success', 'updated');
                }

                if (!empty($images_position))
                {
                    $ip_arr = explode(',', $images_position);
                    foreach($ip_arr as $pos=>$id)
                        $this->image->update_image($id, array('position'=>$pos));
                }

                //Получим существующие теги категории
                $exists_category_tags = $this->tags->get_category_tags($category->id);
                $exists_category_tags_ids = array();
                foreach($exists_category_tags as $ct)
                    $exists_category_tags_ids[] = intval($ct->id);

                //Получим переданные теги товара
                $posted_tags = $this->request->post('category_tags');
                $posted_tags_ids = array();
                if ($posted_tags)
                    foreach($posted_tags as $group_id=>$tags_list){
                        $tags_array = explode('#%#', $tags_list);
                        foreach($tags_array as $t)
                        {
                            if (empty($t))
                                continue;
                            if ($tag = $this->tags->get_tags(array('group_id'=>$group_id,'name'=>$t)))
                                $posted_tags_ids[] = intval(reset($tag)->id);
                            else
                            {
                                $tag = new StdClass;
                                $tag->group_id = $group_id;
                                $tag->name = $t;
                                $tag->is_enabled = 1;
                                if ($group_id == $category_tags_group)
                                    $tag->is_auto = 1;
                                $tag->id = $this->tags->add_tag($tag);

                                $posted_tags_ids[] = $tag->id;
                            }
                        }
                    }

                //id товаров которые входят в категорию
                $products_ids = array();
                $tmp_category = $this->categories->get_category($category->id);
                $this->db->query("SELECT distinct product_id FROM __products_categories WHERE category_id = ?", $tmp_category->id/*children*/);
                foreach($this->db->results('product_id') as $p_id)
                    $products_ids[] = $p_id;

                //Найдем пересечение массивов, удалим теги которые отсутствуют в пересечении
                $tags_to_check_empty = array();
                $intersect_tags = array_intersect($exists_category_tags_ids, $posted_tags_ids);
                foreach($exists_category_tags_ids as $et)
                    if (!in_array($et, $intersect_tags))
                    {
                        $this->db->query("DELETE FROM __tags_categories WHERE category_id=? AND tag_id=?", $category->id, $et);
                        $this->db->query("DELETE FROM __tags_products WHERE product_id in(?@) and tag_id=?", $products_ids, $et);
                        $tags_to_check_empty[] = $et;
                    }

                if (!empty($tags_to_check_empty))
                    $this->tags->delete_empty_tags($tags_to_check_empty);

                //Добавим теги, которые отсутствуют в пересечении
                foreach($posted_tags_ids as $pt)
                    if (!in_array($pt, $intersect_tags))
                    {
                        $this->tags->add_category_tag($category->id, $pt);

                        foreach($products_ids as $p_id)
                            $this->tags->add_product_tag($p_id, $pt);
                    }

                $category = $this->categories->get_category(intval($category->id));

                if ($close_after_save && $main_module)
                    header("Location: ".$this->config->root_url.$main_module->url);

                if ($add_after_save)
                    header("Location: ".$this->config->root_url.$edit_module->url);
            }
        }
        else
            if ($this->request->method('post') && isset($_FILES['uploaded-images']))
            {
                $uploaded = $this->request->files('uploaded-images');
                $object_id = $this->request->post('object_id');

                if (is_numeric($object_id))
                {
                    $tmp_object = $this->categories->get_category($object_id);
                    foreach($uploaded as $index=>$ufile)
                        $img = $this->image->add_image('categories', $object_id, $this->furl->generate_url($tmp_object->name), $ufile['name'], $ufile['tmp_name']);
                }
                else
                    foreach($uploaded as $index=>$ufile)
                        $img = $this->image_temp->add_image($object_id, $ufile['name'], $ufile['tmp_name']);

                header("Content-type: application/json; charset=UTF-8");
                header("Cache-Control: must-revalidate");
                header("Pragma: no-cache");
                header("Expires: -1");
                print json_encode(1);
                die();
            }
            elseif ($this->request->method('post') && isset($_FILES['gallery-uploaded-images']))
            {
                $uploaded = $this->request->files('gallery-uploaded-images');
                $object_id = $this->request->post('object_id');

                if (is_numeric($object_id))
                {
                    $tmp_object = $this->categories->get_category($object_id);
                    foreach($uploaded as $index=>$ufile)
                        $img = $this->image->add_image('categories-gallery', $object_id, $this->furl->generate_url($tmp_object->name), $ufile['name'], $ufile['tmp_name']);
                }
                else
                    foreach($uploaded as $index=>$ufile)
                        $img = $this->image_temp->add_image($object_id, $ufile['name'], $ufile['tmp_name']);

                header("Content-type: application/json; charset=UTF-8");
                header("Cache-Control: must-revalidate");
                header("Pragma: no-cache");
                header("Expires: -1");
                print json_encode(1);
                die();
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
                            else
                                $id = strval($v);
                            break;
                        case "mode":
                            $mode = strval($v);
                            break;
                        case "ajax":
                            $json_answer = true;
                            unset($this->params_arr[$p]);
                            break;
                        case "page":
                            $this->design->assign('page', intval($v));
                            unset($this->params_arr[$p]);
                            break;
                    }
                }

                if (!empty($id))
                    $category = $this->categories->get_category($id);
                else
                {
                    $temp_id = uniqid();
                    $temp_id_gallery = uniqid();
                    $this->design->assign('temp_id', $temp_id);
                    $this->design->assign('temp_id_gallery', $temp_id_gallery);

                    $images = $this->image_temp->get_images($temp_id);
                    if (!empty($images)){
                        foreach($images as $i){
                            $fname = $this->config->root_dir . '/' . $this->config->original_tempimages_dir . $i->filename;
                            $this->image_temp->delete_image($i->temp_id, $i->id);
                        }
                    }

                    $images_gallery = $this->image_temp->get_images($temp_id_gallery);
                    if (!empty($images_gallery)){
                        foreach($images_gallery as $i){
                            $fname = $this->config->root_dir . '/' . $this->config->original_tempimages_dir . $i->filename;
                            $this->image_temp->delete_image($i->temp_id, $i->id);
                        }
                    }
                }

                if (!empty($mode) && ((isset($category) && !empty($category)) || !is_numeric($id)))
                    switch($mode){
                        case "delete":
                            //Получим существующие теги категории
                            $exists_category_tags = $this->tags->get_category_tags($category->id);
                            $exists_category_tags_ids = array();
                            foreach($exists_category_tags as $ct)
                                $exists_category_tags_ids[] = intval($ct->id);

                            //id товаров которые входят в категорию
                            $products_ids = array();
                            $this->db->query("SELECT distinct product_id FROM __products_categories WHERE category_id = ?", $category->id/*children*/);
                            foreach($this->db->results('product_id') as $p_id)
                                $products_ids[] = $p_id;

                            foreach($exists_category_tags_ids as $et)
                            {
                                $this->db->query("DELETE FROM __tags_categories WHERE category_id=? AND tag_id=?", $category->id, $et);

                                $this->db->query("DELETE FROM __tags_products WHERE product_id in(?@) and tag_id=?", $products_ids, $et);

                                $this->db->query("SELECT COUNT(category_id) as kol_cats FROM __tags_categories WHERE tag_id=?", $et);
                                $kol_cats = $this->db->result('kol_cats');
                                $this->db->query("SELECT COUNT(product_id) as kol_prods FROM __tags_products WHERE tag_id=?", $et);
                                $kol_prods = $this->db->result('kol_prods');
                                if (($kol_cats + $kol_prods) == 0)
                                    $this->db->query("DELETE FROM __tags WHERE id=?", $et);
                            }

                            $this->categories->delete_category($id);
                            $response['success'] = true;
                            break;
                        case "toggle":
                            $this->categories->update_category($id, array('is_visible'=>1-$category->is_visible));
                            $response['success'] = true;
                            break;
                        case "get_images":
                            $this->design->assign('object', $category);
                            $images_object_name = $this->params_arr['object'];

                            if (is_numeric($id))
                                $images = $this->image->get_images(/*'categories'*/$images_object_name, $id);
                            else
                            {
                                $images = $this->image_temp->get_images($id);
                                $this->design->assign('temp_id', $id);
                            }

                            if ($images_object_name == 'categories')
                                $this->design->assign('images', $images);
                            if ($images_object_name == 'categories-gallery')
                                $this->design->assign('images_gallery', $images);
                            $this->design->assign('images_object_name', /*'categories'*/$images_object_name);
                            $response['success'] = true;
                            if ($images_object_name == 'categories')
                                $response['data'] = $this->design->fetch($this->design->getTemplateDir('admin').'object-images.tpl');
                            if ($images_object_name == 'categories-gallery')
                                $response['data'] = $this->design->fetch($this->design->getTemplateDir('admin').'object-images-gallery.tpl');
                            break;
                        case "delete_image":
                            $image_id = intval($this->params_arr['image_id']);

                            if (is_numeric($id)){
                                $images_object_name = $this->params_arr['object'];
                                $this->image->delete_image(/*'categories'*/$images_object_name, $id, $image_id);
                            }
                            else
                                $this->image_temp->delete_image($id, $image_id);

                            $response['success'] = true;
                            break;
                        case "upload_internet_image":
                            $image_url = base64_decode($this->params_arr['image_url']);

                            if (is_numeric($id)){
                                $images_object_name = $this->params_arr['object'];
                                $this->image->add_internet_image(/*'categories'*/$images_object_name, $id, $category->name, $image_url);
                            }
                            else
                                $this->image_temp->add_internet_image($id, $image_url);

                            $response['success'] = true;
                            break;
                        case "get_tags":
                            $tags = $this->tags->get_tags(array('is_enabled'=>1));
                            $response['data'] = array();
                            foreach($tags as $t)
                                $response['data'][] = array('id'=>$t->name, 'text'=>$t->name);
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
        if (isset($category))
        {
            $this->design->assign('category', $category);
            $images = $this->image->get_images('categories', $category->id);
            $this->design->assign('images', $images);
            $images_gallery = $this->image->get_images('categories-gallery', $category->id);
            $this->design->assign('images_gallery', $images_gallery);

            // Теги категории
            $category_tags = $this->tags->get_category_tags($category->id);
            $category_tags_groups = array();
            foreach($category_tags as $tag)
            {
                if (!array_key_exists($tag->group_id, $category_tags_groups))
                    $category_tags_groups[$tag->group_id] = array();
                $category_tags_groups[$tag->group_id][] = $tag;
            }

            $this->design->assign('category_tags', $category_tags_groups);

            if ($category->parent_id == 0)
                $this->design->assign('show_menu_config_level1', true);

            $show_menu_config_level2 = false;
            if ($category->parent_id > 0)
            {
                $parent_category = $this->categories->get_category($category->parent_id);
                if (empty($parent_category->parent_id))
                {
                    $this->design->assign('parent_category', $parent_category);
                    $show_menu_config_level2 = true;
                }
            }
            $this->design->assign('show_menu_config_level2', $show_menu_config_level2);
        }

        $tab = "main";
        if (array_key_exists("tab", $this->params_arr))
            $tab = $this->params_arr["tab"];
        $this->design->assign('tab', $tab);

        $tags_groups = $this->tags->get_taggroups();
        foreach($tags_groups as &$group)
            $group->tags = $this->tags->get_tags(array('is_enabled'=>1,'group_id'=>$group->id));
        $this->design->assign('tags_groups', $tags_groups);

        $this->design->assign('categories', $this->categories->get_categories_tree());
        $this->design->assign('tags_sets', $this->tags->get_tags_sets(array('is_visible'=>1)));

        $this->design->assign('modificators', $this->modificators->get_modificators(array('parent_id'=>null, 'is_visible'=>1)));
        $this->design->assign('modificators_groups', $this->modificators->get_modificators_groups(array('is_visible' => 1)));

        return $this->design->fetch($this->design->getTemplateDir('admin').'category.tpl');
    }
}