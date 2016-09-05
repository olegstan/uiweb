<?php
namespace app\models\material;

use app\models\attachment\Attachment;
use app\models\attachment\AttachmentModule;
use app\models\image\Image;
use app\layer\LayerModel;
use \stdClass;
use core\db\Database;
use core\Collection;

class Material extends LayerModel
{
    protected $table = 'mc_materials';

    public $module_id = 28;

    public $images = [];
    public $gallery_mode = 'tile';

    public $title;
    public $name;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function afterSelect($material_category_url)
    {
        $this->title = $this->title ? $this->title : $this->name;
        if($this->url == '/'){
            $this->url = '/';
        }else{
            $this->url = $material_category_url . $this->url . $this->getCore()->config['material_url_end'];
        }
        return $this;
    }



    public static function images(Collection $collection, $rules = null)
    {
        switch($rules['type']){
            case 'one':
                $material = $collection->getResult();

                if($material){
                    $images = (new Image())
                        ->query()
                        ->select()
                        ->where('module_id = :module_id AND object_id = :object_id', [':module_id' => $material->module_id, ':object_id' => $material->id])
                        ->order('position')
                        ->execute()
                        ->all('materials-gallery', 'id')
                        ->getResult();

                    if($images){
                        $material->images = $images;
                    }
                }
                break;
            case 'all':

                break;
        }
    }

    public static function attachements(Collection $collection, $rules = null)
    {
        switch($rules['type']){
            case 'one':
                $material = $collection->getResult();

                if($material){
                    $module = (new AttachmentModule())
                        ->query()
                        ->select(['id'])
                        ->where('name = :name', [':name' => 'materials'])
                        ->limit()
                        ->execute()
                        ->one()
                        ->getResult();

                    if($module){
                        $attachments = (new Attachment())
                            ->query()
                            ->select()
                            ->where('module_id = :module_id AND object_id = :object_id', [':module_id' => $module->id, ':object_id' => $object_id])
                            ->order('position')
                            ->execute()
                            ->all(null, 'id')
                            ->getResult();
                        if($attachments){
                            $material->attachments = $attachments;
                        }
                    }
                }
                break;
            case 'all':

                break;
        }
    }





    // Список указателей на категории в дереве категорий (ключ = id категории)
    private $all_categories;
    // Дерево категорий
    private $categories_tree;

    private $all_menu_items;
    private $menu_items_tree;

    ###############################################
    ##  ФУНКЦИИ РАБОТЫ С КАТЕГОРИЯМИ МАТЕРИАЛОВ  ##
    ###############################################

    // Функция возвращает массив категорий
    public function get_categories($filter = array()){
        if(!isset($this->categories_tree))
            $this->init_categories();

        return $this->all_categories;
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
            $category['url'] = $this->furl->generate_url($category['name']);

            $this->db->query("SELECT count(id) as count from __materials_categories WHERE url=?", $category['url']);
            $k = $this->db->result('count');
            if ($k > 0)
                $url_exist = true;
        }

        $this->db->query("INSERT INTO __materials_categories SET ?%", $category);
        $id = $this->db->insert_id();
        $this->db->query("UPDATE __materials_categories SET position=id WHERE id=?", $id);
        if ($url_exist)
        {
            $category['url'] = $this->furl->generate_url($category['url'].'-'.$id);
            $this->db->query("UPDATE __materials_categories SET url=? WHERE id=?", $category['url'], $id);
        }
        $this->init_categories();
        return $id;
    }

    private function update_is_visible_subcategories($id, $is_visible){
        $category = $this->get_category($id);

        foreach($category->children as $c_id)
        {
            $query = $this->db->placehold("UPDATE __materials_categories SET is_visible=? WHERE id=?", $is_visible, $c_id);
            $this->db->query($query);
        }
    }

    // Изменение категории
    public function update_category($id, $category){
        $url_exist = false;
        $category = (array)$category;
        if (isset($category) && is_array($category) && isset($category['is_visible']))
            $this->update_is_visible_subcategories($id, $category['is_visible']);

        if(isset($category['url']) && empty($category['url']) && isset($category['name']))
        {
            $category['url'] = $this->furl->generate_url($category['name']);

            $this->db->query("SELECT count(id) as count from __materials_categories WHERE url=?", $category['url']);
            $k = $this->db->result('count');
            if ($k > 0)
                $category['url'] = $this->furl->generate_url($category['url'].'-'.$id);
        }

        if (!empty($category['url']))
        {
            $this->db->query("SELECT count(id) as count from __materials_categories WHERE url=? and id<>?", $category['url'], $id);
            $k = $this->db->result('count');
            if ($k > 0)
                $category['url'] = $this->furl->generate_url($category['url'].'-'.$id);
        }

        $query = $this->db->placehold("UPDATE __materials_categories SET ?% WHERE id=? LIMIT 1", $category, intval($id));
        $this->db->query($query);
        $this->init_categories();
        return $id;
    }

    // Удаление категории
    public function delete_category($id){
        if(!$category = $this->get_category(intval($id)))
            return false;
        foreach($category->children as $id)
        {
            if(!empty($id))
            {
                $query = $this->db->placehold("DELETE FROM __materials_categories WHERE id=? LIMIT 1", $id);
                $this->db->query($query);
                $query = $this->db->placehold("UPDATE __materials SET parent_id=0 WHERE parent_id=?", $id);
                $this->db->query($query);
                $this->init_categories();
            }
        }
        return true;
    }

    // Инициализация категорий, после которой категории будем выбирать из локальной переменной
    private function init_categories(){
        // Дерево категорий
        $tree = new stdClass();
        $tree->subcategories = array();

        // Указатели на узлы дерева
        $pointers = array();
        $pointers[0] = &$tree;
        $pointers[0]->path = array();

        // Выбираем все категории
        $query = $this->db->placehold("SELECT id, parent_id, url, name, title, meta_title, meta_keywords, meta_description, description, position, is_visible, show_date, css_class, collapsed, sort_type
                                 FROM __materials_categories ORDER BY parent_id, position");
        $this->db->query($query);
        $categories = $this->db->results();

        $finish = false;
        // Не кончаем, пока не кончатся категории, или пока ниодну из оставшихся некуда приткнуть
        while(!empty($categories)  && !$finish)
        {
            $flag = false;
            // Проходим все выбранные категории
            foreach($categories as $k=>$category)
            {
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

    // Функция возвращает количество категорий удовлетворяющих фильтру
    public function count_categories_filter($filter = array()){
        $keyword_filter = '';
        $is_visible_filter = '';

        if(isset($filter['keyword']))
        {
            $keywords = explode(' ', $filter['keyword']);
            foreach($keywords as $keyword)
                $keyword_filter .= $this->db->placehold('AND (c.name LIKE "%'.mysql_real_escape_string(trim($keyword)).'%" OR c.title LIKE "%'.mysql_real_escape_string(trim($keyword)).'%" OR c.meta_keywords LIKE "%'.mysql_real_escape_string(trim($keyword)).'%") ');
        }

        if(!empty($filter['is_visible']))
            $is_visible_filter = $this->db->placehold('AND c.is_visible=?', intval($filter['is_visible']));


        $query = "SELECT count(distinct c.id) count
                FROM __materials_categories c
                WHERE 1
                    $keyword_filter
                    $is_visible_filter ";

        $this->db->query($query);
        return $this->db->result('count');
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
        $order = 'c.position';
        $order_direction = '';

        if(isset($filter['limit']))
            $limit = max(1, intval($filter['limit']));

        if(isset($filter['page']))
            $page = max(1, intval($filter['page']));

        $sql_limit = $this->db->placehold(' LIMIT ?, ? ', ($page-1)*$limit, $limit);

        if(!empty($filter['id']))
            $category_id_filter = $this->db->placehold('AND c.id in(?@)', (array)$filter['id']);

        if(isset($filter['parent_id']))
            $parent_id_filter = $this->db->placehold('AND c.parent_id in(?@)', (array)$filter['parent_id']);

        if(!empty($filter['is_visible']))
            $is_visible_filter = $this->db->placehold('AND c.is_visible=?', intval($filter['is_visible']));

         if(!empty($filter['keyword']))
        {
            $keyword_filter = $this->db->placehold('AND (c.name LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%" OR c.title LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%")');
        }

        if (!empty($filter['sort']))
            switch($filter['sort'])
            {
                case 'name':
                    $order = 'c.name';
                    break;
                case 'position':
                    $order = 'c.position';
                    break;
                case 'newest':
                    $order = 'c.show_date';
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

        /*if (!empty($filter['order']))
            $order = $filter['order'];*/

        $query = "SELECT
                    c.id,
                    c.parent_id,
                    c.url,
                    c.name,
                    c.title,
                    c.meta_title,
                    c.meta_keywords,
                    c.meta_description,
                    c.description,
                    c.position,
                    c.is_visible,
                    c.show_date,
                    c.css_class,
                    c.collapsed
                FROM __materials_categories c
                WHERE
                    1
                    $category_id_filter
                    $parent_id_filter
                    $keyword_filter
                    $is_visible_filter
                ORDER BY $order $order_direction
                    $sql_limit";

        $query = $this->db->placehold($query);
        $this->db->query($query);
        return $this->db->results();
    }

    private function calc_materials_count(&$node){
        $tmp_cat = $this->get_category($node->id);
        $node->materials_count = $this->materials->count_materials(array('category_id'=>$tmp_cat->children));
        if (isset($tmp->subcategories))
            foreach($tmp->subcategories as $subnode)
                $this->calc_materials_count($subnode);
    }

    public function get_categories_lazy_load_filter($filter = array()){
        if (!isset($filter['parent_id']))
            $filter['parent_id'] = 0;
        $temp_cats = $this->get_categories_filter($filter);
        foreach($temp_cats as $index=>$temp_cat)
            $this->calc_materials_count($temp_cats[$index]);
        $result = array();
        foreach($temp_cats as $c)
        {
            $r = array();
            $r['id'] = $c->id;
            $r['title'] = $c->name;
            $r['materials_count'] = $c->materials_count;
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

    ####################################
    ##  ФУНКЦИИ РАБОТЫ С МАТЕРИАЛАМИ  ##
    ####################################

    public function get_material($id)
    {
        if(is_numeric($id))
            $where = $this->db->placehold(' WHERE id=? ', intval($id));
        else
            $where = $this->db->placehold(' WHERE url=? ', $id);

        $query = "SELECT id, url, unix_timestamp(created_dt) as date, name, title, parent_id, meta_title, meta_description, meta_keywords, description, body, position, is_visible, css_class, script_text, unix_timestamp(updated) updated, gallery_mode, gallery_tile_width, gallery_tile_height, gallery_list_width, gallery_list_height
                  FROM __materials $where LIMIT 1";

        $this->db->query($query);
        return $this->db->result();
    }

    public function get_materials($filter = array())
    {
        $category_id_filter = '';
        $is_visible_filter = '';
        $keyword_filter = '';
        $limit = 10000;
        $page = 1;
        $materials = array();
        $order = 'position';
        $order_direction = '';

        if(isset($filter['limit']))
            $limit = max(1, intval($filter['limit']));

        if(isset($filter['page']))
            $page = max(1, intval($filter['page']));

        $sql_limit = $this->db->placehold(' LIMIT ?, ? ', ($page-1)*$limit, $limit);

        if(isset($filter['category_id']))
            $category_id_filter = $this->db->placehold('AND parent_id in (?@)', (array)$filter['category_id']);

        if(isset($filter['is_visible']))
            $is_visible_filter = $this->db->placehold('AND is_visible = ?', intval($filter['is_visible']));

        if(!empty($filter['keyword']))
            $keyword_filter = $this->db->placehold('AND name LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%"');

        if (!empty($filter['sort']))
            switch($filter['sort'])
            {
                case 'position':
                    $order = 'position';
                    break;
                case 'newest':
                    $order = 'date';
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

        $query = "SELECT id, url, unix_timestamp(created_dt) as date, name, title, parent_id, meta_title, meta_description, meta_keywords, description, body, position, is_visible, css_class, script_text, unix_timestamp(updated) updated, gallery_mode, gallery_tile_width, gallery_tile_height, gallery_list_width, gallery_list_height
                  FROM __materials WHERE 1 $category_id_filter $is_visible_filter $keyword_filter ORDER BY $order $order_direction $sql_limit";

        $this->db->query($query);

        foreach($this->db->results() as $material)
            $materials[$material->id] = $material;

        return $materials;
    }

    public function count_materials($filter = array())
    {
        $category_id_filter = '';
        $is_visible_filter = '';

        if(isset($filter['category_id']))
            $category_id_filter = $this->db->placehold('AND parent_id in (?@)', (array)$filter['category_id']);

        if(isset($filter['is_visible']))
            $is_visible_filter = $this->db->placehold('AND is_visible = ?', intval($filter['is_visible']));


        $query = "SELECT count(distinct id) as count
                FROM __materials
                WHERE 1
                    $category_id_filter
                    $is_visible_filter";

        $this->db->query($query);
        return $this->db->result('count');
    }

    public function add_material($material)
    {
        $material = (array)$material;

        if (array_key_exists('parent_id', $material) && $material['parent_id'] == 0)
            $material['parent_id'] = null;

        $url_exist = false;

        if (empty($material['url']))
        {
            $material['url'] = $this->furl->generate_url($material['name']);

            $this->db->query("SELECT count(id) as count from __materials WHERE url=?", $material['url']);
            $k = $this->db->result('count');
            if ($k > 0)
                $url_exist = true;
        }

        $query = $this->db->placehold('INSERT INTO __materials SET ?%, updated=now()', $material);

        if(!$this->db->query($query))
            return false;

        $id = $this->db->insert_id();
        $this->db->query("UPDATE __materials SET position=id WHERE id=?", $id);
        if ($url_exist)
        {
            $material['url'] = $this->furl->generate_url($material['url'].'-'.$id);
            $this->db->query("UPDATE __materials SET url=? WHERE id=?", $category['url'], $id);
        }
        return $id;
    }

    public function update_material($id, $material)
    {
        $url_exist = false;
        $material = (array)$material;

        if (array_key_exists('parent_id', $material) && $material['parent_id'] == 0)
            $material['parent_id'] = null;

        if(isset($material['url']) && empty($material['url']) && isset($material['name']))
        {
            $material['url'] = $this->furl->generate_url($material['name']);
            $this->db->query("SELECT count(id) as count from __materials WHERE url=? and id<>?", $material['url'], $id);
            $k = $this->db->result('count');
            if ($k > 0)
                $material['url'] = $this->furl->generate_url($material['url'].'-'.$id);
        }

        if (!empty($material['url']))
        {
            $this->db->query("SELECT count(id) as count from __materials WHERE url=? and id<>?", $material['url'], $id);
            $k = $this->db->result('count');
            if ($k > 0)
                $material['url'] = $this->furl->generate_url($material['url'].'-'.$id);
        }

        $query = $this->db->placehold("UPDATE __materials SET ?%, updated=now() WHERE id in (?@)", $material, (array)$id);
        if(!$this->db->query($query))
            return false;
        return $id;
    }

    public function delete_material($id)
    {
        if(!empty($id))
        {
            // Удаляем изображения
            $images = $this->image->get_images('materials', $id);
            foreach($images as $i)
                $this->image->delete_image('materials', $id, $i->id);

            $query = $this->db->placehold("DELETE FROM __materials WHERE id=? LIMIT 1", intval($id));
            if($this->db->query($query))
                return true;
        }
        return false;
    }

    ########################################
    ##  ФУНКЦИИ РАБОТЫ С МЕНЮ МАТЕРИАЛОВ  ##
    ########################################

    public function get_menu($id)
    {
        if(is_numeric($id))
            $where = $this->db->placehold(' WHERE id=? ', intval($id));
        else
            $where = $this->db->placehold(' WHERE name=? ', $id);

        $query = "SELECT id, name, is_static, is_visible, css_class FROM __materials_menu $where LIMIT 1";

        $this->db->query($query);
        return $this->db->result();
    }

    public function get_menus($filter = array())
    {
        $static_filter = '';

        if (isset($filter['is_static']))
            $static_filter = $this->db->placehold("AND is_static = ?", intval($filter['is_static']));

        $query = "SELECT id, name, is_static, is_visible, css_class FROM __materials_menu WHERE 1 $static_filter ORDER BY id";
        $this->db->query($query);

        return $this->db->results();
    }

    public function add_menu($menu)
    {
        $query = $this->db->placehold('INSERT INTO __materials_menu SET ?%', $menu);

        if(!$this->db->query($query))
            return false;

        $id = $this->db->insert_id();
        return $id;
    }

    public function update_menu($id, $menu)
    {
        $query = $this->db->placehold('UPDATE __materials_menu SET ?% WHERE id in (?@)', $menu, (array)$id);
        if(!$this->db->query($query))
            return false;
        return $id;
    }

    public function delete_menu($id)
    {
        if(!empty($id))
        {
            $query = $this->db->placehold("DELETE FROM __materials_menu WHERE id=? LIMIT 1", intval($id));
            if($this->db->query($query))
                return true;
        }
        return false;
    }

    ####################################################
    ##  ФУНКЦИИ РАБОТЫ С ДРЕВОВИДНЫМ МЕНЮ МАТЕРИАЛОВ  ##
    ####################################################

    // Функция возвращает массив категорий
    public function get_menu_items($filter = array()){
        if(!isset($this->menu_items_tree))
            $this->init_menu_items();

        return $this->all_menu_items;
    }

    // Функция возвращает дерево категорий
    public function get_menu_items_tree(){
        if(!isset($this->menu_items_tree))
            $this->init_menu_items();

        return $this->menu_items_tree;
    }

    // Функция возвращает заданную категорию
    public function get_menu_item($id){
        if(!isset($this->all_menu_items))
            $this->init_menu_items();

        if(is_numeric($id) && array_key_exists(intval($id), $this->all_menu_items))
            return $menu_item = $this->all_menu_items[intval($id)];
        return false;
    }

    // Добавление категории
    public function add_menu_item($menu_item){
        $menu_item = (array)$menu_item;
        if (!isset($menu_item['object_type']))
            $menu_item['object_type'] = 'none';
        if (!isset($menu_item['object_id']))
            $menu_item['object_id'] = 0;
        if ($menu_item['is_main'] == 1)
            $this->db->query("UPDATE __materials_menu_items SET is_main=0");
        $this->db->query("INSERT INTO __materials_menu_items SET ?%", $menu_item);
        $id = $this->db->insert_id();
        $this->db->query("UPDATE __materials_menu_items SET position=id WHERE id=?", $id);
        $this->init_menu_items();
        return $id;
    }

    private function update_is_visible_subitems($id, $is_visible){
        $menu_item = $this->get_menu_item($id);

        foreach($menu_item->children as $c_id)
        {
            $query = $this->db->placehold("UPDATE __materials_menu_items SET is_visible=? WHERE id=?", $is_visible, $c_id);
            $this->db->query($query);
        }
    }

    // Изменение категории
    public function update_menu_item($id, $menu_item){
        $menu_item = (array)$menu_item;
        $old_menu_item = $this->get_menu_item($id);
        if ($old_menu_item->is_main && array_key_exists('is_main', $menu_item) && !$menu_item['is_main'])
            $menu_item['is_main'] = 1;
        if (!$old_menu_item->is_main && array_key_exists('is_main', $menu_item) && $menu_item['is_main'])
            $this->db->query("UPDATE __materials_menu_items SET is_main=0");
        if (isset($menu_item) && is_array($menu_item) && isset($menu_item['is_visible']))
            $this->update_is_visible_subitems($id, $menu_item['is_visible']);
        $query = $this->db->placehold("UPDATE __materials_menu_items SET ?% WHERE id=? LIMIT 1", $menu_item, intval($id));
        $this->db->query($query);
        $this->init_menu_items();
        return $id;
    }

    // Удаление категории
    public function delete_menu_item($id){
        if(!$menu_item = $this->get_menu_item(intval($id)))
            return false;
        foreach($menu_item->children as $id)
        {
            if(!empty($id))
            {
                $query = $this->db->placehold("DELETE FROM __materials_menu_items WHERE id=? LIMIT 1", $id);
                $this->db->query($query);
                $query = $this->db->placehold("UPDATE __materials_menu_items SET parent_id=0 WHERE parent_id=?", $id);
                $this->db->query($query);
                $this->init_menu_items();
            }
        }
        return true;
    }

    // Инициализация категорий, после которой категории будем выбирать из локальной переменной
    private function init_menu_items(){
        // Дерево категорий
        $tree = new stdClass();
        $tree->subitems = array();

        // Указатели на узлы дерева
        $pointers = array();
        $pointers[0] = &$tree;
        $pointers[0]->path = array();

        // Выбираем все категории
        $query = $this->db->placehold("SELECT id, name, menu_id, parent_id, is_visible, object_type, object_id, object_text, position, css_class, is_main
                                 FROM __materials_menu_items ORDER BY parent_id, position");
        $this->db->query($query);
        $menu_items = $this->db->results();

        $finish = false;
        // Не кончаем, пока не кончатся категории, или пока ниодну из оставшихся некуда приткнуть
        while(!empty($menu_items)  && !$finish)
        {
            $flag = false;
            // Проходим все выбранные категории
            foreach($menu_items as $k=>$menu_item)
            {
                if(isset($pointers[$menu_item->parent_id]))
                {
                    // В дерево категорий (через указатель) добавляем текущую категорию
                    $pointers[$menu_item->id] = $pointers[$menu_item->parent_id]->subitems[] = $menu_item;

                    // Путь к текущей категории
                    $curr = clone($pointers[$menu_item->id]);
                    $pointers[$menu_item->id]->path = array_merge((array)$pointers[$menu_item->parent_id]->path, array($curr));

                    // Убираем использованную категорию из массива категорий
                    unset($menu_items[$k]);
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

        $this->menu_items_tree = $tree->subitems;
        $this->all_menu_items = $pointers;
    }

    public function get_menu_items_filter($filter = array()){
        // По умолчанию
        $limit = 100;
        $page = 1;
        $keyword_filter = '';
        $main_filter = '';
        $order = 'position';

         if(!empty($filter['keyword']))
            $keyword_filter .= $this->db->placehold('AND name LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%"');

        if (isset($filter['is_main']))
            $main_filter = $this->db->placehold('AND is_main = ?', intval($filter['is_main']));

        if (!empty($filter['order']))
            $order = $filter['order'];

        $query = "SELECT
                    id, name, menu_id, parent_id, is_visible, object_type, object_id, object_text, position, css_class, is_main
                FROM __materials_menu_items
                WHERE
                    1
                    $keyword_filter $main_filter
                ORDER BY $order";

        $query = $this->db->placehold($query);
        $this->db->query($query);
        return $this->db->results();
    }

    /*
    *
    *    Вывод хлебных крошек
    *
    */
    public function get_breadcrumbs($id, $type)
    {
        $return_str = "";
        $id = intval($id);
        if (!$id)
            $return_str;
        if (empty($type))
            $type = 'material';
        $pages_module = $this->furl->get_module_by_name('PagesController');

        switch($type){
            case "material-category":
                $category = $this->materials->get_category($id);
                while(true)
                {
                    $item = new stdClass;
                    $item->object_type = 'material-category';
                    $item->object_id = $category->id;
                    if ($category->id == $id)
                        $return_str = $this->settings->breadcrumbs_selected_element_open_tag . $category->name . $this->settings->breadcrumbs_selected_element_close_tag . $return_str;
                    else
                        $return_str = $this->settings->breadcrumbs_element_open_tag . "<a href='".$this->makeurl($item)."' data-type='material-category'>".$category->name."</a>" . $this->settings->breadcrumbs_element_close_tag . $return_str;
                    if ($category->parent_id == 0)
                        break;
                    $category = $this->materials->get_category($category->parent_id);
                }
                $return_str = $this->settings->breadcrumbs_open_tag . $this->settings->breadcrumbs_first_element . $return_str . $this->settings->breadcrumbs_close_tag;
                break;
            case "material":
                $material = $this->materials->get_material($id);
                $return_str = $this->settings->breadcrumbs_selected_element_open_tag . $material->name . $this->settings->breadcrumbs_selected_element_close_tag;
                if ($material->parent_id > 0)
                {
                    $category = $this->materials->get_category($material->parent_id);
                    while(true)
                    {
                        $item = new stdClass;
                        $item->object_type = 'material-category';
                        $item->object_id = $category->id;
                        $return_str = $this->settings->breadcrumbs_element_open_tag . "<a href='".$this->makeurl($item)."' data-type='material-category'>".$category->name."</a>" . $this->settings->breadcrumbs_element_close_tag . $return_str;
                        if ($category->parent_id == 0)
                            break;
                        $category = $this->materials->get_category($category->parent_id);
                    }
                }
                 $return_str = $this->settings->breadcrumbs_open_tag . $this->settings->breadcrumbs_first_element . $return_str . $this->settings->breadcrumbs_close_tag;
                break;
        }
                
        return $return_str;
    }

    public function makeurl($item)
    {
        $return_str = "";
        if (isset($item->object_type))
        {
            //пункт меню
            $menu_item = $this->get_menu_item($item->id);
            switch($item->object_type){
                case "category":
                    $category = $this->categories->get_category($item->object_id);
                    $module = $this->furl->get_module_by_name('ProductsController');
                    $return_str = $this->config->root_url . $module->url . $category->url . '/';
                    break;
                case "product":
                    $product = $this->products->get_product($item->object_id);
                    $module = $this->furl->get_module_by_name('ProductController');
                    $return_str = $this->config->root_url . $module->url . $product->url;
                    break;
                case "href":
                    $return_str = $item->object_text;
                    break;
                case "none":
                    $return_str = "#";
                    break;
                case "material-category":
                    $category = $this->get_category($item->object_id);
                    $module = $this->furl->get_module_by_name('PagesController');
                    while(true)
                    {
                        $return_str = $category->url . '/' . $return_str;
                        if ($category->parent_id == 0)
                            break;
                        $category = $this->get_category($category->parent_id);
                    }
                    $return_str = $this->config->root_url . $module->url . $return_str;
                    break;
                case "material":
                    $material = $this->get_material($item->object_id);
                    $category = $this->get_category($material->parent_id);
                    $module = $this->furl->get_module_by_name('PagesController');
                    while(true)
                    {
                        $return_str = ($category->url ? $category->url . '/' : ''). $return_str;
                        if ($category->parent_id == 0)
                            break;
                        $category = $this->get_category($category->parent_id);
                    }

                    $return_str = $this->config->root_url . $module->url . $return_str . $material->url . '.html';
                    break;
                case "sitemap":
                    $module = $this->furl->get_module_by_name('SitemapViewController');
                    $return_str = $this->config->root_url . $module->url;
                    break;
            }
        }
        else
        {
            //материал
            $material = $this->get_material($item->id);
            $category = $this->get_category($material->parent_id);
            $module = $this->furl->get_module_by_name('PagesController');
            while(true)
            {
                $return_str = ($category->url ? $category->url . '/' : ''). $return_str;
                if ($category->parent_id == 0)
                    break;
                $category = $this->get_category($category->parent_id);
            }
            $return_str = $this->config->root_url . $module->url . $return_str . $material->url . '.html';
        }
        return $return_str;
    }

    public function makeurl_short($item)
    {
        $return_str = "";
        if (isset($item->object_type))
        {
            //пункт меню
            switch($item->object_type){
                case "category":
                    $category = $this->categories->get_category($item->object_id);
                    $module = $this->furl->get_module_by_name('ProductsController');
                    $return_str = $module->url . "category_id=".$item->object_id;
                    break;
                case "product":
                    $product = $this->products->get_product($item->object_id);
                    $module = $this->furl->get_module_by_name('ProductController');
                    $return_str = $module->url . $product->url;
                    break;
                case "href":
                    $return_str = $item->object_text;
                    break;
                case "none":
                    $return_str = "#";
                    break;
                case "material-category":
                    $category = $this->get_category($item->object_id);
                    $module = $this->furl->get_module_by_name('PagesController');
                    while(true)
                    {
                        $return_str = $category->url . '/' . $return_str;
                        if ($category->parent_id == 0)
                            break;
                        $category = $this->get_category($category->parent_id);
                    }
                    $return_str = $module->url . $return_str;
                    break;
                case "material":
                    $material = $this->get_material($item->object_id);
                    $category = $this->get_category($material->parent_id);
                    $module = $this->furl->get_module_by_name('PagesController');
                    while(true)
                    {
                        $return_str = ($category->url ? $category->url . '/' : ''). $return_str;
                        if ($category->parent_id == 0)
                            break;
                        $category = $this->get_category($category->parent_id);
                    }

                    $return_str = $module->url . $return_str . $material->url . '.html';
                    break;
                case "sitemap":
                    $module = $this->furl->get_module_by_name('SitemapViewController');
                    $return_str = $this->config->root_url . $module->url;
                    break;
            }
        }
        else
        {
            //материал
            $material = $this->get_material($item->id);
            $category = $this->get_category($material->parent_id);
            $module = $this->furl->get_module_by_name('PagesController');
            if (!empty($material) && !empty($category))
            while(true)
            {
                $return_str = ($category->url ? $category->url . '/' : ''). $return_str;
                if ($category->parent_id == 0)
                    break;
                $category = $this->get_category($category->parent_id);
            }
            $return_str = $module->url . $return_str . $material->url . '.html';
        }
        return $return_str;
    }
}