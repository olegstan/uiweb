<?php
namespace app\controllers;

use core\Controller;

class ProductControllerAdmin extends Controller
{
    private $param_url, $params_arr, $options;
    private $all_categories;

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

    private function recreate_product_autotags($product, $variants){
        //Проставим автотеги цены и наличия
        $this->db->query("SELECT id FROM __tags_groups WHERE name=? AND is_auto=?", "Цена", 1);
        $price_group_id = $this->db->result('id');
        $this->db->query("SELECT id FROM __tags_groups WHERE name=? AND is_auto=?", "Есть в наличии", 1);
        $stock_group_id = $this->db->result('id');

        $this->db->query("SELECT tp.tag_id
                FROM __tags_products tp
                INNER JOIN __tags t ON tp.tag_id = t.id
            WHERE tp.product_id=? AND t.is_auto=1 AND t.group_id in (?,?)", $product->id, $price_group_id, $stock_group_id);
        $autotags_ids = $this->db->results('tag_id');
        if (!empty($autotags_ids))
            $this->db->query("DELETE FROM __tags_products WHERE product_id=? AND tag_id IN (?@)", $product->id, $autotags_ids);

        $in_stock = false;
        $in_order = false;
        if(is_array($variants))
            foreach($variants as $variant)
            {
                if (!$variant->is_visible)
                    continue;
                if ($variant->stock > 0)
                    $in_stock = true;
                if ($variant->stock < 0)
                    $in_order = true;
                $tag_id = 0;
                if ($tag = $this->tags->get_tags(array('group_id'=>$price_group_id,'name'=>$this->currencies->convert($variant->price, empty($product->currency_id) ? $this->admin_currency->id : $product->currency_id, false))))
                {
                    $tag_id = intval(reset($tag)->id);
                }
                else
                {
                    $tag = new StdClass;
                    $tag->group_id = $price_group_id;
                    $tag->name = $this->currencies->convert($variant->price, empty($product->currency_id) ? $this->admin_currency->id : $product->currency_id, false);
                    $tag->is_enabled = 1;
                    $tag->is_auto = 1;
                    $tag->id = $this->tags->add_tag($tag);

                    $tag_id = $tag->id;
                }
                $this->tags->add_product_tag($product->id, $tag_id);
            }
        if ($in_stock)
            $in_stock_text = "да";
        else
            if ($in_order)
                $in_stock_text = "под заказ";
            else
                $in_stock_text = "нет";
        $tag_id = 0;
        if ($tag = $this->tags->get_tags(array('group_id'=>$stock_group_id,'name'=>$in_stock_text)))
            $tag_id = intval(reset($tag)->id);
        else
        {
            $tag = new StdClass;
            $tag->group_id = $stock_group_id;
            $tag->name = $in_stock_text;
            $tag->is_enabled = 1;
            $tag->is_auto = 1;
            $tag->id = $this->tags->add_tag($tag);

            $tag_id = $tag->id;
        }
        $this->tags->add_product_tag($product->id, $tag_id);
    }

    private function process_category($category, $level = 0)
    {
        $category->name = $category->name;
        $category->level = $level;
        $this->all_categories[] = $category;

        if (isset($category->subcategories))
            foreach($category->subcategories as $subcategory)
                $this->process_category($subcategory, $level+1);
    }

    function fetch()
    {
        if (!(isset($_SESSION['admin']) && $_SESSION['admin']=='admin'))
            header("Location: http://".$_SERVER['SERVER_NAME']."/admin/login/");

        $edit_module = $this->furl->get_module_by_name('ProductControllerAdmin');
        $main_module =  $this->furl->get_module_by_name('ProductsControllerAdmin');
        $product_frontend_module = $this->furl->get_module_by_name('ProductController');
        $this->design->assign('main_module', $main_module);
        $this->design->assign('product_frontend_module', $product_frontend_module);
        $this->design->assign('current_params', $this->params_arr);

        if ($this->categories->count_categories_filter() == 0)
            header("Location: " . $this->config->root_url . $main_module->url);

        if ($this->request->method('post') && !isset($_FILES['uploaded-images']) && !isset($_FILES['uploaded-attachments']) &&
            (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest'))
        {
            $product = new stdClass();
            $product->id = $this->getCore()->request->post('id', 'integer');
            $product->brand_id = $this->request->post('brand_id', 'integer');
            $product->url = $this->request->post('url');
            $product->name = $this->request->post('name');

            $product_date = $this->request->post('created_date');
            $datetime = DateTime::createFromFormat('d.m.Y', $product_date);
            $product->created = $datetime->format('Y-m-d');

            $product->meta_title = $this->request->post('meta_title');
            $product->meta_keywords = $this->request->post('meta_keywords');
            $product->meta_description = $this->request->post('meta_description');

            $product->annotation = $this->request->post('annotation');
            $product->annotation2 = $this->request->post('annotation2');
            $product->body = $this->request->post('body');

            $product->css_class = $this->request->post('css_class');

            $product->is_visible = $this->request->post('is_visible', 'boolean');
            $product->currency_id = $this->request->post('currency_id', 'integer');

            if ($this->settings->catalog_use_variable_amount)
            {
                $product->use_variable_amount = $this->request->post('use_variable_amount', 'boolean');
                $product->min_amount = $this->request->post('min_amount');
                $product->max_amount = $this->request->post('max_amount');
                $product->step_amount = $this->request->post('step_amount');
            }

            if ($this->request->post('add_field1'))
                $product->add_field1 = $this->request->post('add_field1');
            if ($this->request->post('add_field2'))
                $product->add_field2 = $this->request->post('add_field2');
            if ($this->request->post('add_field3'))
                $product->add_field3 = $this->request->post('add_field3');

            if (array_key_exists('add_flag1', $_POST))
                $product->add_flag1 = $this->request->post('add_flag1', 'boolean');
            if (array_key_exists('add_flag2', $_POST))
                $product->add_flag2 = $this->request->post('add_flag2', 'boolean');
            if (array_key_exists('add_flag3', $_POST))
                $product->add_flag3 = $this->request->post('add_flag3', 'boolean');

            $images_position = $this->request->post('images_position');
            $attachments_position = $this->request->post('attachments_position');

            $categories_ids = $this->request->post('categories_ids');

            $related_ids = $this->request->post('related_ids');
            $analogs_ids = $this->request->post('analogs_ids');
            $analogs_group_id = $this->request->post('analogs_group_id');

            $page = $this->request->post('page');
            $preselect_category_id = $this->request->post('category_id');
            $sort = $this->request->post('sort');
            $sort_type = $this->request->post('sort_type');

            // Варианты товара
            $variants = array();
            if($this->request->post('variants'))
                foreach($this->request->post('variants') as $n=>$va)
                    foreach($va as $i=>$v)
                    {
                        if (empty($variants[$i]))
                            $variants[$i] = new stdClass;
                        $variants[$i]->$n = $v;
                    }
            if (count($variants) > 1)
                foreach($variants as $index=>$variant)
                    if (empty($variant->name) && empty($variant->sku) && empty($variant->price))
                        unset($variants[$index]);

            $product_categories = array();
            foreach($categories_ids as $c_id)
                if (!empty($c_id))
                    $product_categories[] = intval($c_id);

            $product->modificators_mode = $this->request->post('modificators_mode', 'boolean');

            $modificators = $this->request->post('modificators');
            $modificators_groups = $this->request->post('modificators_groups');

            $product->modificators = !empty($modificators) ? join(',', $modificators) : '';
            $product->modificators_groups = !empty($modificators_groups) ? join(',', $modificators_groups) : '';

            $close_after_save = $this->request->post('close_after_save', 'integer');
            $add_after_save = $this->request->post('add_after_save', 'integer');
            $recreate_seo = $this->request->post('recreate_seo', 'integer');
            $create_analogs_group = $this->request->post('create_analogs_group', 'integer');
            $change_analogs_group = $this->request->post('change_analogs_group', 'integer');

            if ((empty($product->meta_keywords) && !empty($product->annotation)) || $recreate_seo)
            {
                $str_name = "";
                if (!empty($product->name))
                    $str_name = mb_strtolower(strip_tags(html_entity_decode($product->name, ENT_COMPAT, 'utf-8')), 'utf-8');
                $str = mb_strtolower(strip_tags(html_entity_decode($product->annotation, ENT_COMPAT, 'utf-8')), 'utf-8');
                $str = preg_replace("/[^a-zа-я0-9\s]/u", " ", $str);
                $str = preg_replace("/\s+/u", "  ", $str);                //заменим пробелы на двойные пробелы, чтоб следующая регулярка работала, иначе между словами будет общий пробел и условие не пройдет
                $str = preg_replace("/(\s[^\s]{1,3}\s)+/u", " ", $str);    //remove words with length<=3
                $str = preg_replace("/(\s\s)+/u", " ", $str);            //remove double spaces
                $str = trim($str, 'p ');
                $str = preg_replace("/\s+/u", ", ", $str);
                $str = empty($str_name)?$str:(empty($str)?$str_name:$str_name.", ".$str);
                if (mb_strlen($str, 'utf-8') <= 200)
                    $str = mb_substr($str, 0, 200, 'utf-8');
                else
                {
                    $space_pos = mb_strpos($str, ' ', 200, 'utf-8');
                    if ($space_pos !== false)
                        $str = mb_substr($str, 0, $space_pos, 'utf-8');
                }
                $product->meta_keywords = $str;
            }

            if ((empty($product->meta_description) && !empty($product->annotation)) || $recreate_seo)
            {
                $str = preg_replace("/[^a-zA-Zа-яА-Я0-9\s]/u", " ", strip_tags(html_entity_decode($product->annotation, ENT_COMPAT, 'utf-8')));
                $str = preg_replace("/\s\s+/u", " ", $str);
                $str = trim($str, 'p ');
                $str_name = "";
                if (!empty($product->name))
                    $str_name = strip_tags(html_entity_decode($product->name, ENT_COMPAT, 'utf-8'));
                $str = empty($str_name)?$str:(empty($str)?$str_name:$str_name.", ".$str);
                if (mb_strlen($str, 'utf-8') <= 200)
                    $str = mb_substr($str, 0, 200, 'utf-8');
                else
                {
                    $space_pos = mb_strpos($str, ' ', 200, 'utf-8');
                    if ($space_pos !== false)
                        $str = mb_substr($str, 0, $space_pos, 'utf-8');
                }
                $product->meta_description = $str;
            }

            if ((empty($product->meta_title) && !empty($product->name)) || $recreate_seo)
                $product->meta_title = $product->name;

            if ($recreate_seo)
                $product->url = '';

            if (empty($product->name))
            {
                $this->design->assign('message_error', 'empty_name');

                if (empty($product->id))
                {
                    $temp_id = uniqid();
                    $this->design->assign('temp_id', $temp_id);

                    $images = $this->image_temp->get_images($temp_id);
                    if (!empty($images)){
                        foreach($images as $i){
                            $fname = $this->config->root_dir . '/' . $this->config->original_tempimages_dir . $i->filename;
                            $this->image_temp->delete_image($i->temp_id, $i->id);
                        }
                    }
                }
            }
            else
            {
                if(empty($product->id))
                {
                    $product->id = $this->products->add_product($product);
                    $this->design->assign('message_success', 'added');

                    $temp_id = $this->request->post('temp_id');
                    if ($temp_id)
                    {
                        $images = $this->image_temp->get_images($temp_id);
                        if (!empty($images)){
                            foreach($images as $i){
                                $fname = $this->config->root_dir . '/' . $this->config->original_tempimages_dir . $i->filename;
                                $this->image->add_internet_image('products', $product->id, $this->furl->generate_url($product->name), $fname);
                                $this->image_temp->delete_image($i->temp_id, $i->id);
                            }
                        }
                    }
                }
                else
                {
                    $old_product = $this->products->get_product($product->id);
                    $this->products->update_product($product->id, $product);
                    $this->design->assign('message_success', 'updated');
                }

                if (!empty($images_position))
                {
                    $ip_arr = explode(',', $images_position);
                    foreach($ip_arr as $pos=>$id)
                        $this->image->update_image($id, array('position'=>$pos));
                }

                if (!empty($attachments_position))
                {
                    $ip_arr = explode(',', $attachments_position);
                    foreach($ip_arr as $pos=>$id)
                        $this->attachments->update_attachment($id, array('position'=>$pos));
                }

                if(is_array($variants))
                {
                    $variants_ids = array();
                    foreach($variants as $index=>$variant)
                    {
                        /*if (empty($variant->name))
                            continue;*/

                        if(!isset($variant->stock) || $variant->stock == '∞' || $variant->stock == '')
                            $variants[$index]->stock = null;

                        if (!isset($variant->is_visible))
                            $variants[$index]->is_visible = 0;

                        if(!empty($variant->id))
                            $this->variants->update_variant($variant->id, $variant);
                        else
                        {
                            $variants[$index]->product_id = $product->id;
                            $variants[$index]->id = $this->variants->add_variant($variant);
                        }
                        $variants[$index] = $this->variants->get_variant($variants[$index]->id);

                        $variants_ids[] = $variants[$index]->id;
                    }

                    // Удалить непереданные варианты
                    $current_variants = $this->variants->get_variants(array('product_id'=>$product->id));
                    foreach($current_variants as $current_variant)
                        if(!in_array($current_variant->id, $variants_ids))
                            $this->variants->delete_variant($current_variant->id);

                    // Отсортировать  варианты
                    asort($variants_ids);
                    $i = 0;
                    foreach($variants_ids as $variant_id)
                    {
                        $this->variants->update_variant($variants_ids[$i], array('position'=>$variant_id));
                        $i++;
                    }
                }

                // Категории товара
                $query = $this->db->placehold('DELETE FROM __products_categories WHERE product_id=?', $product->id);
                $this->db->query($query);
                foreach($product_categories as $i=>$category_id){
                    $this->categories->add_product_category($product->id, $category_id, $i);
                }

                //Получим существующие бейджи товара
                $exists_product_badges = $this->badges->get_product_badges($product->id);
                $exists_product_badges_ids = array();
                foreach($exists_product_badges as $pb)
                    $exists_product_badges_ids[] = intval($pb->id);

                // Получим переданные бейджи товара
                $posted_badges = $this->request->post('product_badges');
                $posted_badges_ids = array();
                $badges_array = explode('#%#', $posted_badges);
                foreach($badges_array as $b)
                    if ($badge = $this->badges->get_badge($b))
                        $posted_badges_ids[] = intval($badge->id);

                // Найдем пересечение массивов, удалим бейджи которые отсутствуют в пересечении
                $intersect_badges = array_intersect($exists_product_badges_ids, $posted_badges_ids);
                foreach($exists_product_badges_ids as $eb)
                    if (!in_array($eb, $intersect_badges))
                        $this->db->query("DELETE FROM __badges_products WHERE product_id=? AND badge_id=?", $product->id, $eb);

                // Добавим бейджи, которые отсутствуют в пересечении
                foreach($posted_badges_ids as $pb)
                    if (!in_array($pb, $intersect_badges))
                        $this->badges->add_product_badge($product->id, $pb);

                // Получим существующие теги товара
                $autotags_ids = array();

                $exists_product_tags = $this->tags->get_product_tags($product->id);
                $exists_product_tags_ids = array();
                foreach($exists_product_tags as $pt)
                {
                    $exists_product_tags_ids[] = intval($pt->id);
                    if ($pt->is_auto)
                        $autotags_ids[] = intval($pt->id);
                }

                // Получим переданные теги товара
                $posted_tags = $this->request->post('product_tags');
                $posted_tags_ids = array();
                if ($posted_tags)
                    foreach($posted_tags as $group_id=>$tags_list){
                        if (empty($tags_list))
                            continue;
                        $tags_array = explode('#%#', $tags_list);
                        foreach($tags_array as $t)
                            if ($tag = $this->tags->get_tags(array('group_id'=>$group_id,'name'=>$t)))
                                $posted_tags_ids[] = intval(reset($tag)->id);
                            else
                            {
                                $tag = new StdClass;
                                $tag->group_id = $group_id;
                                $tag->name = $t;
                                $tag->is_enabled = 1;
                                $tag->id = $this->tags->add_tag($tag);

                                $posted_tags_ids[] = $tag->id;
                            }
                    }

                // Найдем пересечение массивов, удалим теги которые отсутствуют в пересечении
                $tags_to_check_empty = array();
                $intersect_tags = array_intersect($exists_product_tags_ids, $posted_tags_ids);
                foreach($exists_product_tags_ids as $et)
                    if (!in_array($et, $intersect_tags) && !in_array($et, $autotags_ids))
                    {
                        $this->db->query("DELETE FROM __tags_products WHERE product_id=? AND tag_id=?", $product->id, $et);
                        $tags_to_check_empty[] = $et;
                    }

                // Добавим теги, которые отсутствуют в пересечении
                foreach($posted_tags_ids as $pt)
                    if (!in_array($pt, $intersect_tags))
                        $this->tags->add_product_tag($product->id, $pt);

                if (!empty($tags_to_check_empty))
                    $this->tags->delete_empty_tags($tags_to_check_empty);

######################################
############ АВТОТЕГИ
######################################
                //УДАЛИМ ВСЕ АВТОТЕГИ ТОВАРА
                $this->db->query("SELECT tp.tag_id
                                    FROM __tags_products tp
                                    INNER JOIN __tags t ON tp.tag_id = t.id
                                WHERE tp.product_id=? AND t.is_auto=1", $product->id);
                $autotags_ids = $this->db->results('tag_id');

                if (!empty($autotags_ids))
                    $this->db->query("DELETE FROM __tags_products WHERE product_id=? AND tag_id IN (?@)", $product->id, $autotags_ids);

                //Проставим тег бренда если выбран Бренд и у него есть тег, предварительно проверим есть ли старый тег бренда, если что его удаляем
                if (isset($old_product) && $old_product->brand_id != $product->brand_id && $old_product->brand_id > 0)
                {
                    $old_product_brand = $this->brands->get_brand($old_product->brand_id);
                    if ($old_product_brand && $old_product_brand->tag_id)
                        $this->tags->delete_product_tag($product->id, $old_product_brand->tag_id);
                }

                if ($product->brand_id > 0)
                {
                    $product_brand = $this->brands->get_brand($product->brand_id);
                    if ($product_brand && $product_brand->tag_id > 0)
                        $this->tags->add_product_tag($product->id, $product_brand->tag_id);
                }

                //Проставим теги категорий в которых входит товар
                foreach($product_categories as $category_id)
                {
                    $this->db->query("SELECT tc.tag_id FROM __tags_categories tc
                                        INNER JOIN __tags t ON tc.tag_id = t.id
                                    WHERE tc.category_id=? AND t.is_auto=1", $category_id);
                    foreach($this->db->results('tag_id') as $t_id)
                        $this->tags->add_product_tag($product->id, $t_id);
                }

                //Проставим теги цены
                $this->db->query("SELECT id FROM __tags_groups WHERE name=? AND is_auto=?", "Цена", 1);
                $price_group_id = $this->db->result('id');

                $in_stock = false;
                $in_order = false;
                if(is_array($variants))
                    foreach($variants as $variant)
                    {
                        if (!$variant->is_visible)
                            continue;
                        if ($variant->stock > 0)
                            $in_stock = true;
                        if ($variant->stock < 0)
                            $in_order = true;
                        $tag_id = 0;
                        if ($tag = $this->tags->get_tags(array('group_id'=>$price_group_id,'name'=>$this->currencies->convert($variant->price, empty($product->currency_id) ? $this->admin_currency->id : $product->currency_id, false))))
                        {
                            $tag_id = intval(reset($tag)->id);
                        }
                        else
                        {
                            $tag = new StdClass;
                            $tag->group_id = $price_group_id;
                            $tag->name = $this->currencies->convert($variant->price, empty($product->currency_id) ? $this->admin_currency->id : $product->currency_id, false);
                            $tag->is_enabled = 1;
                            $tag->is_auto = 1;
                            $tag->id = $this->tags->add_tag($tag);

                            $tag_id = $tag->id;
                        }
                        $this->tags->add_product_tag($product->id, $tag_id);
                    }

                $this->db->query("SELECT id FROM __tags_groups WHERE name=? AND is_auto=?", "Есть в наличии", 1);
                $stock_group_id = $this->db->result('id');
                if ($in_stock)
                    $in_stock_text = "да";
                else
                    if ($in_order)
                        $in_stock_text = "под заказ";
                    else
                        $in_stock_text = "нет";
                $tag_id = 0;
                if ($tag = $this->tags->get_tags(array('group_id'=>$stock_group_id,'name'=>$in_stock_text)))
                    $tag_id = intval(reset($tag)->id);
                else
                {
                    $tag = new StdClass;
                    $tag->group_id = $stock_group_id;
                    $tag->name = $in_stock_text;
                    $tag->is_enabled = 1;
                    $tag->is_auto = 1;
                    $tag->id = $this->tags->add_tag($tag);

                    $tag_id = $tag->id;
                }
                $this->tags->add_product_tag($product->id, $tag_id);
######################################
############ АВТОТЕГИ (END)
######################################

                // Сопутствующие товары
                $query = $this->db->placehold('DELETE FROM __related_products WHERE product_id=? and product_type=?', $product->id, 0);
                $this->db->query($query);
                if(is_array($related_ids))
                {
                    $pos = 0;
                    foreach($related_ids  as $i=>$related_id)
                        $this->products->add_related_product($product->id, $related_id, $pos++, 0);
                }

                if ($analogs_group_id)
                {
                    $this->products->empty_group_analogs($analogs_group_id);
                    if(is_array($analogs_ids))
                        foreach($analogs_ids  as $position=>$analog_id)
                            $this->products->add_product_to_exist_group_analogs($analogs_group_id, $analog_id);
                }

                // Аналоги
                /*$query = $this->db->placehold("SELECT related_id FROM __related_products WHERE product_id=? AND product_type=?", $product->id, 3);
                $this->db->query($query);
                $old_analogs_ids = $this->db->results('related_id');
                $query = $this->db->placehold('DELETE FROM __related_products WHERE product_id in (?@) and related_id in (?@) and product_type=?', array_merge($old_analogs_ids, array($product->id)), array_merge($old_analogs_ids, array($product->id)), 3);
                $this->db->query($query);
                if(is_array($analogs_ids))
                {
                    $pos = 0;
                    foreach($analogs_ids  as $i=>$analog_id)
                    {
                        $this->products->add_related_product($product->id, $analog_id, $pos++, 3);

                        foreach($analogs_ids as $j=>$analog2_id)
                        {
                            if ($i == $j)
                                continue;
                            $this->db->query("SELECT max(position) as max_pos FROM __related_products WHERE product_id=? AND product_type=?", $analog2_id, 3);
                            $max_pos = $this->db->result('max_pos');
                            if (empty($max_pos))
                                $max_pos = 1;
                            else
                                $max_pos++;

                            $this->products->add_related_product($analog_id, $analog2_id, $max_pos, 3);
                        }

                        $this->db->query("SELECT max(position) as max_pos FROM __related_products WHERE product_id=? AND product_type=?", $analog_id, 3);
                        $max_pos = $this->db->result('max_pos');
                        if (empty($max_pos))
                            $max_pos = 1;
                        else
                            $max_pos++;

                        $this->products->add_related_product($analog_id, $product->id, $max_pos, 3);
                    }
                }*/

                $product = $this->products->get_product(intval($product->id));

                if ($close_after_save && $main_module)
                {
                    $href = $this->config->root_url.$main_module->url.$this->design->url_modifier(array('add'=>array('page'=>$page, 'category_id'=>$preselect_category_id, 'sort'=>$sort, 'sort_type'=>$sort_type)));
                    header("Location: ".$href);
                }

                if ($add_after_save)
                {
                    $href = $this->config->root_url.$edit_module->url.$this->design->url_modifier(array('add'=>array('page'=>$page, 'category_id'=>$preselect_category_id, 'sort'=>$sort, 'sort_type'=>$sort_type)));
                    header("Location: ".$href);
                }

                if ($create_analogs_group)
                {
                    $this->products->add_product_to_new_group_analogs($product->id);
                }

                if ($change_analogs_group)
                {
                    $analogs_group = $this->request->post('analogs_group');
                    $this->products->delete_product_from_group_analogs($product->id);
                    if (!empty($analogs_group))
                        $this->products->add_product_to_exist_group_analogs($analogs_group, $product->id);
                }

                $this->design->assign('category_id', $preselect_category_id);
            }
        }
        else
            if ($this->request->method('post') && isset($_FILES['uploaded-images']) &&
                (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest'))
            {
                $uploaded = $this->request->files('uploaded-images');
                $object_id = $this->request->post('object_id');

                if (is_numeric($object_id))
                {
                    $tmp_object = $this->products->get_product($object_id);
                    foreach($uploaded as $index=>$ufile)
                        $img = $this->image->add_image('products', $object_id, $this->furl->generate_url($tmp_object->name), $ufile['name'], $ufile['tmp_name']);
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
            elseif ($this->request->method('post') && isset($_FILES['uploaded-attachments']) &&
                (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest'))
            {
                $uploaded = $this->request->files('uploaded-attachments');
                $object_id = $this->request->post('object_id');

                if (is_numeric($object_id))
                {
                    $tmp_object = $this->products->get_product($object_id);
                    foreach($uploaded as $index=>$ufile){
                        $img = $this->attachments->add_attachment('products', $object_id, $this->furl->generate_url(pathinfo($ufile['name'], PATHINFO_FILENAME)), $ufile['name'], $ufile['tmp_name']);
                    }
                }
                /*else
                    foreach($uploaded as $index=>$ufile)
                        $img = $this->image_temp->add_image($object_id, $ufile['name'], $ufile['tmp_name']);*/

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
                $multiple = false;
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
                        case "category_id":
                            $category_id = intval($v);
                            $this->design->assign('category_id', $category_id);
                            break;
                        case "multiple":
                            $multiple = intval($v);
                            break;
                        case "page":
                            $this->design->assign('page', intval($v));
                            break;
                        case "sort":
                            $this->design->assign('sort', $v);
                            break;
                        case "sort_type":
                            $this->design->assign('sort_type', $v);
                            break;
                    }
                }

                if (!empty($id))
                    $product = $this->products->get_product($id);
                else
                {
                    $temp_id = uniqid();
                    $this->design->assign('temp_id', $temp_id);

                    $images = $this->image_temp->get_images($temp_id);
                    if (!empty($images)){
                        foreach($images as $i){
                            $fname = $this->config->root_dir . '/' . $this->config->original_tempimages_dir . $i->filename;
                            $this->image_temp->delete_image($i->temp_id, $i->id);
                        }
                    }
                }


                if (!empty($mode) && ((isset($product) && !empty($product)) || $multiple || !is_numeric($id)))
                    switch($mode){
                        case "delete":
                            if ($multiple)
                                foreach(explode(',', $this->params_arr['id']) as $mid)
                                    $this->products->delete_product($mid);
                            else
                                $this->products->delete_product($id);
                            $response['success'] = true;
                            break;
                        case "toggle":
                            $this->products->update_product($id, array('is_visible'=>1-$product->is_visible));
                            $response['success'] = true;
                            break;
                        case "toggle_flag1":
                            $this->products->update_product($id, array('add_flag1'=>1-$product->add_flag1));
                            $response['success'] = true;
                            break;
                        case "toggle_flag2":
                            $this->products->update_product($id, array('add_flag2'=>1-$product->add_flag2));
                            $response['success'] = true;
                            break;
                        case "toggle_flag3":
                            $this->products->update_product($id, array('add_flag3'=>1-$product->add_flag3));
                            $response['success'] = true;
                            break;
                        case "get_tag_groups":
                            $all_groups = $this->tags->get_taggroups(array('is_enabled'=>1, 'is_auto'=>0));
                            $ids = $this->request->post('ids');
                            if (!is_array($ids))
                                $ids = array();
                            $response['data'] = '';
                            foreach($all_groups as $gr)
                                if (!in_array($gr->id, $ids))
                                    $response['data'] .= "<option value='".$gr->id."' data-name='".$gr->name."'>".$gr->name." (".$gr->id.")</option>";
                            $response['success'] = true;
                            break;
                        case "get_images":
                            $this->design->assign('object', $product);

                            if (is_numeric($id))
                                $images = $this->image->get_images('products', $id);
                            else
                            {
                                $images = $this->image_temp->get_images($id);
                                $this->design->assign('temp_id', $id);
                            }

                            $this->design->assign('images', $images);
                            $this->design->assign('images_object_name', 'products');
                            $response['success'] = true;
                            $response['data'] = $this->design->fetch($this->design->getTemplateDir('admin').'object-images.tpl');
                            break;
                        case "delete_image":
                            $image_id = intval($this->params_arr['image_id']);

                            if (is_numeric($id))
                                $this->image->delete_image('products', $id, $image_id);
                            else
                                $this->image_temp->delete_image($id, $image_id);

                            $response['success'] = true;
                            break;
                        case "upload_internet_image":
                            $image_url = base64_decode($this->params_arr['image_url']);

                            if (is_numeric($id))
                                $this->image->add_internet_image('products', $id, $this->furl->generate_url($product->name), $image_url);
                            else
                                $this->image_temp->add_internet_image($id, $image_url);

                            $response['success'] = true;
                            break;
                        case "get_attachments":
                            $this->design->assign('object', $product);

                            if (is_numeric($id))
                                $attachments = $this->attachments->get_attachments('products', $id);
                            /*else
                            {
                                $attachments = $this->image_temp->get_images($id);
                                $this->design->assign('temp_id', $id);
                            }*/

                            $this->design->assign('attachments', $attachments);
                            $this->design->assign('attachments_object_name', 'products');
                            $response['success'] = true;
                            $response['data'] = $this->design->fetch($this->design->getTemplateDir('admin').'object-attachments.tpl');
                            break;
                        case "delete_attachment":
                            $attachment_id = intval($this->params_arr['attachment_id']);

                            if (is_numeric($id))
                                $this->attachments->delete_attachment('products', $id, $attachment_id);
                            /*else
                                $this->image_temp->delete_image($id, $image_id);*/

                            $response['success'] = true;
                            break;
                        case "upload_internet_attachment":
                            $attachment_url = base64_decode($this->params_arr['attachment_url']);

                            if (is_numeric($id))
                                $this->attachments->add_internet_attachment('products', $id, $this->furl->generate_url($product->name), $attachment_url);
                            /*else
                                $this->image_temp->add_internet_image($id, $image_url);*/

                            $response['success'] = true;
                            break;
                        case "update_attachment_name":
                            $attachment_id = intval($this->params_arr['attachment_id']);
                            $attachment_name = strval($this->params_arr['attachment_name']);

                            if (is_numeric($id))
                                $this->attachments->update_attachment($attachment_id, array('name' => $attachment_name));

                            $response['success'] = true;
                            break;
                        case "get_tags":
                            $group_id = $this->params_arr['group_id'];
                            $keyword = $this->params_arr['keyword'];
                            $tags = $this->tags->get_tags(array('group_id'=>$group_id, 'is_enabled'=>1, 'keyword'=>$keyword));
                            $response['data'] = array();
                            foreach($tags as $t)
                                $response['data'][] = array('id'=>$t->name, 'text'=>$t->name);
                            $response['success'] = true;
                            break;
                        case "get_tags_new":
                            $group_id = $this->params_arr['group_id'];
                            $keyword = $this->params_arr['keyword'];
                            $tags = $this->tags->get_tags(array('group_id'=>$group_id, 'is_enabled'=>1, 'keyword'=>$keyword));
                            $response['data'] = array();
                            foreach($tags as $t)
                                $response['data'][] = $t->name;
                            $response['success'] = true;
                            break;
                        case "get_badges":
                            $keyword = $this->params_arr['keyword'];
                            $badges = $this->badges->get_badges(array('is_visible'=>1, 'keyword'=>$keyword));
                            $response['data'] = array();
                            foreach($badges as $b)
                                $response['data'][] = array('id'=>$b->name, 'text'=>$b->name);
                            $response['success'] = true;
                            break;
                        case "get_variants":
                            $variants = $this->variants->get_variants(array('product_id'=>$id));
                            $response['data'] = array();
                            foreach($variants as $v)
                                $response['data'][] = array('id'=>$v->id, 'name'=>$v->name, 'sku'=>$v->sku, 'price'=>$this->currencies->convert($v->price,null,false), 'format_price'=>$this->currencies->convert($v->price,null,true));
                            $response['success'] = true;
                            break;
                        case "search_category":
                            $keyword = urldecode($this->params_arr['keyword']);
                            $cats = $this->categories->get_categories_filter(array('keyword'=>$keyword, 'limit'=>15, 'order'=>'c.parent_id, c.id'));
                            $response['success'] = true;
                            $response['data'] = array();
                            foreach($cats as $c)
                            {
                                $level = 0;
                                $cat = $c;
                                while ($cat && $cat->parent_id != 0)
                                {
                                    $level++;
                                    $cat = $this->categories->get_category(intval($cat->parent_id));
                                }
                                $response['data'][] = array('id'=>$c->id, 'text'=>$c->name.' (id:'.$c->id.')');
                            }
                            break;
                        case "move_category":
                            if (isset($category_id) && !empty($category_id))
                            {
                                $category = $this->categories->get_category($category_id);
                                if ($category)
                                {
                                    $query = $this->db->placehold('DELETE FROM __products_categories WHERE product_id=?', $id);
                                    $this->db->query($query);
                                    $this->categories->add_product_category($id, $category_id, 0);
                                    $response['success'] = true;
                                }
                            }
                            break;
                        case "add_category":
                            if (isset($category_id) && !empty($category_id))
                            {
                                $category = $this->categories->get_category($category_id);
                                if ($category)
                                {
                                    $product_cats = $this->categories->get_product_categories($id);
                                    $position = 0;
                                    $end_element = end($product_cats);
                                    if ($end_element)
                                        $position = $end_element->position+1;
                                    $this->categories->add_product_category($id, $category_id, $position);
                                    $response['success'] = true;
                                }
                            }
                            break;
                        case "set_stock":
                            $stock = urldecode($this->params_arr['stock']);
                            if($stock == '∞' || $stock == '')
                                $stock = null;
                            else
                                $stock = intval($stock);
                            $variants = $this->variants->get_variants(array('product_id'=>$id));
                            foreach($variants as $variant)
                                $this->variants->update_variant($variant->id, array('stock'=>$stock));

                            $variants = $this->variants->get_variants(array('product_id'=>$id));
                            $product = $this->products->get_product($id);

                            $this->recreate_product_autotags($product, $variants);

                            $response['success'] = true;
                            break;
                        case "set_discount":
                            $discount = floatval($this->params_arr['discount']);
                            if (0 < $discount && $discount < 100)
                            {
                                $variants = $this->variants->get_variants(array('product_id'=>$id));
                                foreach($variants as $variant)
                                    $this->variants->update_variant($variant->id, array('price_old' => $variant->price, 'price' => $variant->price * (100 - $discount) / 100));

                                $variants = $this->variants->get_variants(array('product_id'=>$id));
                                $product = $this->products->get_product($id);
                                $this->recreate_product_autotags($product, $variants);

                                $response['success'] = true;
                            }
                            break;
                        case "set_fix_discount":
                            $discount = floatval($this->params_arr['discount']);
                            if (0 < $discount)
                            {
                                $variants = $this->variants->get_variants(array('product_id'=>$id));
                                foreach($variants as $variant)
                                    $this->variants->update_variant($variant->id, array('price_old' => $variant->price, 'price' => $variant->price - $discount));

                                $variants = $this->variants->get_variants(array('product_id'=>$id));
                                $product = $this->products->get_product($id);
                                $this->recreate_product_autotags($product, $variants);

                                $response['success'] = true;
                            }
                            break;
                        case "remove_discount":
                            $variants = $this->variants->get_variants(array('product_id'=>$id));
                            foreach($variants as $variant)
                                $this->variants->update_variant($variant->id, array('price_old' => 0, 'price' => !empty($variant->price_old)?$variant->price_old:$variant->price));

                            $variants = $this->variants->get_variants(array('product_id'=>$id));
                            $product = $this->products->get_product($id);
                            $this->recreate_product_autotags($product, $variants);

                            $response['success'] = true;
                            break;
                        case "add_tags":
                            $group_id = intval($this->params_arr['group_id']);
                            //Получим переданные теги товара
                            $tags_values = trim(urldecode($this->params_arr['tags_values']));
                            $tags_arr = explode('#%#', $tags_values);
                            $tags_ids = array();
                            foreach($tags_arr as $t)
                            {
                                if ($tag = $this->tags->get_tags(array('group_id'=>$group_id,'name'=>$t)))
                                    $tags_ids[] = intval(reset($tag)->id);
                                else
                                {
                                    $tag = new StdClass;
                                    $tag->group_id = $group_id;
                                    $tag->name = $t;
                                    $tag->is_enabled = 1;
                                    $tag->id = $this->tags->add_tag($tag);
                                    $tags_ids[] = $tag->id;
                                }
                            }
                            //Получим существующие теги товара
                            $exists_product_tags = $this->tags->get_product_tags($id);
                            $exists_product_tags_ids = array();
                            foreach($exists_product_tags as $pt)
                                $exists_product_tags_ids[] = intval($pt->id);
                            //Добавим теги, которые отсутствуют у товара
                            foreach($tags_ids as $tid)
                                if (!in_array($tid, $exists_product_tags_ids))
                                    $this->tags->add_product_tag($id, $tid);
                            $response['success'] = true;
                            break;
                        case "remove_tags":
                            $group_id = intval($this->params_arr['group_id']);
                            //Получим переданные теги товара
                            $tags_values = trim(urldecode($this->params_arr['tags_values']));
                            $tags_arr = explode('#%#', $tags_values);
                            $tags_ids = array();
                            foreach($tags_arr as $t)
                                if ($tag = $this->tags->get_tags(array('group_id'=>$group_id,'name'=>$t)))
                                    $this->db->query("DELETE FROM __tags_products WHERE product_id=? AND tag_id=?", $id, intval(reset($tag)->id));
                            $response['success'] = true;
                            break;
                        case "move_page":    //move_page/product_id/category_id/page/items_count
                            $page = intval($this->params_arr['page']);
                            $items_count = intval($this->params_arr['items_count']);
                            $start_position = $product->position;
                            $category_filter_id = "";
                            if ($category_id)
                                $category_filter_id  = $this->db->placehold("AND pc.category_id=?", $category_id);
                            $this->db->query("SELECT p.id,p.position FROM __products p INNER JOIN __products_categories pc ON p.id=pc.product_id WHERE 1 $category_filter_id ORDER BY position LIMIT ?", $this->settings->products_num_admin*($page-1)+$items_count);
                            $positions = $this->db->results();
                            $last_el = end($positions);
                            die($last_el->id);
                            break;
                        case "set_price":
                            $value = urldecode($this->params_arr['value']);
                            $value = floatval($value);
                            $variants = $this->variants->get_variants(array('product_id'=>$id));
                            foreach($variants as $variant)
                                $this->variants->update_variant($variant->id, array('price'=>$value));

                            $variants = $this->variants->get_variants(array('product_id'=>$id));
                            $product = $this->products->get_product($id);
                            $this->recreate_product_autotags($product, $variants);

                            $response['success'] = true;
                            break;
                        case "inc_fix_price":
                            $value = urldecode($this->params_arr['value']);
                            $value = floatval($value);
                            $variants = $this->variants->get_variants(array('product_id'=>$id));
                            foreach($variants as $variant)
                                $this->variants->update_variant($variant->id, array('price'=>$variant->price + $value));

                            $variants = $this->variants->get_variants(array('product_id'=>$id));
                            $product = $this->products->get_product($id);
                            $this->recreate_product_autotags($product, $variants);

                            $response['success'] = true;
                            break;
                        case "sub_fix_price":
                            $value = urldecode($this->params_arr['value']);
                            $value = floatval($value);
                            $variants = $this->variants->get_variants(array('product_id'=>$id));
                            foreach($variants as $variant)
                                $this->variants->update_variant($variant->id, array('price'=>$variant->price - $value));

                            $variants = $this->variants->get_variants(array('product_id'=>$id));
                            $product = $this->products->get_product($id);
                            $this->recreate_product_autotags($product, $variants);

                            $response['success'] = true;
                            break;
                        case "inc_percent":
                            $value = urldecode($this->params_arr['value']);
                            $value = floatval($value);
                            $variants = $this->variants->get_variants(array('product_id'=>$id));
                            foreach($variants as $variant)
                                $this->variants->update_variant($variant->id, array('price'=>$variant->price + ($variant->price * $value / 100)));

                            $variants = $this->variants->get_variants(array('product_id'=>$id));
                            $product = $this->products->get_product($id);
                            $this->recreate_product_autotags($product, $variants);

                            $response['success'] = true;
                            break;
                        case "sub_percent":
                            $value = urldecode($this->params_arr['value']);
                            $value = floatval($value);
                            $variants = $this->variants->get_variants(array('product_id'=>$id));
                            foreach($variants as $variant)
                                $this->variants->update_variant($variant->id, array('price'=>$variant->price - ($variant->price * $value / 100)));

                            $variants = $this->variants->get_variants(array('product_id'=>$id));
                            $product = $this->products->get_product($id);
                            $this->recreate_product_autotags($product, $variants);

                            $response['success'] = true;
                            break;
                        case "set_currency":
                            $value = intval($this->params_arr['value']);

                            $this->products->update_product($id, array('currency_id' => $value));

                            $variants = $this->variants->get_variants(array('product_id'=>$id));
                            $product = $this->products->get_product($id);

                            $this->recreate_product_autotags($product, $variants);
                            break;
                        case "set_add_flag":
                            $flag = $this->params_arr['flag'];
                            $value = intval($this->params_arr['value']);

                            switch($flag){
                                case "add_flag1":
                                    $this->products->update_product($id, array('add_flag1' => $value));
                                    break;
                                case "add_flag2":
                                    $this->products->update_product($id, array('add_flag2' => $value));
                                    break;
                                case "add_flag3":
                                    $this->products->update_product($id, array('add_flag3' => $value));
                                    break;
                            }
                            break;
                        case "set_add_field":
                            $field = $this->params_arr['field'];
                            $value = $this->params_arr['value'];

                            switch($field){
                                case "add_field1":
                                    $this->products->update_product($id, array('add_field1' => $value));
                                    break;
                                case "add_field2":
                                    $this->products->update_product($id, array('add_field2' => $value));
                                    break;
                                case "add_field3":
                                    $this->products->update_product($id, array('add_field3' => $value));
                                    break;
                            }
                            break;
                    }

                if ($json_answer)
                {
                    header("Content-type: application/json; charset=UTF-8");
                    header("Cache-Control: must-revalidate");
                    header("Pragma: no-cache");
                    header("Expires: -1");
                    if ($mode == "get_tags_new")
                        print json_encode($response['data']);
                    else
                        print json_encode($response);
                    die();
                }
            }
        if (isset($product) && $product->id){
            if ($product->id){

                //Рейтинг
                $product->rating = $this->ratings->calc_product_rating($product->id);

                // Варианты товара
                $variants = $this->variants->get_variants(array('product_id'=>$product->id));
                $this->design->assign('product_variants', $variants);

                // Изображения товара
                $images = $this->image->get_images('products', $product->id);
                $this->design->assign('images', $images);

                // Аттачи товара
                $attachments = $this->attachments->get_attachments('products', $product->id);
                $this->design->assign('attachments', $attachments);

                // Категории товара
                $product_categories = $this->categories->get_categories(array('product_id'=>$product->id));
                $this->design->assign('product_categories', $product_categories);
                if (!empty($product_categories))
                {
                    $main_category = array_shift($product_categories);
                    $this->design->assign('main_category', $main_category);
                }

                if (!empty($product->modificators))
                    $product->modificators = explode(',', $product->modificators);
                else
                    $product->modificators = array();
                if (!empty($product->modificators_groups) && !is_array($product->modificators_groups))
                    $product->modificators_groups = explode(',', $product->modificators_groups);
                else
                    $product->modificators_groups = array();

                // Бейджи товара
                $product_badges = $this->badges->get_product_badges($product->id);
                $this->design->assign('product_badges', $product_badges);

                //Теги товара
                $product_tags_positions = array();
                $product_tags_groups = array();

                //Группы тегов, которые входят в набор групп тегов
                if ($product_categories)
                    foreach($product_categories as $pc){
                        $categories_array = array();
                        $tmp_category = $this->categories->get_category($pc->id);
                        while ($tmp_category){
                            $categories_array[] = $tmp_category->id;
                            $tmp_category = $this->categories->get_category($tmp_category->parent_id);
                        }
                        $set_tag_groups = $this->tags->get_tags_set_tags(array('category_id'=>$categories_array));

                        foreach($set_tag_groups as $gr)
                            if (!array_key_exists($gr->group_id, $product_tags_groups))
                            {
                                $product_tags_groups[$gr->group_id] = array();
                                $product_tags_positions[] = $gr->group_id;
                            }
                    }

                // Теги товара
                $product_tags = $this->tags->get_product_tags($product->id);
                foreach($product_tags as $tag)
                {
                    if (!array_key_exists($tag->group_id, $product_tags_groups))
                    {
                        $product_tags_groups[$tag->group_id] = array();
                        $product_tags_positions[] = $tag->group_id;
                    }
                    $product_tags_groups[$tag->group_id][] = $tag;
                }

                $this->design->assign('product_tags', $product_tags_groups);
                $this->design->assign('product_tags_positions', $product_tags_positions);

                //Сопутствующие товары
                $related_products = $this->products->get_related_products(array('product_id'=>$product->id, 'product_type'=>0));
                if ($related_products)
                {
                    foreach($related_products as $index=>$related)
                    {
                        $related_products[$index] = $this->products->get_product($related->related_id);
                        $related_products[$index]->variants = $this->variants->get_variants(array('product_id'=>$related->related_id));
                        $related_products[$index]->variant = @reset($related_products[$index]->variants);
                        $related_products[$index]->in_stock = false;
                        $related_products[$index]->in_order = false;
                        foreach($related_products[$index]->variants as $rv)
                            if ($rv->stock > 0)
                                $related_products[$index]->in_stock = true;
                            else
                                if ($rv->stock < 0)
                                    $related_products[$index]->in_order = true;
                        $related_products[$index]->images = $this->image->get_images('products', $related->related_id);
                        if (isset($related_products[$index]->images[0]))
                            $related_products[$index]->image = $related_products[$index]->images[0];
                        $related_products[$index]->attachments = $this->attachments->get_attachments('products', $related->related_id);
                        $related_products[$index]->badges = $this->badges->get_product_badges($related->related_id);
                    }
                    $this->design->assign('related_products', $related_products);
                }

                //Аналогичные товары
                //$analogs_products = $this->products->get_related_products(array('product_id'=>$product->id, 'product_type'=>3));
                $analogs_products = $this->products->get_analogs_by_product_id($product->id);
                if ($analogs_products)
                {
                    $this->design->assign('analogs_group_id', $analogs_products[0]->group_id);
                    foreach($analogs_products as $index=>$analog)
                    {
                        $analogs_products[$index] = $this->products->get_product($analog->product_id);
                        $analogs_products[$index]->variants = $this->variants->get_variants(array('product_id'=>$analog->product_id));
                        $analogs_products[$index]->variant = @reset($analogs_products[$index]->variants);
                        $analogs_products[$index]->in_stock = false;
                        $analogs_products[$index]->in_order = false;
                        foreach($analogs_products[$index]->variants as $rv)
                            if ($rv->stock > 0)
                                $analogs_products[$index]->in_stock = true;
                            else
                                if ($rv->stock < 0)
                                    $analogs_products[$index]->in_order = true;
                        $analogs_products[$index]->images = $this->image->get_images('products', $analog->product_id);
                        if (isset($analogs_products[$index]->images[0]))
                            $analogs_products[$index]->image = $analogs_products[$index]->images[0];
                        $analogs_products[$index]->attachments = $this->attachments->get_attachments('products', $analog->product_id);
                        $analogs_products[$index]->badges = $this->badges->get_product_badges($analog->product_id);
                    }
                    $this->design->assign('analogs_products', $analogs_products);
                }
            }
            $this->design->assign('product', $product);
        }

        $tab = "main";
        if (array_key_exists("tab", $this->params_arr))
            $tab = $this->params_arr["tab"];
        $this->design->assign('tab', $tab);

        $tags_groups = array();
        foreach($this->tags->get_taggroups() as $group)
            $tags_groups[$group->id] = $group;
        foreach($tags_groups as $index=>$group)
            $tags_groups[$index]->tags = $this->tags->get_tags(array('is_enabled'=>1,'group_id'=>$group->id));
        $this->design->assign('tags_groups', $tags_groups);

        $this->all_categories = array();
        $cats = $this->categories->get_categories_tree();
        foreach($cats as $c)
            $this->process_category($c);
        $this->design->assign('all_categories', $this->all_categories);

        $currencies = $this->currencies->get_currencies(array('is_enabled'=>1));
        $this->design->assign('all_currencies', $currencies);

        $this->design->assign('brands', $this->brands->get_brands(array('is_visible'=>1,'sort'=>'name','sort_type'=>'asc')));

        $this->db->query('SELECT distinct group_id FROM __analogs_products ORDER BY group_id');
        $this->design->assign('analogs_groups', $this->db->results('group_id'));

        $this->design->assign('modificators', $this->modificators->get_modificators(array('parent_id'=>null, 'is_visible'=>1)));
        $this->design->assign('modificators_groups', $this->modificators->get_modificators_groups(array('is_visible' => 1)));

        return $this->design->fetch($this->design->getTemplateDir('admin').'product.tpl');
    }
}