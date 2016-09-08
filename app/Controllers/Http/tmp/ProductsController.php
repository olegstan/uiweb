<?php
namespace app\controllers;

use app\layer\LayerController;

class ProductsController extends LayerController
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
                if (array_key_exists($x[0], $this->params_arr) && count($x)>1)
                {
                    $this->params_arr[$x[0]] = (array) $this->params_arr[$x[0]];
                    $this->params_arr[$x[0]][] = $x[1];
                }
                else
                {
                    $this->params_arr[$x[0]] = "";
                    if (count($x)>1)
                        $this->params_arr[$x[0]] = $x[1];
                }
            }
        }
    }

    private function make_categories_lazy_tree(&$tree, $category_path)
    {
        $next_id = @reset($category_path);
        foreach($tree as &$t)
            if ($t['id'] == $next_id)
            {
                $t['subcategories'] = $this->categories->get_categories_lazy_load_filter(array('parent_id'=>$next_id, 'is_visible'=>1));
                if (count($category_path) > 1)
                {
                    array_shift($category_path);
                    $this->make_categories_lazy_tree($t['subcategories'], $category_path);
                }
            }
    }

    private function recursive_categories_tree(&$tree, $steps)
    {
        if ($steps <= 0)
            return;

        foreach($tree as &$tree_item)
        {
            $tree_item['subcategories'] = $this->categories->get_categories_lazy_load_filter(array('parent_id'=>$tree_item['id'], 'is_visible'=>1));
            $this->recursive_categories_tree($tree_item['subcategories'], $steps-1);
        }
    }

    function fetch()
    {
        $category_id = 0;

        /*ИЕРАРХИЯ УРЛА */
        /*$url_arr = explode('/', $this->param_url);
        foreach($url_arr as $u)
        {
            $this->db->query("SELECT id FROM __categories WHERE parent_id=? AND url=?", $category_id, $u);
            $tmp_c = $this->db->result();
            if (!$tmp_c)
                return false;
            $category_id = $tmp_c->id;
        }*/

        if (!empty($this->param_url))
            $tmp_c = $this->categories->get_category($this->param_url);
        if (!empty($tmp_c))
            $category_id = $tmp_c->id;

        if (!empty($tmp_c) && !$tmp_c->is_visible && (empty($_SESSION['admin']) || $_SESSION['admin'] != 'admin'))
            return false;

        if (empty($tmp_c) && (!array_key_exists('keyword', $this->params_arr) || empty($this->params_arr['keyword'])) && !array_key_exists('history_products', $this->params_arr) && !array_key_exists('related_products', $this->params_arr) && !array_key_exists('reviews', $this->params_arr) && !array_key_exists('review-images', $this->params_arr))
            return false;

        $filter = array();
        $filter['is_visible'] = 1;
        $filter['limit'] = 10000;
        if ($this->settings->catalog_show_all_products)
            $filter['in_stock'] = 1;
        $current_page = 1;
        $ajax = false;
        $output_format = "";

        foreach($this->params_arr as $p=>$v)
        {
            switch ($p)
            {
                case "keyword":
                    if (!empty($v))
                    {
                        //echo $v."<BR>".urldecode($v)."<BR>".rawurldecode($v)."<BR>".html_entity_decode($v,null,'UTF-8')."<BR>";
                        $filter[$p] = $v;
                        $this->design->assign('keyword', $v);
                    }
                    else
                        unset($this->params_arr[$p]);
                    break;
                case "page":
                    if (!empty($v))
                        if ($v == "all")
                            $current_page = "all";
                        else
                            $current_page = intval($v);
                    else
                        unset($this->params_arr[$p]);
                    break;
                case "ajax":
                    $ajax = intval($v);
                    unset($this->params_arr[$p]);
                    break;
                case "format":
                    if ($v != "append")
                        $filter['limit'] = 100;
                    $output_format = $v;
                    //unset($this->params_arr[$p]);
                    break;
                case "category_id":
                    $category_id = intval($v);
                    break;
                case "sort":
                    if (!empty($v))
                        $filter[$p] = $v;
                    else
                        unset($this->params_arr[$p]);
                    break;
                case "sort_type":
                    if (!empty($v))
                    {
                        if (!array_key_exists('sort_type', $filter))
                            $filter[$p] = $v;
                    }
                    else
                        unset($this->params_arr[$p]);
                    break;
                case "history_products":
                    $history_products = array();
                    if(!empty($_COOKIE['history_products']))
                    {
                        $history_products_ids = explode(',', $_COOKIE['history_products']);
                        $history_products_ids = array_reverse($history_products_ids);

                        foreach($history_products_ids as $history_id)
                        {
                            $tmp_product = $this->products->get_product($history_id);
                            if ($tmp_product->is_visible)
                                $history_products[] = $tmp_product;
                        }

                        foreach($history_products as &$history_product)
                        {
                            $history_product->images = $this->image->get_images('products', $history_product->id);
                            $history_product->image = reset($history_product->images);
                            $variants_filter = array('product_id'=>$history_product->id, 'is_visible'=>1);
                            if ($this->settings->catalog_default_variants_sort == "stock")
                                $variants_filter['sort'] = $this->db->placehold('abs(IFNULL(v.stock, ?)) desc, stock desc', $this->settings->max_order_amount);
                            $history_product->variants = $this->variants->get_variants($variants_filter);
                            $history_product->variant = reset($history_product->variants);
                        }
                    }
                    $this->design->assign('history_products', $history_products);
                    $template_result = $this->design->fetch($this->design->getTemplateDir('frontend').'viewed-products.tpl');
                    header("Content-type: application/json; charset=UTF-8");
                    header("Cache-Control: must-revalidate");
                    header("Pragma: no-cache");
                    header("Expires: -1");
                    print json_encode($template_result);
                    die();
                    break;
                case "related_products":
                    $related_products = $this->products->get_related_products(array('product_id'=>$v, 'product_type'=>0, 'is_visible'=>1));
                    if ($related_products)
                    {
                        foreach($related_products as $index=>$related)
                            $related_products[$index] = $this->products->get_product($related->related_id);

                        $related_products = $this->products->get_data_for_frontend_products($related_products);

                        $this->design->assign('related_products', $related_products);
                    }

                    //$analogs_products = $this->products->get_related_products(array('product_id'=>$v, 'product_type'=>3, 'is_visible'=>1));
                    $analogs_products = $this->products->get_analogs_by_product_id($v);
                    if ($analogs_products)
                    {
                        foreach($analogs_products as $index=>$analog)
                        {
                            $tmp_p = $this->products->get_product($analog->product_id);

                            if ($analog->product_id != $v && $tmp_p->is_visible)
                                $analogs_products[$index] = $tmp_p;
                            else
                                unset($analogs_products[$index]);
                        }

                        $analogs_products = $this->products->get_data_for_frontend_products($analogs_products);

                        $this->design->assign('analogs_products', $analogs_products);
                    }

                    $tags_groups = $this->tags->get_taggroups(array('is_auto'=>0, 'is_enabled'=>1));
                    $this->design->assign('tags_groups', $tags_groups);

                    $this->design->assign('current_params', $this->params_arr);
                    $this->design->assign('params_arr', $this->params_arr);
                    /*Зададим формат вывода по умолчанию*/
                    if (!array_key_exists('mode', $this->params_arr))
                        $this->design->assign('mode', $this->settings->default_related_products_mode);
                    else
                        $this->design->assign('mode', $this->params_arr['mode']);
                    $template_result = $this->design->fetch($this->design->getTemplateDir('frontend').'related-products.tpl');
                    header("Content-type: application/json; charset=UTF-8");
                    header("Cache-Control: must-revalidate");
                    header("Pragma: no-cache");
                    header("Expires: -1");
                    print json_encode($template_result);
                    die();
                    break;
                case "reviews":
                    $filter = array('product_id'=>$v, 'is_visible'=>1, 'moderated'=>1);
                    if (array_key_exists('sort', $this->params_arr))
                        $filter['sort'] = $this->params_arr['sort'];
                    $current_page = 1;
                    if (array_key_exists('page', $this->params_arr) && !empty($this->params_arr['page']))
                        if ($this->params_arr['page'] == "all")
                            $current_page = "all";
                        else
                            $current_page = intval($this->params_arr['page']);

                    if (is_numeric($current_page))
                        $current_page = max(1, $current_page);
                    $this->design->assign('current_page_num', $current_page);

                    $reviews_count = $this->reviews->count_reviews(array('product_id'=>$v, 'is_visible'=>1, 'moderated'=>1));
                    $this->design->assign('reviews_count', $reviews_count);

                    // Постраничная навигация
                    if (array_key_exists('page', $this->params_arr) && $this->params_arr['page'] == 'all')
                        $items_per_page = $reviews_count;
                    else
                        $items_per_page = $this->settings->reviews_num;

                    $filter['page'] = $current_page;
                    $filter['limit'] = $items_per_page;

                    $pages_num = ceil($reviews_count/$items_per_page);
                    $this->design->assign('total_pages_num', $pages_num);

                    $this->design->assign('data_type', 'reviews');

                    $reviews = $this->reviews->get_reviews($filter);
                    if ($reviews)
                    {
                        $todayday = new DateTime();
                        $yesterday = new DateTime();
                        $yesterday->modify('-1 day');
                        $days_arr = array('Monday' => 'Пн',
                                            'Tuesday' => 'Вт',
                                            'Wednesday' => 'Ср',
                                            'Thursday' => 'Чт',
                                            'Friday' => 'Пт',
                                            'Saturday' => 'Сб',
                                            'Sunday' => 'Вс');
                        $months_arr = array(1 => 'января',
                                            2 => 'февраля',
                                            3 => 'марта',
                                            4 => 'апреля',
                                            5 => 'мая',
                                            6 => 'июня',
                                            7 => 'июля',
                                            8 => 'августа',
                                            9 => 'сентября',
                                            10 => 'октября',
                                            11 => 'ноября',
                                            12 => 'декабря');


                        foreach($reviews as $index=>$review)
                        {
                            if (!empty($review->user_id))
                                $reviews[$index]->reviews_count = $this->reviews->count_reviews(array('user_id'=>$review->user_id, 'is_visible'=>1));
                            if (!empty($review->session_id))
                                $reviews[$index]->reviews_count = $this->reviews->count_reviews(array('session_id'=>$review->session_id, 'is_visible'=>1));
                            if (date("Ymd", $reviews[$index]->datetime) == $todayday->format("Ymd"))
                                $reviews[$index]->day_str = "Сегодня в " . date("H:i", $reviews[$index]->datetime);
                            else
                                if (date("Ymd", $reviews[$index]->datetime) == $yesterday->format("Ymd"))
                                    $reviews[$index]->day_str = "Вчера в " . date("H:i", $reviews[$index]->datetime);
                                else
                                    if (date("Y", $reviews[$index]->datetime) == date("Y"))
                                        $reviews[$index]->day_str = date("d",$reviews[$index]->datetime) . " " . $months_arr[date("n",$reviews[$index]->datetime)] . " в " . date("H:i", $reviews[$index]->datetime);
                                    else
                                        $reviews[$index]->day_str = date("d",$reviews[$index]->datetime) . " " . $months_arr[date("n",$reviews[$index]->datetime)] . " " . date("Y",$reviews[$index]->datetime) . " в " . date("H:i", $reviews[$index]->datetime);
                            $reviews[$index]->images = $this->image->get_images('reviews', $review->id);
                        }

                        $this->design->assign('reviews', $reviews);
                    }
                    $this->design->assign('temp_id', uniqid());
                    $this->design->assign('product', $this->products->get_product($v));
                    $this->design->assign('current_params', $this->params_arr);
                    $this->design->assign('params_arr', $this->params_arr);
                    $template_result = $this->design->fetch($this->design->getTemplateDir('frontend').'reviews-product.tpl');
                    header("Content-type: application/json; charset=UTF-8");
                    header("Cache-Control: must-revalidate");
                    header("Pragma: no-cache");
                    header("Expires: -1");
                    print json_encode($template_result);
                    die();
                    break;
                case "review-images":
                    $mode = @$this->params_arr['mode'];

                    switch($mode){
                        case "upload-images":
                            $uploaded = $this->request->files('uploaded-images');
                            $temp_id = $this->request->post('object_id');

                            foreach($uploaded as $index=>$ufile)
                                $img = $this->image_temp->add_image($temp_id, $ufile['name'], $ufile['tmp_name']);

                            header("Content-type: application/json; charset=UTF-8");
                            header("Cache-Control: must-revalidate");
                            header("Pragma: no-cache");
                            header("Expires: -1");
                            print json_encode(1);
                            die();
                            break;
                        case "get-images":
                            $temp_id = $this->params_arr['temp_id'];

                            if (empty($temp_id))
                                break;

                            $this->design->assign('temp_id', $temp_id);
                            $images = $this->image_temp->get_images($temp_id);
                            $this->design->assign('images', $images);
                            $response['success'] = true;
                            $response['data'] = $this->design->fetch($this->design->getTemplateDir('frontend').'reviews-product-images.tpl');
                            header("Content-type: application/json; charset=UTF-8");
                            header("Cache-Control: must-revalidate");
                            header("Pragma: no-cache");
                            header("Expires: -1");
                            print json_encode($response);
                            die();
                            break;
                        case "delete-image":
                            $image_id = $this->params_arr['image_id'];
                            $temp_id = $this->params_arr['temp_id'];

                            if (empty($temp_id) || empty($image_id))
                                break;

                            $this->image_temp->delete_image($temp_id, $image_id);
                            header("Content-type: application/json; charset=UTF-8");
                            header("Cache-Control: must-revalidate");
                            header("Pragma: no-cache");
                            header("Expires: -1");
                            print json_encode(1);
                            die();
                            break;
                    }

                    break;
            }
        }

        if ($category_id)
        {
            $category = $this->categories->get_category(intval($category_id));

            $category->images = $this->image->get_images('categories-gallery', $category->id);
            $category->image = @reset($category->images);

            if (!empty($category->subcategories))
                foreach($category->subcategories as $subcat){
                    $subcat->images = $this->image->get_images('categories', $subcat->id);
                    $subcat->image = @reset($subcat->images);
                }

            $filter['category_id'] = $category->children;
            $this->design->assign('category', $category);
            $this->design->assign('body_category_css', $category->css_class);
            $this->design->assign('category_id', $category_id);
            //Составим путь выборки категории, чтоб подгрузить ее во фронтенде
            $category_path = array();
            $tmp_category = $category;
            while($tmp_category->parent_id != 0)
            {
                $tmp_category = $this->categories->get_category(intval($tmp_category->parent_id));
                $category_path[] = $tmp_category->id;
            }
            $category_path = array_reverse($category_path);

            //Подгрузим подкатегории
            $load_category_id = $category->id;
            if (!empty($category_path))
                $load_category_id = reset($category_path);

            $category_header = $this->categories->get_category($load_category_id);
            if ($category_header->parent_id == 0 && $category_header->children == array($load_category_id))
                $current_categories_frontend = array();
            else
                $current_categories_frontend = $this->categories->get_categories_lazy_load_filter(array('parent_id'=>0, 'is_visible'=>1, 'id' => $load_category_id));

            $this->make_categories_lazy_tree($current_categories_frontend, array_merge($category_path, array($category->id)));
            $this->recursive_categories_tree($current_categories_frontend, $this->settings->catalog_count_opened_level);
            $this->design->assign('category_header', $category_header);
            $this->design->assign('current_categories_frontend', $current_categories_frontend);
            $this->design->assign('current_level', count($category_path));
            //Подгрузим покатегории (конец)

            //Подготовим статическое меню для фронтенда
            $categories_frontend = $this->categories->get_categories_lazy_load_filter(array('parent_id'=>0, 'is_visible'=>1));
            $this->make_categories_lazy_tree($categories_frontend, array_merge($category_path, array($category->id)));
            $this->recursive_categories_tree($categories_frontend, $this->settings->catalog_count_opened_level);
            $this->design->assign('categories_frontend', $categories_frontend);

            $category_path = join(",", $category_path);
            $this->design->assign('category_path', $category_path);
        }

        if (isset($category) && !empty($category) && $category->set_id > 0)
        {
            $tag_set = $this->tags->get_tags_set($category->set_id);
            if (!empty($tag_set) && $tag_set->is_visible)
            {
                $this->design->assign('tag_set', $tag_set);
                if (!empty($tag_set->id))
                {
                    $filter_tags = array('is_enabled'=>1, 'category_id'=>$category->children);
                    $tags_groups = $this->tags->get_tags_set_tags(array('set_id'=>$tag_set->id, 'in_filter'=>1/*, 'mode'=>'select'*/));
                    foreach($tags_groups as $index=>$tag_group)
                        if (array_key_exists($tag_group->name_translit, $this->params_arr))
                        {
                            if (!array_key_exists('tags', $filter_tags))
                                $filter_tags['tags'] = array();
                            if ($this->params_arr[$tag_group->name_translit] != '0')
                                if ($tag_group->mode == "range")
                                {
                                    $v = explode(';', $this->params_arr[$tag_group->name_translit]);
                                    if (count($v) == 2)
                                    {
                                        $filter_tags['tags'][$tag_group->group_id] = new StdClass;
                                        $filter_tags['tags'][$tag_group->group_id]->from = strval($v[0]);
                                        $filter_tags['tags'][$tag_group->group_id]->to = strval($v[1]);
                                    }
                                }
                                else
                                    $filter_tags['tags'][$tag_group->group_id] = (array) $this->params_arr[$tag_group->name_translit];
                        }

                    if ($this->settings->catalog_show_all_products && !empty($filter_tags['tags']))
                    {
                        $nalichie_group = $this->tags->get_taggroup('Есть в наличии');
                        $in_stock = $this->tags->get_tags(array('group_id' => $nalichie_group->id, 'name'=>'да', 'is_enabled'=>1));
                        $in_order = $this->tags->get_tags(array('group_id' => $nalichie_group->id, 'name'=>'под заказ', 'is_enabled'=>1));
                        if (!empty($in_stock) || !empty($in_order))
                        {
                            $filter_tags['tags'][$nalichie_group->id] = array();

                            if (!empty($in_stock))
                                $filter_tags['tags'][$nalichie_group->id][] = $in_stock[0]->id;

                            if (!empty($in_order))
                                $filter_tags['tags'][$nalichie_group->id][] = $in_order[0]->id;
                        }
                    }

                    foreach($tags_groups as $index=>$tag_group)
                    {
                        $group_filter_tags = $filter_tags;
                        $group_filter_tags['group_id'] = $tag_group->group_id;
                        if (array_key_exists('tags', $group_filter_tags) && array_key_exists($tag_group->group_id, $group_filter_tags['tags']))
                            unset($group_filter_tags['tags'][$tag_group->group_id]);
                        $group_filter_tags['products_is_visible'] = 1;
                        if ($this->settings->catalog_show_all_products)
                            $group_filter_tags['products_in_stock'] = 1;

                        if ($tag_group->mode == "range")
                        {
                            $tags_groups[$index]->min_value_available = $this->tags->get_tags_min_value($group_filter_tags);
                            $tags_groups[$index]->max_value_available = $this->tags->get_tags_max_value($group_filter_tags);
                            $tags_groups[$index]->min_value = $this->tags->get_tags_min_value(array('is_enabled'=>1, 'category_id'=>$category->children, 'group_id'=>$tag_group->group_id));
                            $tags_groups[$index]->max_value = $this->tags->get_tags_max_value(array('is_enabled'=>1, 'category_id'=>$category->children, 'group_id'=>$tag_group->group_id));

                            //Приведение к шагу
                            $tags_groups[$index]->min_value = floor($tags_groups[$index]->min_value / $tags_groups[$index]->diapason_step) * $tags_groups[$index]->diapason_step;
                            $tags_groups[$index]->max_value = ceil($tags_groups[$index]->max_value / $tags_groups[$index]->diapason_step) * $tags_groups[$index]->diapason_step;
                        }
                        elseif($tag_group->mode == "logical")
                        {
                            $available = $this->tags->get_tags($group_filter_tags);
                            $tags_groups[$index]->tags_available = array();
                            foreach($available as $a)
                                $tags_groups[$index]->tags_available[] = $a->id;
                            $tags_groups[$index]->tags_popular = $this->tags->get_tags(array('is_enabled'=>1, 'group_id'=>$tag_group->group_id, 'is_popular' => 1, 'sort' => 'position', 'sort_type' => 'asc'));
                            $tags_groups[$index]->tags_all = $this->tags->get_tags(array('is_enabled'=>1, 'group_id'=>$tag_group->group_id, 'sort' => 'position', 'sort_type' => 'asc'));
                        }
                        else
                        {
                            $available = $this->tags->get_tags($group_filter_tags);
                            $tags_groups[$index]->tags_available = array();
                            foreach($available as $a)
                                $tags_groups[$index]->tags_available[] = $a->id;
                            $tags_groups[$index]->tags_popular = $this->tags->get_tags(array('is_enabled'=>1, 'category_id'=>$category->children, 'group_id'=>$tag_group->group_id, 'sort'=>'position', 'numeric_sort'=>$tag_group->numeric_sort, 'is_popular' => 1));
                            $tags_groups[$index]->tags_popular_arr = array();
                            foreach($tags_groups[$index]->tags_popular as $p)
                                $tags_groups[$index]->tags_popular_arr[] = $p->id;
                            $tags_groups[$index]->tags_all = $this->tags->get_tags(array('is_enabled'=>1, 'category_id'=>$category->children, 'group_id'=>$tag_group->group_id, 'sort'=>'position', 'numeric_sort'=>$tag_group->numeric_sort));
                            $tags_groups[$index]->tags_all_arr = array();
                            foreach($tags_groups[$index]->tags_all as $p)
                                $tags_groups[$index]->tags_all_arr[] = $p->id;
                        }
                    }
                    $this->design->assign('filter_tags_groups', $tags_groups);
                    if (array_key_exists('tags', $filter_tags))
                    {
                        $this->design->assign('filter_tags', $filter_tags['tags']);
                        $filter['tags'] = $filter_tags['tags'];
                    }
                }
            }
        }

        $sort_methods = array();
        if ($this->settings->settings_sort_position)
            $sort_methods[] = 'position';
        if ($this->settings->settings_sort_price){
            $sort_methods[] = 'price_asc';
            $sort_methods[] = 'price_desc';
        }
        if ($this->settings->settings_sort_popular){
            $sort_methods[] = 'popular_asc';
            $sort_methods[] = 'popular_desc';
        }
        if ($this->settings->settings_sort_newest){
            $sort_methods[] = 'newest_asc';
            $sort_methods[] = 'newest_desc';
        }
        if ($this->settings->settings_sort_name){
            $sort_methods[] = 'name_asc';
            $sort_methods[] = 'name_desc';
        }

        /*Зададим сортировку по умолчанию*/
        if (!array_key_exists('sort', $this->params_arr))
        {
            /*$this->params_arr['sort'] = 'position';
            $this->params_arr['sort_type'] = 'asc';*/
            $filter['sort'] = reset($sort_methods);//'position';
            $filter['sort_type'] = 'asc';
            $sort_type = isset($category)?$category->sort_type:'';
            if (empty($sort_type))
                $sort_type = $this->settings->catalog_default_sort;
            if (!in_array($sort_type, $sort_methods))
                $sort_type = reset($sort_methods);
            switch($sort_type){
                case 'position':
                    $filter['sort'] = 'position';
                    $filter['sort_type'] = 'asc';
                    break;
                case 'price_asc':
                    $filter['sort'] = /*$this->params_arr['sort'] =*/ 'price';
                    $filter['sort_type'] = /*$this->params_arr['sort_type'] =*/ 'asc';
                    break;
                case 'price_desc':
                    $filter['sort'] = /*$this->params_arr['sort'] =*/ 'price';
                    $filter['sort_type'] = /*$this->params_arr['sort_type'] =*/ 'desc';
                    break;
                case 'popular_asc':
                    $filter['sort'] = /*$this->params_arr['sort'] =*/ 'popular';
                    $filter['sort_type'] = /*$this->params_arr['sort_type'] =*/ 'asc';
                    break;
                case 'popular_desc':
                    $filter['sort'] = /*$this->params_arr['sort'] =*/ 'popular';
                    $filter['sort_type'] = /*$this->params_arr['sort_type'] =*/ 'desc';
                    break;
                case 'newest_asc':
                    $filter['sort'] = /*$this->params_arr['sort'] =*/ 'newest';
                    $filter['sort_type'] = /*$this->params_arr['sort_type'] =*/ 'asc';
                    break;
                case 'newest_desc':
                    $filter['sort'] = /*$this->params_arr['sort'] =*/ 'newest';
                    $filter['sort_type'] =/* $this->params_arr['sort_type'] =*/ 'desc';
                    break;
                case 'name_asc':
                    $filter['sort'] = /*$this->params_arr['sort'] =*/ 'name';
                    $filter['sort_type'] = /*$this->params_arr['sort_type'] =*/ 'asc';
                    break;
                case 'name_desc':
                    $filter['sort'] = /*$this->params_arr['sort'] =*/ 'name';
                    $filter['sort_type'] = /*$this->params_arr['sort_type'] =*/ 'desc';
                    break;
            }
        }
        else
            if (!array_key_exists('sort_type', $this->params_arr))
            {
                /*$this->params_arr['sort_type'] = 'asc';*/
                $filter['sort_type'] = 'asc';
            }
        $this->design->assign('sort', $filter['sort']);
        $this->design->assign('sort_type', $filter['sort_type']);

        $this->design->assign('sort', $filter['sort']);
        $this->design->assign('sort_type', $filter['sort_type']);
        /*$this->params_arr['sort'] = $filter['sort'];
        $this->params_arr['sort_type'] = $filter['sort_type'];*/

        /*Зададим формат вывода по умолчанию*/
        if (!array_key_exists('mode', $this->params_arr))
        {
            if (!empty($category))
            {
                if (!empty($category->show_mode))
                    $this->design->assign('mode', $category->show_mode);
                else
                    $this->design->assign('mode', $this->settings->default_show_mode);
                //$this->params_arr['mode'] = $category->show_mode;
            }
            else
                $this->design->assign('mode', $this->settings->default_show_mode);
                //$this->params_arr['mode'] = 'list';
        }
        else
            $this->design->assign('mode', $this->params_arr['mode']);

        $this->design->assign('current_url', $this->param_url.(empty($this->param_url)?'':'/'));
        $this->design->assign('current_params', $this->params_arr);

        // Если страница не задана, то равна 1
        if (is_numeric($current_page))
            $current_page = max(1, $current_page);
        $this->design->assign('current_page_num', $current_page);

        $products_count = $this->products->count_products($filter);
        $this->design->assign('products_count', $products_count);

        if ($output_format == "products_count" && $ajax)
        {
            $data = array('success'=>true, 'filter' => $this->design->fetch($this->design->getTemplateDir('frontend').'filter.tpl'));
            header("Content-type: application/json; charset=UTF-8");
            header("Cache-Control: must-revalidate");
            header("Pragma: no-cache");
            header("Expires: -1");
            print json_encode($data);
            die();
        }

        // Постраничная навигация
        if (array_key_exists('page', $this->params_arr) && $this->params_arr['page'] == 'all')
            $items_per_page = $products_count;
        else
            $items_per_page = $this->settings->products_num;

        $filter['page'] = $current_page;
        $filter['limit'] = $items_per_page;

        $products = $this->products->get_products($filter);

        $products = $this->products->get_data_for_frontend_products($products);
        /*foreach($products as $index=>$product)
        {
            $products[$index]->images = $this->image->get_images('products', $product->id);
            $products[$index]->image = reset($products[$index]->images);
            $variants_filter = array('product_id'=>$product->id, 'is_visible'=>1);
            if ($this->settings->catalog_default_variants_sort == "stock")
                $variants_filter['sort'] = $this->db->placehold('abs(IFNULL(v.stock, ?)) desc, stock desc', $this->settings->max_order_amount);
            $products[$index]->variants = $this->variants->get_variants($variants_filter);
            $products[$index]->variant = reset($products[$index]->variants);
            $products[$index]->badges = $this->badges->get_product_badges($product->id);
            $products[$index]->rating = $this->reviews->calc_product_rating($product->id);

            // Свойства товара
            $products[$index]->tags = $this->tags->get_product_tags($product->id);
            $products[$index]->tags_groups = array();
            foreach($products[$index]->tags as $tag)
            {
                if (!array_key_exists($tag->group_id, $products[$index]->tags_groups))
                    $products[$index]->tags_groups[$tag->group_id] = array();
                $products[$index]->tags_groups[$tag->group_id][] = $tag;
            }

            $products[$index]->reviews_count = $this->reviews->count_reviews(array('product_id'=>$product->id, 'is_visible'=>1, 'moderated'=>1));
        }*/

        $tags_groups = $this->tags->get_taggroups(array('is_auto'=>0, 'is_enabled'=>1));
        $this->design->assign('tags_groups', $tags_groups);

        $this->design->assign('params_arr', $this->params_arr);
        $pages_num = ceil($products_count/$items_per_page);
        $this->design->assign('total_pages_num', $pages_num);

        $this->design->assign('products', $products);
        $this->design->assign('data_type', 'category');

        if (array_key_exists('keyword', $this->params_arr))
            $title = "Поиск";
        else
            $title = isset($category)?$category->meta_title:'';
        if ($current_page > 1)
            $title .= ' - Страница '.$current_page;

        if (!empty($output_format) || $ajax)
        {
            $data = array('success'=>true, 'body_category_css'=>isset($category)?$category->css_class:'', 'data'=>'', 'category_path'=>isset($category_path)?$category_path:'', 'category_id'=>$category_id/*isset($this->params_arr['category_id'])?$this->params_arr['category_id']:''*/, 'meta_title'=>$title, 'meta_description'=>isset($category)?$category->meta_description:'', 'meta_keywords'=>isset($category)?$category->meta_keywords:'', 'products_count'=>$products_count);
            if ($products_count == 1)
            {
                $product_module = $this->furl->get_module_by_name('ProductController');
                $data['product_url'] = $this->config->root_url . $product_module->url . @reset($products)->url . $this->settings->postfix_product_url;
            }
            switch($output_format){
                case "add_to_history":
                    /* Занесем поисковый запрос в историю */
                    $keyword = $this->params_arr['keyword'];
                    if (mb_strlen($keyword, 'utf-8') > 2)
                    {
                        $categories_count = $this->settings->search_var_category_name ? $this->categories->count_categories_filter(array('is_visible'=>1, 'keyword'=>$this->params_arr['keyword'])) : 0;

                        $this->db->query("SELECT * FROM __search_history WHERE keyword='$keyword'");
                        $res = $this->db->result();
                        if ($res)
                            $this->db->query("UPDATE __search_history SET amount=?, last_updated=NOW(), products_count=?, categories_count=? WHERE id=?", $res->amount+1, $res->products_count+$products_count, $res->categories_count+$categories_count, $res->id);
                        else
                        {
                            $this->db->query("INSERT INTO __search_history(keyword,amount,last_updated,products_count,categories_count) VALUES(?,1,NOW(),?,?)", $keyword, $products_count, $categories_count);
                            $this->db->query("SELECT id FROM __search_history ORDER BY last_updated desc LIMIT 1000,1000");
                            $for_del = $this->db->results();
                            foreach($for_del as $d)
                                $this->db->query("DELETE FROM __search_history WHERE id=?", $d->id);
                        }
                    }
                    $data['data'] = 1;
                    break;
                case "search_ajax":
                    $categories = array();
                    if (array_key_exists('keyword', $this->params_arr))
                    {
                        $categories = $this->settings->search_var_category_name ? $this->categories->get_categories_filter(array('is_visible'=>1, 'keyword'=>$this->params_arr['keyword'])) : array();
                        foreach($categories as &$category)
                        {
                            $category->images = $this->image->get_images('categories', $category->id);
                            $category->image = reset($category->images);
                        }
                        $this->design->assign('categories', $categories);
                    }
                    $data['categories_count'] = count($categories);
                    $data['data'] = $this->design->fetch($this->design->getTemplateDir('frontend').'products-search-ajax.tpl');
                    break;
                case "search":
                    $keyword = array_key_exists('keyword', $this->params_arr) ? $this->params_arr['keyword'] : '';

                    //категории которые удовлетворяют поисковой фразе
                    $categories_filtered = $this->settings->search_var_category_name ? $this->categories->get_categories_filter(array('is_visible'=>1, 'keyword'=>$keyword)) : array();
                    foreach($categories_filtered as &$category)
                    {
                        $category->images = $this->image->get_images('categories', $category->id);
                        $category->image = reset($category->images);
                    }
                    $this->design->assign('categories_filtered', $categories_filtered);
                    $this->design->assign('categories_count', count($categories_filtered));

                    $data['data'] = $this->design->fetch($this->design->getTemplateDir('frontend').'search.tpl');
                    $products_module = $this->furl->get_module_by_name('ProductsController');
                    $data['products_categories'] = array();
                    $finded_categories = array();
                    //Запросим заново список товаров без category_id
                    if (array_key_exists('category_id', $filter))
                        unset($filter['category_id']);
                    unset($filter['page']);
                    $filter['limit'] = 10000;
                    $all_products_count = $this->products->count_products($filter);
                    $products_temp = $this->products->get_products($filter);

                    /*foreach($products as $index=>$product)
                    {
                        $cats = $this->categories->get_product_categories($product->id);
                        if (!$cats)
                            continue;
                        foreach($cats as $cat)
                        {
                            $cat_tmp = $this->categories->get_category($cat->category_id);
                            if (array_key_exists($cat_tmp->id, $finded_categories))
                                $finded_categories[$cat_tmp->id]['count'] += 1;
                            else
                                $finded_categories[$cat_tmp->id] = array('id'=>$cat_tmp->id,'url'=>$this->config->root_url.$products_module->url.'?format=search&keyword='.$keyword.'&category_id='.$cat_tmp->id,'name'=>$cat_tmp->name,'position'=>$cat_tmp->position,'count'=>1);
                        }
                    }*/

                    $finded_categories[0] = array('id'=>0, 'url'=>$this->config->root_url.$products_module->url.'?format=search'.(empty($keyword)?'':'&keyword='.$keyword),'name'=>'Все категории','position'=>0,'count'=>$all_products_count,'children'=>array());
                    foreach($products_temp as $index=>&$product)
                    {
                        $product->categories = $this->categories->get_product_categories($product->id);
                        if (!$product->categories)
                            continue;
                        foreach($product->categories as $cat)
                        {
                            $cat_tmp = $this->categories->get_category($cat->category_id);
                            if (!array_key_exists($cat_tmp->id, $finded_categories))
                                $finded_categories[$cat_tmp->id] = array('id'=>$cat_tmp->id,'url'=>$this->config->root_url.$products_module->url.'?format=search'.(empty($keyword)?'':'&keyword='.$keyword).'&category_id='.$cat_tmp->id,'name'=>$cat_tmp->name,'position'=>$cat_tmp->position,'count'=>0,'children'=>$cat_tmp->children);
                        }
                    }
                    foreach($products_temp as $index=>&$product)
                    {
                        if (!$product->categories)
                            continue;
                        foreach($product->categories as $cat)
                        {
                            foreach($finded_categories as &$fc)
                                if (in_array($cat->category_id, $fc['children']))
                                    $fc['count'] += 1;
                        }
                    }
                    //удалим массив подкатегорий, чтобы уменьшить трафик
                    foreach($finded_categories as &$fc)
                        unset($fc['children']);
                    foreach($finded_categories as $cat_id=>$cat)
                    {
                        if (!array_key_exists($cat['position'], $data['products_categories']))
                            $data['products_categories'][$cat['position']] = array();
                        $data['products_categories'][$cat['position']][] = $cat;
                    }
                    $this->design->assign('finded_categories', $data['products_categories']);
                    break;
                case "append":
                    $data['data'] = $this->design->fetch($this->design->getTemplateDir('frontend').'products-part.tpl');
                    break;
                case "":
                    $data['data'] = $this->design->fetch($this->design->getTemplateDir('frontend').'products.tpl');
                    $data['filter'] = $this->design->fetch($this->design->getTemplateDir('frontend').'filter.tpl');
                    break;
            }

            if ($ajax)
            {
                header("Content-type: application/json; charset=UTF-8");
                header("Cache-Control: must-revalidate");
                header("Pragma: no-cache");
                header("Expires: -1");
                print json_encode($data);
                die();
            }
        }

        /*if (!empty($category))
        {*/
            $this->design->assign('meta_title', $title);
            $this->design->assign('meta_description', isset($category)?$category->meta_description:$title);
            $this->design->assign('meta_keywords', isset($category)?$category->meta_keywords:$title);
        //}

        if (array_key_exists('keyword', $this->params_arr))
            return $this->design->fetch($this->design->getTemplateDir('frontend').'search.tpl');
        else
            return $this->design->fetch($this->design->getTemplateDir('frontend').'products.tpl');
    }
}