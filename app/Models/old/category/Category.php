<?php
namespace app\models\category;

use app\layer\LayerModel;
use app\models\image\Image;
use app\models\product\Product;
use app\models\product\ProductCategory;

use core\Collection;
use core\db\Database;

class Category extends LayerModel
{
    protected $table = 'mc_categories';

    public $meta_title;
    public $meta_keywords;
    public $meta_description;

    public $url;

    public $css_class;

    public $image;
    public $images = [];

    public $products = [];

    public $nodes = [];

    private $module_id = 3;
    /**
     * @var int
     * уровень вложенности
     */

    public $guarded = [
        'url'
    ];

    public function __construct()
    {
        $this->image = (new Image())->defaultImage();
        $this->image->type = 'categories';
        //self::getDB() = new Database();
    }

    public function afterSelect($rules = null)
    {
        $this->front_url = '/catalog/' . $this->url . '/';
        $this->admin_url = '/admin/category/edit/' . $this->id;

        return $this;
    }

    public static function products(Collection $collection, $rules = null)
    {
        switch($rules['type']){
            case 'one':
                $category = $collection->getResult();

                if($category){
                    $products_ids = (new ProductCategory())
                        ->query()
                        ->select()
                        ->where('category_id = :category_id', [':category_id' => $category->id])
                        ->execute()
                        ->all(null, 'id')
                        ->getField('product_id');

                    if($products_ids) {
                        $products = (new Product())
                            ->query()
                            ->with(['images'])
                            ->select()
                            ->where('is_visible = 1 AND id IN (' . implode(',', $products_ids) . ')')
                            ->execute()
                            ->all(null, 'id')
                            ->getResult();

                        $category->products = $products;
                        $category->products_count = count($products);
                    }
                }
                break;
            case 'all':

                break;
        }
        return $collection;
    }

    public static function image(Collection $collection, $rules = null)
    {
        switch($rules['type']){
            case 'one':

                break;
            case 'all':
                $categories = $collection->getResult();
                $categories_ids = $collection->getField('id');
                $categories_map = $collection->toMap()->getMap();

                if($categories){
                    $images = (new Image())
                        ->query()
                        ->select()
                        ->where('object_id IN (' . implode(',', $categories_ids) . ') AND module_id = :module_id', [':module_id' => current($categories)->module_id])
                        ->order('position')
                        ->execute()
                        ->all(['folder' => 'categories'])
                        ->getResult();

                    if($images) {
                        foreach($images as $image){
                            if(isset($categories_map[$image->object_id])){
                                if(empty($categories_map[$image->object_id]->images)) {
                                    $categories_map[$image->object_id]->image = $image;
                                }
                                $categories_map[$image->object_id]->images[] = $image;
                            }
                        }
                    }

                }
                break;
        }
        return $collection;
    }




    // Список указателей на категории в дереве категорий (ключ = id категории)
    private $all_categories;
    // Дерево категорий
    private $categories_tree;

    // Функция возвращает массив категорий
    public function get_categories($filter = array()){
        if(!isset($this->categories_tree))
            $this->init_categories();
 
        if(!empty($filter['product_id']))
        {
            $query = self::getDB()->placehold("SELECT category_id FROM __products_categories WHERE product_id in(?@) ORDER BY position", (array)$filter['product_id']);
            self::getDB()->query($query);
            $categories_ids = self::getDB()->results('category_id');
            $result = array();
            foreach($categories_ids as $id)
                if(isset($this->all_categories[$id]))
                    $result[$id] = $this->all_categories[$id];
            return $result;
        }

        return $this->all_categories;
        //$this->query->select()->w

        /*if(!empty($filter['product_id']))
        {

        }
        return $this->query->select()->execute()->all()->getResult();*/

    }

    // Функция возвращает id категорий для заданного товара
    public function get_product_categories($product_id){
        $query = self::getDB()->placehold("SELECT product_id, category_id, position FROM __products_categories WHERE product_id in(?@) ORDER BY position", (array)$product_id);
        self::getDB()->query($query);
        return self::getDB()->results();
    }

    // Функция возвращает id категорий для всех товаров
    public function get_products_categories(){
        $query = self::getDB()->placehold("SELECT product_id, category_id, position FROM __products_categories ORDER BY position");
        self::getDB()->query($query);
        return self::getDB()->results();
    }

    // Функция возвращает дерево категорий
    public function get_categories_tree(){
        if(!isset($this->categories_tree))
            $this->init_categories();

        return $this->categories_tree;
    }

    // Функция возвращает заданную категорию
    public function get_category($id){
        if(!isset($this->all_categories))
            $this->init_categories();

        if(is_numeric($id) && array_key_exists(intval($id), $this->all_categories))
            return $category = $this->all_categories[intval($id)];
        elseif(is_string($id))
            foreach ($this->all_categories as $category)
                if ($category->url == $id)
                    return $this->get_category((int)$category->id);
        return false;
    }

    // Добавление категории
    public function add_category($category){
        $category = (array)$category;

        $url_exist = false;

        if (empty($category['url']))
        {
            if (!empty($category['frontend_name']))
                $category['url'] = $this->furl->generate_url($category['frontend_name']);
            else
                $category['url'] = $this->furl->generate_url($category['name']);

            self::getDB()->query("SELECT count(id) as count from __categories WHERE url=?", $category['url']);
            $k = self::getDB()->result('count');
            if ($k > 0)
                $url_exist = true;
        }

        $query = self::getDB()->placehold("INSERT INTO __categories SET ?%, updated_dt=now()", $category);
        self::getDB()->query($query);
        $id = self::getDB()->insert_id();
        self::getDB()->query("UPDATE __categories SET position=id WHERE id=?", $id);
        if ($url_exist)
        {
            $category['url'] = $this->furl->generate_url($category['url'].'-'.$id);
            self::getDB()->query("UPDATE __categories SET url=? WHERE id=?", $category['url'], $id);
        }
        $this->init_categories();
        return $id;
    }

    private function update_is_visible_subcategories($id, $is_visible){
        $category = $this->get_category($id);

        foreach($category->children as $c_id)
        {
            $query = self::getDB()->placehold("UPDATE __categories SET is_visible=? WHERE id=?", $is_visible, $c_id);
            self::getDB()->query($query);
        }
    }

    private function update_modificators_subcategories($id, $modificators, $modificators_groups){
        $category = $this->get_category($id);

        foreach($category->children as $c_id)
        {
            $query = self::getDB()->placehold("UPDATE __categories SET modificators=?, modificators_groups=? WHERE id=?", $modificators, $modificators_groups, $c_id);
            self::getDB()->query($query);
        }
    }

    // Изменение категории
    public function update_category($id, $category){
        $url_exist = false;
        $category = (array)$category;
        if (isset($category) && is_array($category) && isset($category['is_visible']))
            $this->update_is_visible_subcategories($id, $category['is_visible']);
        if (isset($category) && is_array($category) && isset($category['modificators']))
            $this->update_modificators_subcategories($id, $category['modificators'], $category['modificators_groups']);

        if(isset($category['url']) && empty($category['url']) && (isset($category['name']) || isset($category['frontend_name'])))
        {
            if (isset($category['frontend_name']) && !empty($category['frontend_name']))
                $category['url'] = $this->furl->generate_url($category['frontend_name']);
            else
                $category['url'] = $this->furl->generate_url($category['name']);

            self::getDB()->query("SELECT count(id) as count from __categories WHERE url=?", $category['url']);
            $k = self::getDB()->result('count');
            if ($k > 0)
                $category['url'] = $this->furl->generate_url($category['url'].'-'.$id);
        }

        if (!empty($category['url']))
        {
            self::getDB()->query("SELECT count(id) as count from __categories WHERE url=? and id<>?", $category['url'], $id);
            $k = self::getDB()->result('count');
            if ($k > 0)
                $category['url'] = $this->furl->generate_url($category['url'].'-'.$id);
        }

        $query = self::getDB()->placehold("UPDATE __categories SET ?%, updated_dt=now() WHERE id=? LIMIT 1", $category, intval($id));
        self::getDB()->query($query);
        $this->init_categories();
        return $id;
    }

    // Удаление категории
    public function delete_category($id){
        if(!$category = $this->get_category(intval($id)))
            return false;
        //Удаляем товары
        $products = $this->products->get_products(array('category_id'=>$category->children, 'limit'=>100000));
        foreach($products as $product)
            $this->products->delete_product($product->id);
        //Удаляем категорию и подкатегории
        foreach($category->children as $id)
        {
            if(!empty($id))
            {
                // Удаляем изображения
                $images = $this->image->get_images('categories', $id);
                foreach($images as $i)
                    $this->image->delete_image('categories', $id, $i->id);

                //Удаляем теги
                /**self::getDB()->query("DELETE FROM __tags_categories WHERE category_id=?", $id);**/

                $query = self::getDB()->placehold("DELETE FROM __categories WHERE id=? LIMIT 1", $id);
                self::getDB()->query($query);
                /**$query = self::getDB()->placehold("DELETE FROM __products_categories WHERE category_id=?", $id);
                self::getDB()->query($query);**/
            }
        }
        $this->init_categories();
        return true;
    }

    // Добавить категорию к заданному товару
    public function add_product_category($product_id, $category_id, $position=0){
        $query = self::getDB()->placehold("INSERT IGNORE INTO __products_categories SET product_id=?, category_id=?, position=?", $product_id, $category_id, $position);
        self::getDB()->query($query);
    }

    // Удалить категорию заданного товара
    public function delete_product_category($product_id, $category_id){
        $query = self::getDB()->placehold("DELETE FROM __products_categories WHERE product_id=? AND category_id=? LIMIT 1", intval($product_id), intval($category_id));
        self::getDB()->query($query);
    }

    // Инициализация категорий, после которой категории будем выбирать из локальной переменной
    private function init_categories(){
        // Дерево категорий
        $tree = new self();
        $tree->subcategories = array();

        // Указатели на узлы дерева
        $pointers = array();
        $pointers[0] = &$tree;
        $pointers[0]->path = array();

        // Выбираем все категории
        $query = self::getDB()->placehold("SELECT id, parent_id, url, name, frontend_name, description, description2, meta_title, meta_keywords, meta_description, is_visible, position, external_id, set_id, is_collapsed, css_class, show_mode, sort_type, unix_timestamp(updated_dt) updated_dt, modificators, modificators_groups, menu_level1_type, menu_level1_columns, menu_level1_width, menu_level1_align, menu_level1_use_banner, menu_level1_banner_id, menu_level1_column1_width, menu_level1_column2_width, menu_level1_column3_width, menu_level1_column4_width, menu_level1_column5_width, menu_level1_column6_width, menu_level2_columns, menu_level2_column
                                 FROM __categories ORDER BY parent_id, position");
        self::getDB()->query($query);
        $categories = self::getDB()->results();

        foreach ($categories as $category) {
            $category->url = '/catalog/' . $category->url;
        }


        $finish = false;
        // Не кончаем, пока не кончатся категории, или пока ниодну из оставшихся некуда приткнуть
        while(!empty($categories)  && !$finish)
        {

            $flag = false;
            // Проходим все выбранные категории
            foreach($categories as $k=>$category)
            {
                if (!empty($category->modificators)) {
                    //$category->modificators = explode(',', $category->modificators);
                }else
                    $category->modificators = array();
                if (!empty($category->modificators_groups) && !is_array($category->modificators_groups))
                    $category->modificators_groups = explode(',', $category->modificators_groups);
                else
                    $category->modificators_groups = array();
                if(isset($pointers[$category->parent_id]))
                {
                    // В дерево категорий (через указатель) добавляем текущую категорию
                    $pointers[$category->id] = $pointers[$category->parent_id]->subcategories[] = $category;

                    // Путь к текущей категории
                    $curr = clone($pointers[$category->id]);
                    $pointers[$category->id]->path = array_merge((array)$pointers[$category->parent_id]->path, array($curr));

                    // Убираем использованную категорию из массива категорий
                    unset($categories[$k]);
                    $flag = true;
                }
            }
            if(!$flag)
                $finish = true;
        }

        // Для каждой категории id всех ее деток узнаем
        $ids = array_reverse(array_keys($pointers));
        foreach($ids as $id)
        {
            if($id>0)
            {
                $pointers[$id]->children[] = $id;

                if(isset($pointers[$pointers[$id]->parent_id]->children))
                    $pointers[$pointers[$id]->parent_id]->children = array_merge($pointers[$id]->children, $pointers[$pointers[$id]->parent_id]->children);
                else
                    $pointers[$pointers[$id]->parent_id]->children = $pointers[$id]->children;
            }
        }
        unset($pointers[0]);

        $this->categories_tree = $tree->subcategories;
        $this->all_categories = $pointers;
    }

    /**
    * Функция возвращает количество категорий удовлетворяющих фильтру
    */
    public function count_categories_filter($filter = array()){
        $keyword_filter = '';
        $is_visible_filter = '';
        $exception_ids_filter = '';

        if(isset($filter['keyword']))
        {
            $keywords = explode(' ', $filter['keyword']);
            foreach($keywords as $keyword)
                $keyword_filter .= self::getDB()->placehold('AND (c.name LIKE "%'.mysql_real_escape_string(trim($keyword)).'%" OR c.meta_keywords LIKE "%'.mysql_real_escape_string(trim($keyword)).'%") ');
        }

        if(!empty($filter['is_visible']))
            $is_visible_filter = self::getDB()->placehold('AND c.is_visible=?', intval($filter['is_visible']));

        if (!empty($filter['exception']))
            $exception_ids_filter = self::getDB()->placehold('AND c.id not in(?@)', (array)$filter['exception']);

        $query = "SELECT count(distinct c.id) as count
                FROM __categories AS c
                WHERE 1
                    $keyword_filter
                    $is_visible_filter
                    $exception_ids_filter";

        self::getDB()->query($query);
        return self::getDB()->result('count');
    }

    /**
    * Функция возвращает категории удовлетворяющие фильтру
    * Возможные значения фильтра:
    * id - id категории или их массив
    * page - текущая страница, integer
    * limit - количество категорий на странице, integer
    * keyword - ключевое слово для поиска
    */
    public function get_categories_filter($filter = array()){
        // По умолчанию
        $limit = 100;
        $page = 1;
        $category_id_filter = '';
        $parent_id_filter = '';
        $keyword_filter = '';
        $is_visible_filter = '';
        $exception_ids_filter = '';
        $order = 'c.position';

        if(isset($filter['limit']))
            $limit = max(1, intval($filter['limit']));

        if(isset($filter['page']))
            $page = max(1, intval($filter['page']));

        $sql_limit = self::getDB()->placehold(' LIMIT ?, ? ', ($page-1)*$limit, $limit);

        if(!empty($filter['id']))
            $category_id_filter = self::getDB()->placehold('AND c.id in(?@)', (array)$filter['id']);

        if(isset($filter['parent_id']))
            $parent_id_filter = self::getDB()->placehold('AND c.parent_id in(?@)', (array)$filter['parent_id']);

        if(!empty($filter['is_visible']))
            $is_visible_filter = self::getDB()->placehold('AND c.is_visible=?', intval($filter['is_visible']));

         $inner_join_search_table = "";
         if(!empty($filter['keyword']))
        {
            /*$keywords = explode(' ', $filter['keyword']);
            foreach($keywords as $keyword)
                $keyword_filter .= self::getDB()->placehold('AND c.name LIKE "%'.mysql_real_escape_string(trim($keyword)).'%"');*/
            $keyword_filter = "AND MATCH(cs.`text`) AGAINST('*".mysql_real_escape_string(trim($filter['keyword']))."*' IN BOOLEAN MODE) > 0
                AND cs.`text` LIKE '%".mysql_real_escape_string(trim($filter['keyword']))."%'";
            $inner_join_search_table = "INNER JOIN mc_categories_search cs on c.id = cs.id";
        }

        if (!empty($filter['exception']))
            $exception_ids_filter = self::getDB()->placehold('AND c.id not in(?@)', (array)$filter['exception']);

        if (!empty($filter['order']))
            $order = $filter['order'];

        $query = "SELECT
                    c.id,
                    c.parent_id,
                    c.url,
                    c.name,
                    c.frontend_name,
                    c.meta_title,
                    c.meta_keywords,
                    c.meta_description,
                    c.description,
                    c.description2,
                    c.position,
                    c.is_visible,
                    c.external_id,
                    c.set_id,
                    c.is_collapsed,
                    unix_timestamp(c.updated_dt) updated_dt,
                    c.modificators,
                    c.modificators_groups,
                    c.menu_level1_type,
                    c.menu_level1_columns,
                    c.menu_level1_width,
                    c.menu_level1_align,
                    c.menu_level1_use_banner,
                    c.menu_level1_banner_id,
                    c.menu_level1_column1_width,
                    c.menu_level1_column2_width,
                    c.menu_level1_column3_width,
                    c.menu_level1_column4_width,
                    c.menu_level1_column5_width,
                    c.menu_level1_column6_width,
                    c.menu_level2_columns,
                    c.menu_level2_column
                FROM __categories c
                $inner_join_search_table
                WHERE
                    1
                    $category_id_filter
                    $parent_id_filter
                    $keyword_filter
                    $is_visible_filter
                    $exception_ids_filter
                ORDER BY $order
                    $sql_limit";

        $query = self::getDB()->placehold($query);
        self::getDB()->query($query);
        return self::getDB()->results();
    }

    function cmp_cats($a, $b){
        if ($a->tags_count == $b->tags_count) {
            return 0;
        }
        return ($a->tags_count < $b->tags_count) ? -1 : 1;
    }

    public function get_categories_with_tags($filter = array()){
        // По умолчанию
        $limit = 1000;
        $page = 1;
        $is_visible_filter = '';
        $order = 'c.position';

        // Инициализация тегов
        $tags_filter = '';
        $tags_tables = '';

        $q_index = 0;
        $where = array();
        $tables = array();

        $tags_groups = $this->tags->get_taggroups();

        foreach($tags_groups as $group)
        {
            if (isset($filter['tags'][$group->id]) && !empty($filter['tags'][$group->id]))
            {
                $q_index++;
                $where[] = "AND (tv$q_index.group_id = $group->id AND tc$q_index.tag_id in (".join(",",$filter['tags'][$group->id]).")) ";
                $tables[] = "INNER JOIN tags_categories tc$q_index ON c.id=tc$q_index.category_id
                                INNER JOIN tags_values tv$q_index ON tc$q_index.tag_id=tv$q_index.id ";
            }
        }

        foreach($tables as $t)
            $tags_tables .= $t;

        foreach($where as $w)
            $tags_filter .= $w;
        // Инициализация тегов (End)

        if(isset($filter['limit']))
            $limit = max(1, intval($filter['limit']));

        if(isset($filter['page']))
            $page = max(1, intval($filter['page']));

        $sql_limit = self::getDB()->placehold(' LIMIT ?, ? ', ($page-1)*$limit, $limit);

        if(!empty($filter['is_visible']))
            $is_visible_filter = self::getDB()->placehold('AND c.is_visible=?', intval($filter['is_visible']));

        $query = "SELECT
                    c.id,
                    c.parent_id,
                    c.url,
                    c.name,
                    c.frontend_name,
                    c.meta_title,
                    c.meta_keywords,
                    c.meta_description,
                    c.description,
                    c.description2,
                    c.position,
                    c.is_visible,
                    c.external_id,
                    c.set_id,
                    c.is_collapsed,
                    unix_timestamp(c.updated_dt) updated_dt,
                    c.modificators,
                    c.modificators_groups,
                    c.menu_level1_type,
                    c.menu_level1_columns,
                    c.menu_level1_width,
                    c.menu_level1_align,
                    c.menu_level1_use_banner,
                    c.menu_level1_banner_id,
                    c.menu_level1_column1_width,
                    c.menu_level1_column2_width,
                    c.menu_level1_column3_width,
                    c.menu_level1_column4_width,
                    c.menu_level1_column5_width,
                    c.menu_level1_column6_width,
                    c.menu_level2_columns,
                    c.menu_level2_column
                FROM __categories c
                    $tags_tables
                WHERE
                    1
                    $is_visible_filter
                    $tags_filter
                ORDER BY $order
                    $sql_limit";

        $query = self::getDB()->placehold($query);

        self::getDB()->query($query);

        $cats = self::getDB()->results();

        foreach($cats as $ind=>$cat)
        {
            $query = self::getDB()->placehold("SELECT COUNT(tag_id) as count FROM tags_categories WHERE category_id=?",$cat->id);
            self::getDB()->query($query);
            $cats[$ind]->tags_count = self::getDB()->result('count');
        }

        usort($cats, array("Categories", "cmp_cats"));

        return $cats;
    }

    private function calc_products_count(&$node, $is_visible = -1)
    {
        $tmp_cat = $this->get_category($node->id);
        $count_filter = array('category_id'=>$tmp_cat->children);
        if ($is_visible >= 0)
            $count_filter['is_visible'] = $is_visible;
        $node->products_count = (new Product())->count_products($count_filter);
        if (isset($tmp->subcategories))
            foreach($tmp->subcategories as $subnode)
                $this->calc_products_count($subnode);
    }

    public function get_categories_lazy_load_filter($filter = array()){
        if (!isset($filter['parent_id']))
            $filter['parent_id'] = 0;
        $temp_cats = $this->get_categories_filter($filter);
        foreach($temp_cats as $index=>$temp_cat)
            if (isset($filter['is_visible']))
                $this->calc_products_count($temp_cats[$index], $filter['is_visible']);
            else
                $this->calc_products_count($temp_cats[$index]);
        $result = array();
        foreach($temp_cats as $c)
        {
            $r = array();
            $r['id'] = $c->id;
            $r['url'] = $c->url.'/';
            /*добавление иерархичности*/
            /*$tmp_c = $this->get_category(intval($c->id));
            while($tmp_c->parent_id>0)
            {
                $tmp_c = $this->get_category(intval($tmp_c->parent_id));
                if (!$tmp_c)
                    break;
                $r['url'] = $tmp_c->url.'/'.$r['url'];
            }*/
            $r['title'] = $c->name;
            $r['products_count'] = $c->products_count;
            $tmp_c = $this->get_category(intval($c->id));
            if ($tmp_c && !empty($tmp_c->subcategories))
            {
                $r['folder'] = true;
                $r['lazy'] = true;
            }
            $result[] = $r;
        }
        return $result;
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
        $products_module = $this->furl->get_module_by_name('ProductsController');
        $category = $this->categories->get_category($id);
        while(true)
        {
            if ($category->id == $id)
                $return_str = $this->settings->breadcrumbs_selected_element_open_tag . $category->name . $this->settings->breadcrumbs_selected_element_close_tag . $return_str;
            else
                $return_str = $this->settings->breadcrumbs_element_open_tag . "<a href='".$this->config->root_url.$products_module->url.$category->url."/' data-type='category'>".$category->name."</a>" . $this->settings->breadcrumbs_element_close_tag . $return_str;
            if ($category->parent_id == 0)
                break;
            $category = $this->categories->get_category($category->parent_id);
        }
        $return_str = $this->settings->breadcrumbs_open_tag . $this->settings->breadcrumbs_first_element . $return_str . $this->settings->breadcrumbs_close_tag;
        return $return_str;
    }
}