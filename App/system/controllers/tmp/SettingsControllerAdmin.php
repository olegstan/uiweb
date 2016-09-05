<?php
namespace app\controllers;

use core\Controller;

class SettingsControllerAdmin extends Controller
{
    private $param_url, $params_arr, $options;
    private $allowed_image_extentions = array('png', 'gif', 'jpg', 'jpeg', 'ico');

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

        $this->db->query("SELECT id,keyword,amount,unix_timestamp(last_updated) as last_updated,products_count,categories_count FROM __search_history ORDER BY amount desc");
        $histories = $this->db->results();
        $this->design->assign('histories', $histories);

        $categories = $this->materials->get_categories_tree();
        $this->design->assign('categories', $categories);

        $this->design->assign('products_module', $this->furl->get_module_by_name('ProductsController'));

        if ($this->request->method('post'))
        {
            $mode = $this->request->post('mode');

            switch($mode){
                case "main":
                    $this->settings->site_name = $this->request->post('site_name');
                    $this->settings->company_name = $this->request->post('company_name');
                    $this->settings->date_format = $this->request->post('date_format');

                    $this->settings->order_email = $this->request->post('order_email');
                    $this->settings->template_email = $this->request->post('template_email');
                    $this->settings->comment_email = $this->request->post('comment_email');

                    $this->settings->yandex_metric_counter = $this->request->post('yandex_metric_counter');
                    $this->settings->counters_codes = $this->request->post('counters_codes');

                    $this->settings->properties_num_admin = $this->request->post('properties_num_admin');

                    /*
                    $this->settings->mainpage_badge_products_count = $this->request->post('mainpage_badge_products_count');*/
                    $session_lifetime = $this->request->post('session_lifetime');
                    if ($session_lifetime != $this->settings->session_lifetime)
                    {
                        $this->settings->session_lifetime = $session_lifetime;
                        @ini_set('session.gc_maxlifetime', $session_lifetime);
                        @ini_set('session.cookie_lifetime', $session_lifetime);
                    }
                    break;
                case "currencies":
                    $this->settings->decimals_point = $this->request->post('decimals_point');
                    $this->settings->thousands_separator = $this->request->post('thousands_separator');
                    $this->settings->catalog_show_currency = $this->request->post('catalog_show_currency', 'boolean');
                    break;
                case "catalog":
                    $this->settings->catalog_count_opened_level = $this->request->post('catalog_count_opened_level');
                    $this->settings->catalog_default_sort = $this->request->post('catalog_default_sort');
                    $this->settings->catalog_default_variants_sort = $this->request->post('catalog_default_variants_sort');
                    $this->settings->products_num = $this->request->post('products_num');
                    if (intval($this->settings->products_num) == 0)
                        $this->settings->products_num = 1;
                    $this->settings->units = $this->request->post('units');
                    $this->settings->products_num_admin = $this->request->post('products_num_admin');
                    if (intval($this->settings->products_num_admin) == 0)
                        $this->settings->products_num_admin = 1;
                    $this->settings->categories_num_admin = $this->request->post('categories_num_admin');
                    if (intval($this->settings->categories_num_admin) == 0)
                        $this->settings->categories_num_admin = 1;
                    $this->settings->brands_num_admin = $this->request->post('brands_num_admin');
                    if (intval($this->settings->brands_num_admin) == 0)
                        $this->settings->brands_num_admin = 1;
                    $this->settings->default_related_products_mode = $this->request->post('default_related_products_mode');
                    $this->settings->default_show_mode = $this->request->post('default_show_mode');
                    $this->settings->use_product_id = $this->request->post('use_product_id', 'boolean');

                    $this->settings->catalog_hide_nostock_variants = $this->request->post('catalog_hide_nostock_variants', 'boolean');

                    $this->settings->catalog_show_multi_buy = $this->request->post('catalog_show_multi_buy', 'boolean');

                    $this->settings->catalog_show_product_id = $this->request->post('catalog_show_product_id', 'boolean');
                    $this->settings->catalog_show_sku = $this->request->post('catalog_show_sku', 'boolean');
                    $this->settings->catalog_show_category_id = $this->request->post('catalog_show_category_id', 'boolean');

                    $this->settings->new_status_when_product_is_out_of_stock = $this->request->post('new_status_when_product_is_out_of_stock');

                    $this->settings->catalog_show_all_products = $this->request->post('catalog_show_all_products');

                    $this->settings->catalog_use_smart_sort = $this->request->post('catalog_use_smart_sort', 'boolean');

                    $this->settings->settings_sort_position = $this->request->post('settings_sort_position', 'boolean');
                    $this->settings->settings_sort_price = $this->request->post('settings_sort_price', 'boolean');
                    $this->settings->settings_sort_newest = $this->request->post('settings_sort_newest', 'boolean');
                    $this->settings->settings_sort_popular = $this->request->post('settings_sort_popular', 'boolean');
                    $this->settings->settings_sort_name = $this->request->post('settings_sort_name', 'boolean');

                    $this->settings->catalog_show_subcategories = $this->request->post('catalog_show_subcategories', 'boolean');

                    if (!$this->settings->settings_sort_position)
                        $this->db->query("UPDATE __categories SET sort_type='' WHERE sort_type='position'");
                    if (!$this->settings->settings_sort_price)
                        $this->db->query("UPDATE __categories SET sort_type='' WHERE sort_type='price_asc' || sort_type='price_desc'");
                    if (!$this->settings->settings_sort_newest)
                        $this->db->query("UPDATE __categories SET sort_type='' WHERE sort_type='newest_asc' || sort_type='newest_desc'");
                    if (!$this->settings->settings_sort_popular)
                        $this->db->query("UPDATE __categories SET sort_type='' WHERE sort_type='popular_asc' || sort_type='popular_desc'");
                    if (!$this->settings->settings_sort_name)
                        $this->db->query("UPDATE __categories SET sort_type='' WHERE sort_type='name_asc' || sort_type='name_desc'");

                    $this->settings->catalog_use_variable_amount = $this->request->post('catalog_use_variable_amount', 'boolean');
                    $this->settings->catalog_min_amount = $this->request->post('catalog_min_amount');
                    $this->settings->catalog_max_amount = $this->request->post('catalog_max_amount');
                    $this->settings->catalog_step_amount = $this->request->post('catalog_step_amount');
                    $this->settings->catalog_variable_amount_name = $this->request->post('catalog_variable_amount_name');
                    $this->settings->catalog_variable_amount_dimension = $this->request->post('catalog_variable_amount_dimension');

                    $this->settings->add_field1_name = $this->request->post('add_field1_name');
                    $this->settings->add_field2_name = $this->request->post('add_field2_name');
                    $this->settings->add_field3_name = $this->request->post('add_field3_name');
                    $this->settings->add_field1_show_frontend = $this->request->post('add_field1_show_frontend', 'boolean');
                    $this->settings->add_field2_show_frontend = $this->request->post('add_field2_show_frontend', 'boolean');
                    $this->settings->add_field3_show_frontend = $this->request->post('add_field3_show_frontend', 'boolean');
                    $this->settings->add_field1_is_enabled = $this->request->post('add_field1_is_enabled', 'boolean');
                    $this->settings->add_field2_is_enabled = $this->request->post('add_field2_is_enabled', 'boolean');
                    $this->settings->add_field3_is_enabled = $this->request->post('add_field3_is_enabled', 'boolean');

                    $this->settings->add_flag1_name = $this->request->post('add_flag1_name');
                    $this->settings->add_flag2_name = $this->request->post('add_flag2_name');
                    $this->settings->add_flag3_name = $this->request->post('add_flag3_name');
                    $this->settings->add_flag1_show_frontend = $this->request->post('add_flag1_show_frontend', 'boolean');
                    $this->settings->add_flag2_show_frontend = $this->request->post('add_flag2_show_frontend', 'boolean');
                    $this->settings->add_flag3_show_frontend = $this->request->post('add_flag3_show_frontend', 'boolean');
                    $this->settings->add_flag1_is_enabled = $this->request->post('add_flag1_is_enabled', 'boolean');
                    $this->settings->add_flag2_is_enabled = $this->request->post('add_flag2_is_enabled', 'boolean');
                    $this->settings->add_flag3_is_enabled = $this->request->post('add_flag3_is_enabled', 'boolean');
                    $this->settings->add_flag1_default_value = $this->request->post('add_flag1_default_value', 'boolean');
                    $this->settings->add_flag2_default_value = $this->request->post('add_flag2_default_value', 'boolean');
                    $this->settings->add_flag3_default_value = $this->request->post('add_flag3_default_value', 'boolean');
                    break;
                case "cart":
                    $this->settings->cart_fio_required = $this->request->post('cart_fio_required');
                    $this->settings->cart_phone_required = $this->request->post('cart_phone_required');
                    $this->settings->cart_email_required = $this->request->post('cart_email_required');
                    $this->settings->cart_address_required = $this->request->post('cart_address_required');
                    $cart_order_min_price = $this->request->post('cart_order_min_price');
                    if (empty($cart_order_min_price))
                        $this->settings->cart_order_min_price = "0";
                    else
                        $this->settings->cart_order_min_price = $cart_order_min_price;
                    $this->settings->cart_fio_required = $this->request->post('cart_fio_required');
                    $this->settings->max_order_amount = $this->request->post('max_order_amount');
                    $this->settings->cart_one_click_show_comment = $this->request->post('cart_one_click_show_comment', 'boolean');
                    $this->settings->cart_show_product_id_in_email = $this->request->post('cart_show_product_id_in_email', 'boolean');
                    break;
                case "breadcrumbs":
                    $this->settings->breadcrumbs_open_tag = $this->request->post('breadcrumbs_open_tag');
                    $this->settings->breadcrumbs_close_tag = $this->request->post('breadcrumbs_close_tag');
                    $this->settings->breadcrumbs_first_element = $this->request->post('breadcrumbs_first_element');
                    $this->settings->breadcrumbs_element_open_tag = $this->request->post('breadcrumbs_element_open_tag');
                    $this->settings->breadcrumbs_element_close_tag = $this->request->post('breadcrumbs_element_close_tag');
                    $this->settings->breadcrumbs_selected_element_open_tag = $this->request->post('breadcrumbs_selected_element_open_tag');
                    $this->settings->breadcrumbs_selected_element_close_tag = $this->request->post('breadcrumbs_selected_element_close_tag');
                    $this->settings->breadcrumbs_element_separator = $this->request->post('breadcrumbs_element_separator');
                    break;
                case "delete-watermark-file":
                    @unlink($this->config->root_dir.$this->config->watermark_file);
                    $this->image->clear_cache();
                    header("Content-type: application/json; charset=UTF-8");
                    header("Cache-Control: must-revalidate");
                    header("Pragma: no-cache");
                    header("Expires: -1");
                    print json_encode(1);
                    die();
                    break;
                case "watermarks":
                    $clear_image_cache = false;

                    if ($this->settings->watermark_is_enabled != $this->request->post('watermark_is_enabled', 'boolean'))
                    {
                        $this->settings->watermark_is_enabled = $this->request->post('watermark_is_enabled', 'boolean');
                        $clear_image_cache = true;
                    }
                    /*if ($this->settings->watermark_products_is_enabled != $this->request->post('watermark_products_is_enabled', 'boolean'))
                    {
                        $this->settings->watermark_products_is_enabled = $this->request->post('watermark_products_is_enabled', 'boolean');
                        $clear_image_cache = true;
                    }
                    if ($this->settings->watermark_materials_is_enabled != $this->request->post('watermark_materials_is_enabled', 'boolean'))
                    {
                        $this->settings->watermark_materials_is_enabled = $this->request->post('watermark_materials_is_enabled', 'boolean');
                        $clear_image_cache = true;
                    }*/
                    if ($this->settings->watermark_reviews_is_enabled != $this->request->post('watermark_reviews_is_enabled', 'boolean'))
                    {
                        $this->settings->watermark_reviews_is_enabled = $this->request->post('watermark_reviews_is_enabled', 'boolean');
                        $clear_image_cache = true;
                    }
                    if ($this->settings->watermark_offset_x != $this->request->post('watermark_offset_x'))
                    {
                        if (0 <= intval($this->request->post('watermark_offset_x')) && intval($this->request->post('watermark_offset_x')) <=100)
                        {
                            $this->settings->watermark_offset_x = intval($this->request->post('watermark_offset_x'));
                            $clear_image_cache = true;
                        }
                    }
                    if ($this->settings->watermark_offset_y != $this->request->post('watermark_offset_y'))
                    {
                        if (0 <= intval($this->request->post('watermark_offset_y')) && intval($this->request->post('watermark_offset_y')) <=100)
                        {
                            $this->settings->watermark_offset_y = intval($this->request->post('watermark_offset_y'));
                            $clear_image_cache = true;
                        }
                    }
                    // if ($this->settings->watermark_transparency != $this->request->post('watermark_transparency'))
                    // {
                        // if (0 <= intval($this->request->post('watermark_transparency')) && intval($this->request->post('watermark_transparency')) <=100)
                        // {
                            // $this->settings->watermark_transparency = $this->request->post('watermark_transparency');
                            // $clear_image_cache = true;
                        // }watermark_reviews_is_enabled
                    // }
                    if ($this->settings->watermark_image_min_width != $this->request->post('watermark_image_min_width'))
                    {
                        if (0 <= intval($this->request->post('watermark_image_min_width')) && intval($this->request->post('watermark_image_min_width')) <=2000)
                        {
                            $this->settings->watermark_image_min_width = $this->request->post('watermark_image_min_width');
                            $clear_image_cache = true;
                        }
                    }
                    if ($this->settings->watermark_image_min_height != $this->request->post('watermark_image_min_height'))
                    {
                        if (0 <= intval($this->request->post('watermark_image_min_height')) && intval($this->request->post('watermark_image_min_height')) <=2000)
                        {
                            $this->settings->watermark_image_min_height = $this->request->post('watermark_image_min_height');
                            $clear_image_cache = true;
                        }
                    }

                    $watermark_file = @reset($this->request->files('watermark_file'));
                    if(!empty($watermark_file) && in_array(pathinfo($watermark_file['name'], PATHINFO_EXTENSION), $this->allowed_image_extentions))
                    {
                        if(@move_uploaded_file($watermark_file['tmp_name'], $this->config->root_dir.$this->config->watermark_file))
                        {
                            $this->design->assign('message_success', 'Водяной знак загружен');
                            $clear_image_cache = true;
                        }
                        else
                            $this->design->assign('message_error', 'Ошибка записи');
                    }

                    if ($clear_image_cache)
                        $this->image->clear_cache();

                    break;
                case "search":
                    $this->settings->search_var_category_name = $this->request->post('search_var_category_name', 'boolean');
                    $this->settings->search_placeholder = $this->request->post('search_placeholder');
                    $this->settings->search_help_text1 = $this->request->post('search_help_text1');
                    $this->settings->search_help_text2 = $this->request->post('search_help_text2');
                    $this->settings->search_ajax_show_sku = $this->request->post('search_ajax_show_sku', 'boolean');
                    $this->settings->search_ajax_show_product_id = $this->request->post('search_ajax_show_product_id', 'boolean');
                    $this->settings->search_min_lenght = $this->request->post('search_min_lenght');
                    break;
                case "news":
                    $this->settings->news_category_id = $this->request->post('news_category_id');
                    $this->settings->news_show_count = $this->request->post('news_show_count');
                    break;
                case "viewed-products":
                    $this->settings->count_history_products = $this->request->post('count_history_products');
                    break;
                case "404":
                    $this->settings->page404_header = $this->request->post('page404_header');
                    $this->settings->page404_meta_title = $this->request->post('page404_meta_title');
                    $this->settings->page404_text = $this->request->post('page404_text');
                    $this->settings->page404_meta_keywords = $this->request->post('page404_meta_keywords');
                    $this->settings->page404_meta_description = $this->request->post('page404_meta_description');
                    break;
                case "yandex-market":
                    $this->settings->yandex_export_all_product = $this->request->post('yandex_export_all_product', 'boolean');
                    $this->settings->yandex_export_add_tag_adult = $this->request->post('yandex_export_add_tag_adult', 'boolean');
                    break;
                case "clear-cache":
                    $clear_cache = $this->request->post('clear-image-cache', 'boolean');
                    $recreate_auto_properties = $this->request->post('recreate-auto-properties', 'boolean');
                    $clear_search_history = $this->request->post('clear-search-history', 'boolean');

                    if ($clear_cache)
                    {
                        $this->image->clear_cache();
                        $this->design->assign('message_success_ex', 'Кеш изображений очищен!');
                    }
                    if ($recreate_auto_properties)
                    {
                        $this->tags->recreate_auto_properties();
                        $this->design->assign('message_succlear_cachecess_ex', 'Пересоздание автосвойств выполнено!');
                    }
                    if ($clear_search_history)
                    {
                        $this->db->query("DELETE FROM __search_history");
                        $this->design->assign('message_success_ex', 'История поиска очищена!');
                    }

                    break;
                case "reviews-products":
                    $this->settings->reviews_is_enabled = $this->request->post('reviews_is_enabled', 'boolean');
                    $this->settings->reviews_premoderate = $this->request->post('reviews_premoderate', 'boolean');
                    $this->settings->reviews_short_is_visible = $this->request->post('reviews_short_is_visible', 'boolean');
                    $this->settings->reviews_pluses_is_visible = $this->request->post('reviews_pluses_is_visible', 'boolean');
                    $this->settings->reviews_minuses_is_visible = $this->request->post('reviews_minuses_is_visible', 'boolean');
                    $this->settings->reviews_recommended_is_visible = $this->request->post('reviews_recommended_is_visible', 'boolean');
                    $this->settings->reviews_images_is_visible = $this->request->post('reviews_images_is_visible', 'boolean');

                    $this->settings->reviews_num = $this->request->post('reviews_num');
                    $this->settings->reviews_claim_count_for_disable = $this->request->post('reviews_claim_count_for_disable');
                    $this->settings->reviews_images_num = $this->request->post('reviews_images_num');
                    $this->settings->reviews_images_max_width = $this->request->post('reviews_images_max_width');
                    $this->settings->reviews_images_max_height = $this->request->post('reviews_images_max_height');

                    $this->settings->reviews_default_sort = $this->request->post('reviews_default_sort');

                    $old_koef_newest = $this->settings->koef_newest;
                    $old_koef_popular = $this->settings->koef_popular;
                    $old_koef_fill = $this->settings->koef_fill;
                    $old_koef_claim = $this->settings->koef_claim;

                    $this->settings->koef_newest = $this->request->post('koef_newest');
                    $this->settings->koef_popular = $this->request->post('koef_popular');
                    $this->settings->koef_fill = $this->request->post('koef_fill');
                    $this->settings->koef_claim = $this->request->post('koef_claim');

                    if ($old_koef_newest != $this->settings->koef_newest ||
                        $old_koef_popular != $this->settings->koef_popular ||
                        $old_koef_fill != $this->settings->koef_fill ||
                        $old_koef_claim != $this->settings->koef_claim)
                    {
                        //Пересчет рангов товаров
                        $this->db->query("SELECT id FROM __reviews ORDER BY id");
                        $reviews_ids = $this->db->results('id');
                        foreach($reviews_ids as $r_id)
                            $this->reviews->update_review_rank($r_id);
                    }
                    break;
                case "licence":
                    $this->settings->licence_code = $this->request->post('licence_code');
                    break;
            }

            $this->design->assign('mode', $mode);
            $this->design->assign('message_success', 'saved');
        }
        else
            if (!empty($this->param_url))
            {
                $str = trim($this->param_url, '/');
                $params = explode('/', $str);

                switch (count($params))
                {
                    case 1:
                        if ($params[0] == "history")
                        {
                            $response = array('success'=>true, 'data'=> array());

                            $this->db->query("SELECT * FROM __search_history ORDER BY id");
                            $results = $this->db->results();
                            foreach($results as $r)
                                $response['data'][] = array('id'=>$r->id, 'keyword'=>$r->keyword, 'amount'=>$r->amount, 'last_date'=>$r->last_updated, 'count'=>$r->products_count+$r->categories_count);

                            header("Content-type: application/json; charset=UTF-8");
                            header("Cache-Control: must-revalidate");
                            header("Pragma: no-cache");
                            header("Expires: -1");
                            print json_encode($response);
                            die();
                        }
                        break;
                    case 2:
                        if ($params[0] == "get")
                        {
                            $template = $params[1];

                            if ($params[1] == "watermarks")
                            {
                                $watermark_images = $this->image->get_images('image', 1);
                                $watermark_image = @reset($watermark_images);
                                $this->design->assign('watermark_image', $watermark_image);
                                if (is_file($this->config->root_dir . $this->config->watermark_file))
                                    $this->design->assign('watermark_exists', true);
                            }

                            if ($params[1] == "yandex-market")
                            {
                                $categories_count = $this->categories->count_categories_filter(array('is_visible'=>1));
                                $this->design->assign('categories_count', $categories_count);

                                $stock_filter = "";
                                if (!$this->settings->yandex_export_all_product)
                                    $stock_filter = "AND (v.stock > 0 OR v.stock is NULL)";
                                $this->db->query("SELECT COUNT(distinct v.id) as variants_count
                                    FROM __variants v
                                        INNER JOIN __products p ON v.product_id = p.id
                                        LEFT JOIN __products_categories pc ON p.id = pc.product_id AND pc.position=(SELECT MIN(position) FROM __products_categories WHERE product_id=p.id LIMIT 1)
                                        LEFT JOIN __categories c ON pc.category_id=c.id
                                        LEFT JOIN __brands b ON p.brand_id = b.id
                                    WHERE v.is_visible and p.is_visible and c.is_visible and v.price>0 $stock_filter");
                                $this->design->assign('variants_count', $this->db->result('variants_count'));

                                $this->db->query("SELECT COUNT(distinct p.id) as products_count
                                    FROM __variants v
                                        INNER JOIN __products p ON v.product_id = p.id
                                        LEFT JOIN __products_categories pc ON p.id = pc.product_id AND pc.position=(SELECT MIN(position) FROM __products_categories WHERE product_id=p.id LIMIT 1)
                                        LEFT JOIN __categories c ON pc.category_id=c.id
                                        LEFT JOIN __brands b ON p.brand_id = b.id
                                    WHERE v.is_visible and p.is_visible and c.is_visible and v.price>0 $stock_filter");
                                $this->design->assign('products_count', $this->db->result('products_count'));
                            }

                            $response = array('success'=>true, 'data'=>$this->design->fetch($this->design->getTemplateDir('admin').'settings/'.$params[1].'.tpl'));
                            header("Content-type: application/json; charset=UTF-8");
                            header("Cache-Control: must-revalidate");
                            header("Pragma: no-cache");
                            header("Expires: -1");
                            print json_encode($response);
                            die();
                        }
                        if ($params[0] == "mode")
                        {
                            $this->design->assign('mode', $params[1]);

                            if ($params[1] == "yandex-market")
                            {
                                $categories_count = $this->categories->count_categories_filter(array('is_visible'=>1));
                                $this->design->assign('categories_count', $categories_count);

                                $stock_filter = "";
                                if (!$this->settings->yandex_export_all_product)
                                    $stock_filter = "AND (v.stock > 0 OR v.stock is NULL)";
                                $this->db->query("SELECT COUNT(distinct v.id) as variants_count
                                    FROM __variants v
                                        INNER JOIN __products p ON v.product_id = p.id
                                        LEFT JOIN __products_categories pc ON p.id = pc.product_id AND pc.position=(SELECT MIN(position) FROM __products_categories WHERE product_id=p.id LIMIT 1)
                                        LEFT JOIN __categories c ON pc.category_id=c.id
                                        LEFT JOIN __brands b ON p.brand_id = b.id
                                    WHERE v.is_visible and p.is_visible and c.is_visible and v.price>0 $stock_filter");
                                $this->design->assign('variants_count', $this->db->result('variants_count'));

                                $this->db->query("SELECT COUNT(distinct p.id) as products_count
                                    FROM __variants v
                                        INNER JOIN __products p ON v.product_id = p.id
                                        LEFT JOIN __products_categories pc ON p.id = pc.product_id AND pc.position=(SELECT MIN(position) FROM __products_categories WHERE product_id=p.id LIMIT 1)
                                        LEFT JOIN __categories c ON pc.category_id=c.id
                                        LEFT JOIN __brands b ON p.brand_id = b.id
                                    WHERE v.is_visible and p.is_visible and c.is_visible and v.price>0 $stock_filter");
                                $this->design->assign('products_count', $this->db->result('products_count'));
                            }
                        }
                        break;
                }
            }

        $watermark_images = $this->image->get_images('image', 1);
        $watermark_image = @reset($watermark_images);
        $this->design->assign('watermark_image', $watermark_image);
        if (is_file($this->config->root_dir . $this->config->watermark_file))
            $this->design->assign('watermark_exists', true);



        if($this->page)
        {
            $this->design->assign('meta_title', $this->page->meta_title);
            $this->design->assign('meta_keywords', $this->page->meta_keywords);
            $this->design->assign('meta_description', $this->page->meta_description);
        }

        return $this->design->fetch($this->design->getTemplateDir('admin').'settings.tpl');
    }
}