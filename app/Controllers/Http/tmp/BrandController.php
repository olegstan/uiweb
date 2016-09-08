<?php
namespace app\controllers;

use app\layer\LayerController;

class BrandController extends LayerController
{
    private $param_url, $params_arr, $options;

    public function set_params($url = null, $options = null)
    {
        $this->options = $options;

        $url = strip_tags($url);
        $url = htmlentities($url);

        echo "url=".$url;

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
        $brand = $this->brands->get_brand($this->param_url);
        if ($brand)
        {
            $brand_id = $brand->id;
            $this->design->assign('brand', $brand);
        }
        else
            return false;

        $ajax = false;
        $filter = array();
        $filter['brand_id'] = $brand->id;
        $filter['is_visible'] = 1;
        $filter['limit'] = 10000;
        if ($this->settings->catalog_show_all_products)
            $filter['in_stock'] = 1;
        $current_page = 1;

        foreach($this->params_arr as $p=>$v)
        {
            switch ($p)
            {
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
            $filter['sort'] = reset($sort_methods);//'position';
            $filter['sort_type'] = 'asc';
            $sort_type = $this->settings->catalog_default_sort;
            if (!in_array($sort_type, $sort_methods))
                $sort_type = reset($sort_methods);
            switch($sort_type){
                case 'position':
                    $filter['sort'] = 'position';
                    $filter['sort_type'] = 'asc';
                    break;
                case 'price_asc':
                    $filter['sort'] = $this->params_arr['sort'] = 'price';
                    $filter['sort_type'] = $this->params_arr['sort_type'] = 'asc';
                    break;
                case 'price_desc':
                    $filter['sort'] = $this->params_arr['sort'] = 'price';
                    $filter['sort_type'] = $this->params_arr['sort_type'] = 'desc';
                    break;
                case 'popular_asc':
                    $filter['sort'] = $this->params_arr['sort'] = 'popular';
                    $filter['sort_type'] = $this->params_arr['sort_type'] = 'asc';
                    break;
                case 'popular_desc':
                    $filter['sort'] = $this->params_arr['sort'] = 'popular';
                    $filter['sort_type'] = $this->params_arr['sort_type'] = 'desc';
                    break;
                case 'newest_asc':
                    $filter['sort'] = $this->params_arr['sort'] = 'newest';
                    $filter['sort_type'] = $this->params_arr['sort_type'] = 'asc';
                    break;
                case 'newest_desc':
                    $filter['sort'] = $this->params_arr['sort'] = 'newest';
                    $filter['sort_type'] = $this->params_arr['sort_type'] = 'desc';
                    break;
                case 'name_asc':
                    $filter['sort'] = $this->params_arr['sort'] = 'name';
                    $filter['sort_type'] = $this->params_arr['sort_type'] = 'asc';
                    break;
                case 'name_desc':
                    $filter['sort'] = $this->params_arr['sort'] = 'name';
                    $filter['sort_type'] = $this->params_arr['sort_type'] = 'desc';
                    break;
            }
        }
        else
            if (!array_key_exists('sort_type', $this->params_arr))
                $filter['sort_type'] = 'asc';

        /*Зададим формат вывода по умолчанию*/
        if (!array_key_exists('mode', $this->params_arr))
            $this->design->assign('mode', $this->settings->default_show_mode);
        else
            $this->design->assign('mode', $this->params_arr['mode']);

        $this->design->assign('current_url', $this->param_url.(empty($this->param_url)?'':'/'));
        $this->design->assign('current_params', $this->params_arr);
        // Если страница не задана, то равна 1
        if (is_numeric($current_page))
            $current_page = max(1, $current_page);
        $this->design->assign('current_page_num', $current_page);

        $products_count = $this->products->count_products($filter);

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
            $products[$index]->rating = $this->ratings->calc_product_rating($product->id);

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
        $this->design->assign('products_count', $products_count);
        $pages_num = ceil($products_count/$items_per_page);
        $this->design->assign('total_pages_num', $pages_num);

        $this->design->assign('data_type', 'brand');
        $this->design->assign('products', $products);

        $title = $brand->meta_title;
        if ($current_page > 1)
            $title .= ' - Страница '.$current_page;

        if ($ajax)
        {
            $data = array('success'=>true, 'body_brand_css'=>$brand->css_class, 'brand_id'=>$brand_id, 'meta_title'=>$title, 'meta_description'=>$brand->meta_description, 'meta_keywords'=>$brand->meta_keywords, 'data'=>$this->design->fetch($this->design->getTemplateDir('frontend').'brand.tpl'));

            header("Content-type: application/json; charset=UTF-8");
            header("Cache-Control: must-revalidate");
            header("Pragma: no-cache");
            header("Expires: -1");
            print json_encode($data);
            die();
        }

        $this->design->assign('meta_title', $title);
        $this->design->assign('meta_description', $brand->meta_description);
        $this->design->assign('meta_keywords', $brand->meta_keywords);

        return $this->design->fetch($this->design->getTemplateDir('frontend').'brand.tpl');
    }
}