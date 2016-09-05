<?php
namespace app\controllers;

use app\layer\LayerController;

class MainController extends LayerController
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
        if (!empty($this->param_url))
            return false;

        $callback_request = false;
        $category_lazy_load = false;
        $category_lazy_filter = array('is_visible'=>1);
        foreach($this->params_arr as $p=>$v)
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
        }

        if ($category_lazy_load)
        {
            $result = $this->categories->get_categories_lazy_load_filter($category_lazy_filter);
            header("Content-type: application/json; charset=UTF-8");
            header("Cache-Control: must-revalidate");
            header("Pragma: no-cache");
            header("Expires: -1");
            print json_encode($result);
            die();
        }

        if ($callback_request)
        {
            $user_id = isset($this->user) ? $this->user->id : 0;
            $phone = array_key_exists('phone_number', $this->params_arr) ? $this->params_arr['phone_number'] : '';
            $match_res = preg_match("/^[^\(]+\(([^\)]+)\).(.+)$/", $phone, $matches);
            $user_name = array_key_exists('user_name', $this->params_arr) ? $this->params_arr['user_name'] : '';
            $call_time = array_key_exists('call_time', $this->params_arr) ? $this->params_arr['call_time'] : '';
            $message = array_key_exists('message', $this->params_arr) ? $this->params_arr['message'] : '';
            $result = false;

            if ($match_res && count($matches) == 3)
            {
                $callback = new StdClass;
                $callback->user_id = $user_id;
                $callback->user_name = $user_name;
                $callback->phone_code = $matches[1];
                $callback->phone = str_replace("-","",$matches[2]);
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

            header("Content-type: application/json; charset=UTF-8");
            header("Cache-Control: must-revalidate");
            header("Pragma: no-cache");
            header("Expires: -1");
            print json_encode($result);
            die();
        }

        $meta_title = "";
        $meta_keywords = "";
        $meta_description = "";

        $menu_items = $this->materials->get_menu_items_filter(array('is_main'=>1));
        if (!$menu_items)
            return false;
        $main_menu_item = reset($menu_items);
        if (!$main_menu_item)
            return false;
        switch($main_menu_item->object_type){
            case "material":
                $material = $this->materials->get_material($main_menu_item->object_id);
                if ($material)
                {
                    $this->design->assign('material', $material);
                    $images_gallery = $this->image->get_images('materials-gallery', $material->id);
                    $this->design->assign('images_gallery', $images_gallery);
                    $attachments = $this->attachments->get_attachments('materials', $material->id);
                    $this->design->assign('attachments', $attachments);
                    $meta_title = $material->meta_title;
                    $meta_keywords = $material->meta_keywords;
                    $meta_description = $material->meta_description;
                }
                break;
        }
        $this->design->assign('main_menu_item', $main_menu_item);

        //Группы товаров
        $groups_on_main = $this->blocks->get_blocks(array('is_visible'=>1));
        foreach($groups_on_main as $index=>$g)
        {
            $groups_on_main[$index]->related_products = $this->blocks->get_related_products(array('group_id'=>$g->id, 'is_visible'=>1));
            $groups_on_main[$index]->related_products = array_slice($groups_on_main[$index]->related_products, 0, $g->products_count);

            if ($groups_on_main[$index]->related_products)
            {
                foreach($groups_on_main[$index]->related_products as $index2=>$related)
                    $groups_on_main[$index]->related_products[$index2] = $this->products->get_product($related->product_id);

                $groups_on_main[$index]->related_products = $this->products->get_data_for_frontend_products($groups_on_main[$index]->related_products);
            }

            if (count($groups_on_main[$index]->related_products) == 0)
            {
                unset($groups_on_main[$index]);
                continue;
            }
        }
        $this->design->assign('groups_on_main', $groups_on_main);
        //Группы товаров (The End)

        //Группы категорий
        $groups_categories_on_main = $this->blocks->get_categories_blocks(array('is_visible'=>1));
        foreach($groups_categories_on_main as $index=>$g)
        {
            $groups_categories_on_main[$index]->related_categories = $this->blocks->get_related_categories(array('group_id'=>$g->id, 'is_visible'=>1));
            $groups_categories_on_main[$index]->related_categories = array_slice($groups_categories_on_main[$index]->related_categories, 0, $g->categories_count);

            if ($groups_categories_on_main[$index]->related_categories)
            {
                foreach($groups_categories_on_main[$index]->related_categories as $index2=>$related)
                    $groups_categories_on_main[$index]->related_categories[$index2] = $this->categories->get_category($related->category_id);

                foreach($groups_categories_on_main[$index]->related_categories as $index2=>$category)
                {
                    $groups_categories_on_main[$index]->related_categories[$index2]->images = $this->image->get_images('categories', $category->id);
                    $groups_categories_on_main[$index]->related_categories[$index2]->image = reset($groups_categories_on_main[$index]->related_categories[$index2]->images);
                    $groups_categories_on_main[$index]->related_categories[$index2]->products_count = $this->products->count_products(array('category_id'=>$category->children, 'is_visible'=>1));
                }
            }

            if (count($groups_categories_on_main[$index]->related_categories) == 0)
            {
                unset($groups_categories_on_main[$index]);
                continue;
            }
        }
        $this->design->assign('groups_categories_on_main', $groups_categories_on_main);
        //Группы категорий (The End)

        $tags_groups = $this->tags->get_taggroups(array('is_auto'=>0, 'is_enabled'=>1));
        $this->design->assign('tags_groups', $tags_groups);

        //Слайд-шоу
        $slides = $this->slideshow->get_slides(array('is_visible'=>1));
        foreach($slides as $index=>$slide)
        {
            $images = $this->image->get_images('slideshow', $slide->id);
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
        $tmp_category = $this->materials->get_category(intval($this->settings->news_category_id));
        $news_filter['category_id'] = $tmp_category->children;
        $news_count = $this->materials->count_materials($news_filter);
        $news_filter['limit'] = $this->settings->news_show_count;
        $news = $this->materials->get_materials($news_filter);
        foreach($news as $index=>$news_item)
        {
            $news[$index]->images = $this->image->get_images('materials', $news_item->id);
            $news[$index]->image = reset($news[$index]->images);
        }

        $this->design->assign('news', $news);
        $this->design->assign('news_count', $news_count);
        $news_category = new stdClass;
        $news_category->object_type = 'material-category';
        $news_category->object_id = $tmp_category->id;
        $this->design->assign('news_category', $news_category);

        $this->design->assign('meta_title', $meta_title);
        $this->design->assign('meta_keywords', $meta_keywords);
        $this->design->assign('meta_description', $meta_description);

        $this->design->assign('products_module', $this->furl->get_module_by_name('ProductsController'));

        if (count($this->params_arr) == 1 && array_key_exists("ajax", $this->params_arr))
        {
            $result = array('success'=>true, 'data'=>$this->design->fetch($this->design->getTemplateDir('frontend').'main.tpl'), 'meta_title'=>$meta_title, 'meta_keywords'=>$meta_keywords, 'meta_description'=>$meta_description);
            header("Content-type: application/json; charset=UTF-8");
            header("Cache-Control: must-revalidate");
            header("Pragma: no-cache");
            header("Expires: -1");
            print json_encode($result);
            die();
        }

        return $this->design->fetch($this->design->getTemplateDir('frontend').'main.tpl');
    }
}