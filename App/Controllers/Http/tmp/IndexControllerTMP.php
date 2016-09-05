<?php
namespace app\controllers;

use app\layer\LayerController;
use app\controllers\ErrorController;

use app\models\block\Block;
use app\models\Brand;
use app\models\Slideshow;
use app\models\Tag;
use app\models\Callback;
use app\models\Review;
use app\models\Category;
use app\models\attachment\Attachment;
use app\models\image\Image;
use app\models\material\Material;
use app\models\material\MaterialMenuItem;
use app\models\product\Product;
use app\models\Order;
use core\helper\Response;

use core\helper\Furl;

use \stdClass;

class IndexController extends LayerController
{
    public $modules_dir = 'controllers/';
    public $ext_modules_dir = 'modules/';

    public function __construct()
    {
        parent::__construct();
    }

    /*private function recursive_categories_tree(&$tree, $steps)
    {
        if ($steps <= 0)
            return;

        foreach($tree as &$tree_item)
        {
            $tree_item->subcategories = (new Category())->get_categories_lazy_load_filter(array('parent_id'=>$tree_item->id, 'is_visible'=>1));

            $this->recursive_categories_tree($tree_item->subcategories, $steps-1);
        }
    }*/

    private function check_is_visible_menu_items(&$items)
    {
        $result = false;
        if (!$items)
            return $result;
        foreach ($items as $item) {
            $item->show_item = false;
            if ($item->is_visible && in_array($item->module_name, $this->global_group_permissions)) {
                $item->show_item = true;
                $result = true;
            } else
                if ($item->is_visible && !empty($item->subitems)) {
                    if ($this->check_is_visible_menu_items($item->subitems)) {
                        $item->show_item = true;
                        $result = true;
                    }
                }
        }
        return $result;
    }

    private function make_url_menu_items(&$items)
    {
        $result = "";
        if (!$items)
            return $result;
        foreach ($items as $item) {
            $item->active_url = "";

            if (in_array($item->module_name, $this->global_group_permissions)) {
                if (!empty($item->url))
                    $item->active_url = $item->url;
                else
                    if (!empty($item->sub_url))
                        $item->active_url = $item->sub_url;
                if (empty($result))
                    $result = $item->active_url;
            } else
                if ($item->is_visible && !empty($item->subitems)) {
                    $item->active_url = $this->make_url_menu_items($item->subitems);
                    if (empty($result))
                        $result = $item->active_url;
                }

            if ($item->active_url == "/")
                $item->active_url = "";
        }
        if ($result == "/")
            $result = "";
        return $result;
    }

    /**
     *
     * Отображение
     *
     */
    function index()
    {
        //if (!empty($this->user))
        //$this->design->assign('favorites_products_count', $this->products->count_favorites_products($this->user->id));


        //$this->design->assign('categories_frontend_all', $this->categories->get_categories_tree());
        /*
                    //Выбираем бренды
                    $brands_popular = $this->brands->get_brands(array('is_visible'=>1, 'is_popular'=>1, 'sort'=>'name'));
                    $brands_all = $this->brands->get_brands(array('is_visible'=>1, 'sort'=>'name'));
                    foreach($brands_popular as $index=>$brand)
                        $brands_popular[$index]->products_count = $this->products->count_products(array('brand_id'=>$brand->id, 'is_visible'=>1));
                    foreach($brands_all as $index=>$brand)
                        $brands_all[$index]->products_count = $this->products->count_products(array('brand_id'=>$brand->id, 'is_visible'=>1));
                    $this->design->assign('brands_popular', $brands_popular);
                    $this->design->assign('brands_all', $brands_all);



                    //Список просмотренных товаров
                    $history_products = array();
                    if(!empty($_COOKIE['history_products']))
                    {
                        $history_products_ids = explode(',', $_COOKIE['history_products']);
                        $history_products_ids = array_reverse($history_products_ids);

                        foreach($history_products_ids as $history_id)
                        {
                            $tmp_product = $this->products->get_product($history_id);
                            if ($tmp_product && $tmp_product->is_visible)
                                $history_products[] = $tmp_product;
                        }

                        foreach($history_products as &$history_product)
                        {
                            $history_product->images = $this->image->get_images('products', $history_product->id);
                            $history_product->image = reset($history_product->images);
                            $history_product->variants = $this->variants->get_variants(array('product_id'=>$history_product->id, 'is_visible'=>1, 'sort'=>'abs(stock)', 'sort_type'=>'desc'));
                            $history_product->variant = reset($history_product->variants);
                        }
                    }
                    $this->design->assign('history_products', $history_products);

                    $compare_items = $this->compare->get_compare();
                    $ids_str = join(",", $compare_items);
                    $compare_href = $this->config->root_url . $compare_module->url . (empty($ids_str) ? "" : "?id=".$ids_str);
                    $this->design->assign('compare_items', $compare_items);
                    $this->design->assign('compare_href', $compare_href);
                    $this->design->assign('compare_href_show', !empty($ids_str));
                }



                // Создаем основной блок страницы
                if (!$content = $this->main->fetch())
                    return false;
        */


        //Выбираем 1-й уровень дерева категорий
        /*$categories_frontend = (new Category())
            ->query()
            ->select()
            ->where('parent_id = :parent_id AND is_visible = :is_visible', [':parent_id' => 0, ':is_visible' => 1])
            ->execute()
            ->all()
            ->getResult();*/

        /*$categories_frontend = (new Category())->get_categories(['parent_id' => 0, 'is_visible' => 1]);

        $this->recursive_categories_tree($categories_frontend, 2);
        $this->design->assign('categories_frontend', $categories_frontend);*/


        //Используемые сортировки
        $sort_methods = array();
        $sort_methods_name = array();
        $sort_methods_biger = array();
        $sort_methods_lower = array();
        $sort_methods_icon_asc = array();
        $sort_methods_icon_desc = array();
        $sort_methods_direction = array();
        if ($this->settings->settings_sort_position)
            if (1) {
                $sort_methods[] = 'position';
                $sort_methods_name[] = 'Порядку';
                $sort_methods_biger[] = '';
                $sort_methods_lower[] = '';
                $sort_methods_icon_asc[] = '';
                $sort_methods_icon_desc[] = '';
                $sort_methods_direction[] = 'asc';
            }
        if ($this->settings->settings_sort_price)
            if (1) {
                $sort_methods[] = 'price';
                $sort_methods_name[] = 'Цене';
                $sort_methods_biger[] = 'Сначала дорогие';
                $sort_methods_lower[] = 'Сначала дешевые';
                $sort_methods_icon_asc[] = 'fa fa-sort-amount-asc';
                $sort_methods_icon_desc[] = 'fa fa-sort-amount-desc';
                $sort_methods_direction[] = 'asc';
            }
        if ($this->settings->settings_sort_popular)
            if (1) {
                $sort_methods[] = 'popular';
                $sort_methods_name[] = 'Популярности';
                $sort_methods_biger[] = 'Сначала не популярные';
                $sort_methods_lower[] = 'Сначала популярные';
                $sort_methods_icon_asc[] = 'fa fa-sort-amount-asc';
                $sort_methods_icon_desc[] = 'fa fa-sort-amount-desc';
                $sort_methods_direction[] = 'asc';
            }
        if ($this->settings->settings_sort_newest)
            if (1) {
                $sort_methods[] = 'newest';
                $sort_methods_name[] = 'Новизне';
                $sort_methods_biger[] = 'Сначала новые';
                $sort_methods_lower[] = 'Сначала старые';
                $sort_methods_icon_asc[] = 'fa fa-sort-numeric-asc';
                $sort_methods_icon_desc[] = 'fa fa-sort-numeric-desc';
                $sort_methods_direction[] = 'desc';
            }
        if ($this->settings->settings_sort_name)
            if (1) {
                $sort_methods[] = 'name';
                $sort_methods_name[] = 'Алфавиту';
                $sort_methods_biger[] = '';
                $sort_methods_lower[] = '';
                $sort_methods_icon_asc[] = 'fa fa-sort-alpha-asc';
                $sort_methods_icon_desc[] = 'fa fa-sort-alpha-desc';
                $sort_methods_direction[] = 'asc';
            }

        $this->design->assign('sort_methods', $sort_methods);
        $this->design->assign('sort_methods_name', $sort_methods_name);
        $this->design->assign('sort_methods_biger', $sort_methods_biger);
        $this->design->assign('sort_methods_lower', $sort_methods_lower);
        $this->design->assign('sort_methods_icon_asc', $sort_methods_icon_asc);
        $this->design->assign('sort_methods_icon_desc', $sort_methods_icon_desc);
        $this->design->assign('sort_methods_direction', $sort_methods_direction);




        //Счетчики на панель администратора вверху
        $new_order_status = (new Order())->get_status('Новые');
        if ($new_order_status) {
            $count_new_orders = (new Order())->count_orders(array('status_id' => $new_order_status->id, 'moderated' => 0));
            $this->design->assign('count_new_orders', $count_new_orders);
        }

        $count_new_reviews = (new Review())->count_reviews(array('moderated' => 0));
        $this->design->assign('count_new_reviews', $count_new_reviews);

        $callbacks_count = (new Callback())->count_callbacks(array('moderated' => 0));
        $this->design->assign('count_callbacks', $callbacks_count);
        //Счетчики на панель администратора вверху (End)


        /**
         * main
         */


        //$this->main->set_params($url, (array)json_decode($module->options));
        $callback_request = false;
        $category_lazy_load = false;
        $category_lazy_filter = array('is_visible' => 1);
        /*foreach($this->params_arr as $p=>$v)
        {
            switch ($p)
            {
                case "lazy_load":
                    $category_lazy_load = true;
                    break;
                case "parent_id":
                    $category_lazy_filter['parent_id'] = $this->params_arr[$p];
                    break;
                case "callback":
                    $callback_request = true;
                    break;
            }
        }*/



        //$this->design->assign('main_menu_item', $main_menu_item);

        //Группы товаров
        $groups_on_main = (new Block())->get_blocks(array('is_visible' => 1));


        foreach ($groups_on_main as $index => $g) {
            $groups_on_main[$index]->related_products = (new Block())->get_related_products(array('group_id' => $g->id, 'is_visible' => 1));
            $groups_on_main[$index]->related_products = array_slice($groups_on_main[$index]->related_products, 0, $g->products_count);

            if ($groups_on_main[$index]->related_products) {
                foreach ($groups_on_main[$index]->related_products as $index2 => $related)
                    $groups_on_main[$index]->related_products[$index2] = (new Product())->get_product($related->product_id);

                $groups_on_main[$index]->related_products = (new Product())->get_data_for_frontend_products($groups_on_main[$index]->related_products);
            }

            if (count($groups_on_main[$index]->related_products) == 0) {
                unset($groups_on_main[$index]);
                continue;
            }
        }


        $this->design->assign('groups_on_main', $groups_on_main);
        //Группы товаров (The End)

        //Группы категорий
        $groups_categories_on_main = (new Block())->get_categories_blocks(array('is_visible' => 1));
        foreach ($groups_categories_on_main as $index => $g) {
            $groups_categories_on_main[$index]->related_categories = (new Block())->get_related_categories(array('group_id' => $g->id, 'is_visible' => 1));
            $groups_categories_on_main[$index]->related_categories = array_slice($groups_categories_on_main[$index]->related_categories, 0, $g->categories_count);

            if ($groups_categories_on_main[$index]->related_categories) {
                foreach ($groups_categories_on_main[$index]->related_categories as $index2 => $related)
                    $groups_categories_on_main[$index]->related_categories[$index2] = (new Category())->get_category($related->category_id);

                foreach ($groups_categories_on_main[$index]->related_categories as $index2 => $category) {
                    $groups_categories_on_main[$index]->related_categories[$index2]->images = (new Image())->get_images('categories', $category->id);
                    $groups_categories_on_main[$index]->related_categories[$index2]->image = reset($groups_categories_on_main[$index]->related_categories[$index2]->images);
                    $groups_categories_on_main[$index]->related_categories[$index2]->products_count = (new Product())->count_products(array('category_id' => $category->children, 'is_visible' => 1));
                }
            }

            if (count($groups_categories_on_main[$index]->related_categories) == 0) {
                unset($groups_categories_on_main[$index]);
                continue;
            }
        }


        $this->design->assign('groups_categories_on_main', $groups_categories_on_main);
        //Группы категорий (The End)

        $tags_groups = (new Tag)->get_taggroups(array('is_auto' => 0, 'is_enabled' => 1));
        $this->design->assign('tags_groups', $tags_groups);

        //Слайд-шоу
        $slides = (new Slideshow())->get_slides(array('is_visible' => 1));
        foreach ($slides as $index => $slide) {
            $images = (new Image())->get_images('slideshow', $slide->id);
            if (!empty($images))
                $slides[$index]->image = @reset($images);
        }
        $this->design->assign('slides', $slides);
        //Слайд-шоу (The End)

        //Выберем новости
        $news_filter = array();
        $news_filter['is_visible'] = 1;
        $news_filter['sort'] = 'newest';
        $news_filter['sort_type'] = 'desc';
        $tmp_category = (new Material())->get_category(intval($this->settings->news_category_id));
        $news_filter['category_id'] = $tmp_category->children;
        $news_count = (new Material())->count_materials($news_filter);
        $news_filter['limit'] = $this->settings->news_show_count;
        $news = (new Material())->get_materials($news_filter);
        foreach ($news as $index => $news_item) {
            $news[$index]->images = (new Image())->get_images('materials', $news_item->id);
            $news[$index]->image = reset($news[$index]->images);
        }

        $this->design->assign('news', $news);
        $this->design->assign('news_count', $news_count);
        $news_category = new stdClass;
        $news_category->object_type = 'material-category';
        $news_category->object_id = $tmp_category->id;
        $this->design->assign('news_category', $news_category);

        $this->design->assign('products_module', (new Furl())->get_module_by_name('ProductsController'));

        /*if (count($this->params_arr) == 1 && array_key_exists("ajax", $this->params_arr)) {
            $result = array('success' => true, 'data' => $this->design->fetch($this->design->getTemplateDir('frontend') . 'main.tpl'), 'meta_title' => $meta_title, 'meta_keywords' => $meta_keywords, 'meta_description' => $meta_description);
            header("Content-type: application/json; charset=UTF-8");
            header("Cache-Control: must-revalidate");
            header("Pragma: no-cache");
            header("Expires: -1");
            print json_encode($result);
            die();
        }*/


        /**
         * main
         */
        $settings = new stdClass();
        $settings->default_show_mode = true;
        $settings->slideshow_enabled = true;


        $this->design->assign('settings', $settings);
        $this->design->assign('main_menu_item', $main_menu_item);


        return $this->render(TPL . '/' . $this->core->tpl_path .'/html/main.tpl');

    }

    public function typography()
    {
        return $this->render(TPL . '/' . $this->core->tpl_path .'/html/typography.tpl');
    }

    public function callback()
    {
        $user_id = isset($this->user) ? $this->user->id : 0;
        $phone = array_key_exists('phone_number', $this->params_arr) ? $this->params_arr['phone_number'] : '';
        $match_res = preg_match("/^[^\(]+\(([^\)]+)\).(.+)$/", $phone, $matches);
        $user_name = array_key_exists('user_name', $this->params_arr) ? $this->params_arr['user_name'] : '';
        $call_time = array_key_exists('call_time', $this->params_arr) ? $this->params_arr['call_time'] : '';
        $message = array_key_exists('message', $this->params_arr) ? $this->params_arr['message'] : '';
        $result = false;

        if ($match_res && count($matches) == 3) {
            $callback = new StdClass;
            $callback->user_id = $user_id;
            $callback->user_name = $user_name;
            $callback->phone_code = $matches[1];
            $callback->phone = str_replace("-", "", $matches[2]);
            $callback->call_time = $call_time;
            $callback->message = $message;
            $callback->ip = $_SERVER['REMOTE_ADDR'];

            $callback->id = $this->callbacks->add_callback($callback);

            //Отправляем письмо админку, т.к. у пользователя мы не знаем почту
            $this->notify_email->email_callback($callback->id);
            //Отправляем смс админу
            $this->notify_email->sms_callback($callback->id);

            //$this->db->query("INSERT INTO __callbacks(user_id, phone_code, phone, call_time, message) VALUES(?,?,?,?,?)", $user_id, $matches[1], str_replace("-","",$matches[2]), $call_time, $message);
            $result = true;
        }

        return Response::json($result);
    }
}