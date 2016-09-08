<?php
namespace app\controllers;

use core\Controller;

class ProductsControllerAdmin extends Controller
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
        $menu[$parent_id][] = intval($item->id);
        if (isset($item->children))
            foreach($item->children as $i)
                $this->process_menu($i, intval($item->id), $menu);
    }

    private function process_category($item, $level, &$categories_list){
        $categories_list[] = array('id'=>$item->id, 'level'=>str_repeat("&nbsp;&nbsp;", $level), 'name'=>$item->name);
        if (isset($item->subcategories))
            foreach($item->subcategories as $subitem)
                $this->process_category($subitem, $level+1, $categories_list);
    }


    private function make_categories_lazy_tree(&$tree, $category_path){
        $next_id = @reset($category_path);
        foreach($tree as &$t)
            if ($t['id'] == $next_id)
            {
                $t['subcategories'] = $this->categories->get_categories_lazy_load_filter(array('parent_id'=>$next_id));
                if (count($category_path) > 1)
                {
                    array_shift($category_path);
                    $this->make_categories_lazy_tree($t['subcategories'], $category_path);
                }
            }
    }

    private function recursive_categories_tree(&$tree, $steps){
        if ($steps <= 0)
            return;

        foreach($tree as &$tree_item)
        {
            $tree_item['subcategories'] = $this->categories->get_categories_lazy_load_filter(array('parent_id'=>$tree_item['id']));
            $this->recursive_categories_tree($tree_item['subcategories'], $steps-1);
        }
    }

    function fetch()
    {
        if (!(isset($_SESSION['admin']) && $_SESSION['admin']=='admin'))
            header("Location: http://".$_SERVER['SERVER_NAME']."/admin/login/?return_url=xxx");

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
                    $tree = $this->categories->get_categories_tree();
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
                    $menu_items = json_decode($this->request->post('menu'));
                    $stocks = json_decode($this->request->post('stocks'));
                    $prices = json_decode($this->request->post('prices'));

                    $menu = array();
                    foreach($menu_items as $item)
                        $this->process_menu($item, 0, $menu);
                    foreach($menu as $parent_id=>$items)
                        foreach($items as $position=>$id)
                            $this->products->update_product($id, array('position'=>$position));

                    //Проставим автотеги цены и наличия
                    $this->db->query("SELECT id FROM __tags_groups WHERE name=? AND is_auto=?", "Цена", 1);
                    $price_group_id = $this->db->result('id');
                    $this->db->query("SELECT id FROM __tags_groups WHERE name=? AND is_auto=?", "Есть в наличии", 1);
                    $stock_group_id = $this->db->result('id');

                    for($idx=0 ; $idx<count($stocks) ; $idx++)
                    {
                        $v = $this->variants->get_variant($stocks[$idx]->id);
                        $p = $this->products->get_product($v->product_id);
                        //УДАЛИМ ВСЕ АВТОТЕГИ ТОВАРА
                        $this->db->query("SELECT tp.tag_id
                                            FROM __tags_products tp
                                            INNER JOIN __tags t ON tp.tag_id = t.id
                                        WHERE tp.product_id=? AND t.is_auto=1 AND t.group_id in (?,?)", $v->product_id, $price_group_id, $stock_group_id);
                        $autotags_ids = $this->db->results('tag_id');
                        if (!empty($autotags_ids))
                            $this->db->query("DELETE FROM __tags_products WHERE product_id=? AND tag_id IN (?@)", $v->product_id, $autotags_ids);

                        $stock = $stocks[$idx]->value;
                        if($stock == '∞' || $stock == '')
                            $stock = null;
                        $this->variants->update_variant($stocks[$idx]->id, array('stock'=>$stock));

                        $this->variants->update_variant($stocks[$idx]->id, array('price'=>$prices[$idx]->value));

                        //Price tag
                        $tag_id = 0;
                        if ($tag = $this->tags->get_tags(array('group_id'=>$price_group_id,'name'=>$this->currencies->convert($v->price, empty($p->currency_id) ? $this->admin_currency->id : $p->currency_id, false))))
                        {
                            $tag_id = intval(reset($tag)->id);
                        }
                        else
                        {
                            $tag = new StdClass;
                            $tag->group_id = $price_group_id;
                            $tag->name = $this->currencies->convert($v->price, empty($p->currency_id) ? $this->admin_currency->id : $p->currency_id, false);
                            $tag->is_enabled = 1;
                            $tag->is_auto = 1;
                            $tag->id = $this->tags->add_tag($tag);

                            $tag_id = $tag->id;
                        }
                        $this->tags->add_product_tag($p->id, $tag_id);

                        //Stock tag
                        if ($stock > 0 || $stock == null)
                            $in_stock_text = "да";
                        else
                            if ($stock < 0)
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
                        $this->tags->add_product_tag($p->id, $tag_id);
                    }

                    /*foreach($stocks as $s)
                    {
                        $stock = $s['value'];
                        if($stock == '∞' || $stock == '')
                            $stock = null;
                        $this->variants->update_variant($s['id'], array('stock'=>$stock));
                    }

                    foreach($prices as $p)
                        $this->variants->update_variant($p['id'], array('price'=>$p['value']));*/

                    /*header("Content-type: application/json; charset=UTF-8");
                    header("Cache-Control: must-revalidate");
                    header("Pragma: no-cache");
                    header("Expires: -1");
                    print json_encode(1);
                    die();*/

                    unset($this->params_arr['save_positions']);

                    $m = $this->furl->get_module_by_name('ProductsControllerAdmin');

                    $url = $this->config->root_url . $m->url . $this->design->url_modifier(array('current_params' => $this->params_arr));
                    header("Location: $url");
                    die();
                    break;
                case "add_brand":
                    $p_str = $this->params_arr[$p];
                    $p_arr = explode(',', $p_str);
                    if (count($p_arr) == 2)
                    {
                        $product_id = intval($p_arr[0]);
                        $brand_id = intval($p_arr[1]);

                        //AUTOTAG
                        $product = $this->products->get_product($product_id);
                        $product_brand = $this->brands->get_brand($product->brand_id);
                        if ($product_brand && $product_brand->tag_id)
                            $this->tags->delete_product_tag($product_id, $product_brand->tag_id);
                        if ($brand_id > 0)
                        {
                            $product_brand = $this->brands->get_brand($brand_id);
                            if ($product_brand && $product_brand->tag_id > 0)
                                $this->tags->add_product_tag($product_id, $product_brand->tag_id);
                        }
                        //AUTOTAG (END)

                        if (!empty($product_id) && !empty($brand_id))
                            $this->products->update_product($product_id, array('brand_id'=>$brand_id));
                        die('1');
                    }
                    else
                        die('0');
                    break;
                case "remove_brand":
                    $product_id = intval($this->params_arr[$p]);
                    if (!empty($product_id))
                    {
                        //AUTOTAG
                        $product = $this->products->get_product($product_id);
                        $product_brand = $this->brands->get_brand($product->brand_id);
                        if ($product_brand && $product_brand->tag_id)
                            $this->tags->delete_product_tag($product_id, $product_brand->tag_id);

                        $this->products->update_product($product_id, array('brand_id'=>null));
                        die('1');
                    }
                    else
                        die('0');
                    break;
                case "toggle_brands_auto_open":
                    $this->settings->brands_auto_open = 1 - $this->settings->brands_auto_open;
                    die('1');
                    break;
                case "keyword":
                    if (!empty($v))
                    {
                        $filter[$p] = $v;
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
                    if (!empty($v))
                    {
                        $category = $this->categories->get_category(intval($v));
                        $filter[$p] = $category->children;
                        $this->design->assign('category_id', $this->params_arr[$p]);
                        //Составим путь выборки категории, чтоб подгрузить ее во фронтенде
                        $category_path = array();
                        $tmp_cat = $category;
                        while($tmp_cat->parent_id != 0)
                        {
                            $tmp_cat = $this->categories->get_category(intval($tmp_cat->parent_id));
                            $category_path[] = $tmp_cat->id;
                        }

                        //Подготовим статическое меню для админки
                        $categories_admin = $this->categories->get_categories_lazy_load_filter(array('parent_id'=>0));
                        $this->make_categories_lazy_tree($categories_admin, array_merge($category_path, array($category->id)));
                        $this->design->assign('categories_admin', $categories_admin);

                        $category_path = join(",",array_reverse($category_path));
                        $this->design->assign('category_path', $category_path);
                    }
                    else
                        unset($this->params_arr[$p]);
                    break;
                case "brand_id":
                    if (!empty($this->params_arr[$p]))
                    {
                        $filter[$p] = explode(",",$this->params_arr[$p]);
                        $this->design->assign('brand_ids', $filter[$p]);
                    }
                    else
                        unset($this->params_arr[$p]);
                    break;
                case "ajax":
                    $ajax = intval($this->params_arr[$p]);
                    unset($this->params_arr[$p]);
                    break;
                case "format":
                    $filter['limit'] = 100;
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
            /*$this->params_arr['sort'] = 'position';
            $this->params_arr['sort_type'] = 'asc';*/
            $filter['sort'] = 'position';
            $filter['sort_type'] = 'asc';
            $sort_type = isset($category)?$category->sort_type:'';
            if (empty($sort_type))
                $sort_type = $this->settings->catalog_default_sort;
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
            {
                /*$this->params_arr['sort_type'] = 'asc';*/
                $filter['sort_type'] = 'asc';
            }

        $this->design->assign('current_params', $this->params_arr);

        // Если страница не задана, то равна 1
        $current_page = max(1, $current_page);
        $this->design->assign('current_page_num', $current_page);

        $all_products_count = $this->products->count_products();
        $products_count = $this->products->count_products($filter);

        // Постраничная навигация
        if (array_key_exists('page', $this->params_arr) && $this->params_arr['page'] == 'all')
            $items_per_page = $products_count;
        else
            $items_per_page = $this->settings->products_num_admin;

        $filter['page'] = $current_page;
        $filter['limit'] = $items_per_page;

        $products = $this->products->get_products($filter);

        foreach($products as $index=>$product)
        {
            $products[$index]->images = $this->image->get_images('products', $product->id);
            $products[$index]->image = reset($products[$index]->images);
            $products[$index]->variants = $this->variants->get_variants(array('product_id'=>$product->id));
            $products[$index]->variant = reset($products[$index]->variants);
        }

        $this->design->assign('params_arr', $this->params_arr);
        $this->design->assign('all_products_count', $all_products_count);
        $this->design->assign('products_count', $products_count);
        $pages_num = $items_per_page>0 ? ceil($products_count/$items_per_page): 0;
        $this->design->assign('total_pages_num', $pages_num);

        $this->design->assign('products', $products);
        $this->design->assign('edit_module', $this->furl->get_module_by_name('ProductControllerAdmin'));
        $this->design->assign('categories_module', $this->furl->get_module_by_name('CategoriesControllerAdmin'));

        $this->design->assign('categories_count', $this->categories->count_categories_filter());

        if (!array_key_exists('category_id', $this->params_arr))
        {
            //Выбираем 1-й уровень дерева категорий
            $categories_admin = $this->categories->get_categories_lazy_load_filter(array('parent_id'=>0));
            //$this->recursive_categories_tree($categories_admin, $this->settings->catalog_count_opened_level);
            $this->design->assign('categories_admin', $categories_admin);
        }

        if ($ajax)
        {
            switch($output_format){
                case "related":
                    $data = array();
                    foreach($products as $product)
                        $data[] = array('name'=>$product->name, 'id'=>$product->id, 'image'=>empty($product->image)?"":$this->design->resize_modifier($product->image->filename, 'products', 40, 40));
                    break;
                case "product-addrelated":
                    $data = $this->design->fetch($this->design->getTemplateDir('admin').'product-addrelated.tpl');
                    break;
                case "select2":
                    $data = array('success'=>true, 'data'=>array());
                    foreach($products as $p)
                        $data['data'][] = array('id'=>$p->id, 'text'=>$p->name);
                    break;
                case "":
                    $data['products'] = $this->design->fetch($this->design->getTemplateDir('admin').'products-refreshpart.tpl');
                    $data['brands'] = array();
                    if (isset($category) && !empty($category))
                        $brands = $this->brands->get_brands(array('is_visible'=>1, 'sort'=>'name', 'sort_type'=>'asc', 'category_id'=>$category->children));
                    else
                        $brands = $this->brands->get_brands(array('is_visible'=>1, 'sort'=>'name', 'sort_type'=>'asc'));
                    $brands_ids = array();
                    if (array_key_exists('brand_id', $this->params_arr))
                        $brands_ids = explode(",",$this->params_arr['brand_id']);
                    foreach($brands as $b)
                    {
                        $b->products_count = $this->products->count_products(array('brand_id'=>$b->id));
                        if ($b->products_count > 0)
                            $data['brands'][] = array('id'=>$b->id, 'name'=>$b->name, 'selected'=>in_array($b->id, $brands_ids));
                    }
                    break;
            }

            header("Content-type: application/json; charset=UTF-8");
            header("Cache-Control: must-revalidate");
            header("Pragma: no-cache");
            header("Expires: -1");
            print json_encode($data);
            die();
        }

        $all_brands = $this->brands->get_brands(array('is_visible'=>1, 'sort'=>'name', 'sort_type'=>'asc'));
        $this->design->assign('all_brands', $all_brands);

        if (isset($category) && !empty($category))
            $brands = $this->brands->get_brands(array('is_visible'=>1, 'sort'=>'name', 'sort_type'=>'asc', 'category_id'=>$category->children));
        else
            $brands = $this->brands->get_brands(array('is_visible'=>1, 'sort'=>'name', 'sort_type'=>'asc'));

        foreach($brands as $index=>$b)
        {
            $b->products_count = $this->products->count_products(array('brand_id'=>$b->id));
            if ($b->products_count == 0)
                unset($brands[$index]);
        }

        $this->design->assign('brands', $brands);
        $this->design->assign('tags_groups', $this->tags->get_taggroups());

        return $this->design->fetch($this->design->getTemplateDir('admin').'products.tpl');
    }
}