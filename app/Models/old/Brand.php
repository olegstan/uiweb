<?php
namespace app\models;

use core\Collection;
use app\models\product\Product;;
use app\layer\LayerModel;

class Brand extends LayerModel
{
    protected $table = 'mc_brands';

    public $meta_title;
    public $meta_keywords;
    public $meta_description;

    public $url;

    public $products = [];

    public function afterSelect()
    {
        $this->front_url = '/brand/' . $this->url . '/';

        return $this;
    }

    public static function products(Collection $collection, $rules = null)
    {
        switch($rules['type']){
            case 'one':
                $brand = $collection->getResult();

                if($brand){
                    $products = (new Product())
                        ->query()
                        ->select()
                        ->where('is_visible = 1 AND brand_id = :brand_id', [':brand_id' => $brand->id])
                        ->execute()
                        ->all(null, 'id')
                        ->getResult();

                    if($products){
                        $brand->products = $products;
                        $brand->products_count = count($products);
                    }
                }
                break;
            case 'all':

                break;
        }
    }













    /*
    *
    * Функция возвращает массив брендов, удовлетворяющих фильтру
    * @param $filter
    *
    */
    public function get_brands($filter = array())
    {
        // По умолчанию
        $limit = 10000;
        $page = 1;

        $category_id_filter = '';
        $category_where_filter = '';
        $keyword_filter = '';
        $popular_filter = '';
        $order = 'b.position';
        $order_direction = '';
        $is_visible_filter = '';

        if(isset($filter['limit']))
            $limit = max(1, intval($filter['limit']));

        if(isset($filter['page']))
            $page = max(1, intval($filter['page']));

        $sql_limit = $this->db->placehold(' LIMIT ?, ? ', ($page-1)*$limit, $limit);

        if (isset($filter['is_visible']))
            $is_visible_filter = $this->db->placehold("AND b.is_visible=?", intval($filter['is_visible']));

        if(!empty($filter['category_id']))
        {
            $category_id_filter = $this->db->placehold('INNER JOIN __products p ON p.brand_id=b.id LEFT JOIN __products_categories pc ON p.id = pc.product_id');
            $category_where_filter = $this->db->placehold('AND pc.category_id in(?@)', (array)$filter['category_id']);
        }

        if(!empty($filter['keyword']))
            $keyword_filter = $this->db->placehold('AND (b.name LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%" OR b.meta_keywords LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%") ');

        if(isset($filter['is_popular']))
            $popular_filter = $this->db->placehold('AND b.is_popular=?', intval($filter['is_popular']));
                
        if(!empty($filter['sort']))
            switch ($filter['sort'])
            {
                case 'position':
                $order = 'b.position';
                break;
                case 'name':
                $order = 'b.name';
                break;
            }

        if (!empty($filter['sort_type']))
            switch($filter['sort_type'])
            {
                case 'asc':
                    $order_direction = '';
                    break;
                case 'desc':
                    $order_direction = 'desc';
                    break;
            }

        // Выбираем все бренды
        $query = $this->db->placehold("SELECT DISTINCT b.id, b.name, b.frontend_name, b.url, b.meta_title, b.meta_keywords, b.meta_description, b.description, b.description2, b.is_visible, b.position, b.css_class, b.tag_id, b.is_popular
                                         FROM __brands b
                                        $category_id_filter
                                        WHERE 1
                                            $is_visible_filter
                                            $keyword_filter
                                            $popular_filter
                                            $category_where_filter
                                        ORDER BY $order $order_direction
                                        $sql_limit");
        $this->db->query($query);

        return $this->db->results();
    }



    /*
    *
    * Функция возвращает бренд по его id или url
    * (в зависимости от типа аргумента, int - id, string - url)
    * @param $id id или url поста
    *
    */
    public function get_brand($id)
    {
        if (empty($id))
            return false;

        if(is_numeric($id))
            $filter = $this->db->placehold('id = ?', $id);
        else
            $filter = $this->db->placehold('url = ? or name = ?', mb_substr($id, 0, mb_strlen($id, 'utf-8') - ($this->settings->postfix_brand_url == "/" ? 0 : mb_strlen($this->settings->postfix_brand_url, 'utf-8')), 'utf-8'), $id);
                
        $query = "SELECT id, name, frontend_name, url, meta_title, meta_keywords, meta_description, description, description2, is_visible, position, css_class, tag_id, is_popular
                                 FROM __brands WHERE $filter ORDER BY name LIMIT 1";
        $this->db->query($query);
        return $this->db->result();
    }

    /*
    *
    * Добавление бренда
    * @param $brand
    *
    */
    public function add_brand($brand)
    {
        $brand = (array)$brand;
        $url_exist = false;

        if (!isset($brand['description']))
            $brand['description'] = "";
        if (!isset($brand['description2']))
            $brand['description2'] = "";

        if (empty($brand['url']))
        {
            if (!empty($brand['frontend_name']))
                $brand['url'] = $this->furl->generate_url($brand['frontend_name']);
            else
                $brand['url'] = $this->furl->generate_url($brand['name']);

            $this->db->query("SELECT count(id) as count from __brands WHERE url=?", $brand['url']);
            $k = $this->db->result('count');
            if ($k > 0)
                $url_exist = true;
        }

        $this->db->query("INSERT INTO __brands SET ?%", $brand);
        $id = $this->db->insert_id();

        $this->db->query("UPDATE __brands SET position=id WHERE id=?", $id);
        if ($url_exist)
        {
            $brand['url'] = $this->furl->generate_url($brand['url'].'-'.$id);
            $this->db->query("UPDATE __brands SET url=? WHERE id=?", $brand['url'], $id);
        }
        return $id;
    }

    /*
    *
    * Обновление бренда(ов)
    * @param $brand
    *
    */
    public function update_brand($id, $brand)
    {
        $url_exist = false;
        $brand = (array)$brand;

        if(isset($brand['url']) && empty($brand['url']) && (isset($brand['name']) || isset($brand['frontend_name'])))
        {
            if (isset($brand['frontend_name']) && !empty($brand['frontend_name']))
                $brand['url'] = $this->furl->generate_url($brand['frontend_name']);
            else
                $brand['url'] = $this->furl->generate_url($brand['name']);

            $this->db->query("SELECT count(id) as count from __brands WHERE url=?", $brand['url']);
            $k = $this->db->result('count');
            if ($k > 0)
                $brand['url'] = $this->furl->generate_url($brand['url'].'-'.$id);
        }

        if (!empty($brand['url']))
        {
            $this->db->query("SELECT count(id) as count from __brands WHERE url=? and id<>?", $brand['url'], $id);
            $k = $this->db->result('count');
            if ($k > 0)
                $brand['url'] = $this->furl->generate_url($brand['url'].'-'.$id);
        }

        $query = $this->db->placehold("UPDATE __brands SET ?% WHERE id=? LIMIT 1", $brand, intval($id));
        $this->db->query($query);
        return $id;
    }

    /*
    *
    * Удаление бренда
    * @param $id
    *
    */
    public function delete_brand($id)
    {
        if(!$brand = $this->get_brand(intval($id)))
            return false;
        if(!empty($id))
        {
            if ($brand->tag_id > 0)
                $this->tags->delete_tag($brand->tag_id);

            // Удаляем изображения
            $images = $this->image->get_images('brands', $id);
            foreach($images as $i)
                $this->image->delete_image('brands', $id, $i->id);

            $query = $this->db->placehold("DELETE FROM __brands WHERE id=? LIMIT 1", $id);
            $this->db->query($query);
            /**$query = $this->db->placehold("UPDATE __products SET brand_id=NULL WHERE brand_id=?", $id);
            $this->db->query($query);**/
        }
    }

    /*
    *
    *    Вывод хлебных крошек
    *
    */
    public function get_breadcrumbs($id, $type, $show_self_element = true)
    {
        $return_str = "";
        $id = intval($id);
        if (!$id)
            $return_str;
        $brand_module = $this->furl->get_module_by_name('BrandController');
        $brand = $this->brands->get_brand($id);

        $return_str = $this->settings->breadcrumbs_selected_element_open_tag . $brand->name . $this->settings->breadcrumbs_selected_element_close_tag . $return_str;

        $return_str = $this->settings->breadcrumbs_open_tag . $this->settings->breadcrumbs_first_element . $return_str . $this->settings->breadcrumbs_close_tag;
        return $return_str;
    }

    /**
     * Функция возвращает количество брендов удовлетворяющих фильтру
     */
    public function count_brands($filter = array())
    {
        $category_id_filter = '';
        $keyword_filter = '';
        $popular_filter = '';
        $is_visible_filter = '';

        if (isset($filter['is_visible']))
            $is_visible_filter = $this->db->placehold("AND b.is_visible=?", intval($filter['is_visible']));

        if(!empty($filter['category_id']))
            $category_id_filter = $this->db->placehold('INNER JOIN __products p ON p.brand_id=b.id LEFT JOIN __products_categories pc ON p.id = pc.product_id WHERE pc.category_id in(?@)', (array)$filter['category_id']);

        if(!empty($filter['keyword']))
            $keyword_filter = $this->db->placehold('AND (b.name LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%" OR b.meta_keywords LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%") ');

        if(isset($filter['is_popular']))
            $popular_filter = $this->db->placehold('AND b.is_popular=?', intval($filter['is_popular']));

        // Выбираем все бренды
        $query = $this->db->placehold("SELECT count(DISTINCT b.id) as count
                                         FROM __brands b $category_id_filter
                                        WHERE 1
                                            $is_visible_filter $keyword_filter $popular_filter");
        $this->db->query($query);

        return $this->db->result('count');
    }

    public function count($conditions, $fields)
    {
        return $this
            ->query
            ->select('count(id) AS count')
            ->where($conditions, $fields)
            ->execute()
            ->all()
            ->getResult();

        /*if (isset($filter['is_visible'])){
            $is_visible_filter = $this->db->placehold("AND b.is_visible=?", intval($filter['is_visible']));
        }

        if(!empty($filter['category_id'])){
            $category_id_filter = $this->db->placehold('INNER JOIN __products p ON p.brand_id=b.id LEFT JOIN __products_categories pc ON p.id = pc.product_id WHERE pc.category_id in(?@)', (array)$filter['category_id']);
        }

        if(!empty($filter['keyword'])){
            $keyword_filter = $this->db->placehold('AND (b.name LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%" OR b.meta_keywords LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%") ');
        }

        if(isset($filter['is_popular'])){

        }
            $popular_filter = */


    }
}