<?php
namespace app\models\tag;

use app\layer\LayerModel;

class Tag extends LayerModel
{
    protected $table = 'mc_tags';


######################################
######## Функция обработки тегов
######################################
    // Функция возвращает заданную группу тегов
    public function get_taggroup($id){
        $where = "";
        if (is_numeric($id))
            $where = self::getDB()->placehold("id=?", intval($id));
        else
            $where = self::getDB()->placehold("name=?", strval($id));

        // Выбираем группу
        $query = self::getDB()->placehold("SELECT id, name, is_enabled, position, show_flag, is_range, is_global, is_auto, prefix, postfix, help_text, mode, name_translit, show_in_frontend, numeric_sort, diapason_step, show_prefix_in_frontend_filter, show_in_product_list, export2yandex
                                 FROM __tags_groups WHERE $where");
        self::getDB()->query($query);
        $taggroup = self::getDB()->result();

        return $taggroup;
    }

    public function count_taggroups($filter = array()){
        $is_enabled_filter = '';
        $show_flag_filter = '';
        $range_filter = '';
        $global_filter = '';
        $auto_filter = '';
        $keyword_filter = '';
        $show_in_product_list_filter = '';

        if (isset($filter['is_enabled']))
            $is_enabled_filter = self::getDB()->placehold('AND is_enabled=?', intval($filter['is_enabled']));

        if (isset($filter['show_flag']))
            $show_flag_filter = self::getDB()->placehold('AND show_flag=?', intval($filter['show_flag']));

        if (isset($filter['is_range']))
            $range_filter = self::getDB()->placehold('AND is_range=?', intval($filter['is_range']));

        if (isset($filter['is_global']))
            $global_filter = self::getDB()->placehold('AND is_global=?', intval($filter['is_global']));

        if (isset($filter['is_auto']))
            $auto_filter = self::getDB()->placehold('AND is_auto=?', intval($filter['is_auto']));

        if (isset($filter['show_in_product_list']))
            $show_in_product_list_filter = self::getDB()->placehold('AND show_in_product_list=?', intval($filter['show_in_product_list']));

        if(!empty($filter['keyword']))
            $keyword_filter = self::getDB()->placehold('AND name LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%"');

        $query = "SELECT count(distinct g.id) as count
                FROM __tags_groups g
                WHERE 1
                    $is_enabled_filter
                    $show_flag_filter
                    $range_filter
                    $global_filter
                    $auto_filter
                    $show_in_product_list_filter
                    $keyword_filter";

        self::getDB()->query($query);
        return self::getDB()->result('count');
    }

    // Функция возвращает все группы тегов
    public function get_taggroups($filter = array()){
        // По умолчанию
        $limit = 5000;
        $page = 1;
        $is_enabled_filter = '';
        $show_flag_filter = '';
        $range_filter = '';
        $global_filter = '';
        $auto_filter = '';
        $show_in_product_list_filter = '';
        $keyword_filter = '';
        $mode_filter = '';
        $order = 'position';
        $order_direction = '';

        if(isset($filter['limit']))
            $limit = max(1, intval($filter['limit']));

        if(isset($filter['page']))
            $page = max(1, intval($filter['page']));

        $sql_limit = self::getDB()->placehold(' LIMIT ?, ? ', ($page-1)*$limit, $limit);

        if (isset($filter['is_enabled']))
            $is_enabled_filter = self::getDB()->placehold('AND is_enabled=?', intval($filter['is_enabled']));

        if (isset($filter['show_flag']))
            $show_flag_filter = self::getDB()->placehold('AND show_flag=?', intval($filter['show_flag']));

        if (isset($filter['is_range']))
            $range_filter = self::getDB()->placehold('AND is_range=?', intval($filter['is_range']));

        if (isset($filter['is_global']))
            $global_filter = self::getDB()->placehold('AND is_global=?', intval($filter['is_global']));

        if (isset($filter['is_auto']))
            $auto_filter = self::getDB()->placehold('AND is_auto=?', intval($filter['is_auto']));

        if (isset($filter['show_in_product_list']))
            $show_in_product_list_filter = self::getDB()->placehold('AND show_in_product_list=?', intval($filter['show_in_product_list']));

        if (!empty($filter['keyword']))
            $keyword_filter = self::getDB()->placehold('AND name LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%"');

        if (!empty($filter['mode']))
            $mode_filter = self::getDB()->placehold('AND mode=?', strval($filter['mode']));


        if (!empty($filter['sort']))
            switch($filter['sort'])
            {
                case 'name':
                    $order = 'name';
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

        // Выбираем все группы
        $query = self::getDB()->placehold("SELECT id, name, is_enabled, position, show_flag, is_range, is_global, is_auto, prefix, postfix, help_text, mode, name_translit, show_in_frontend, numeric_sort, diapason_step, show_prefix_in_frontend_filter, show_in_product_list, export2yandex
                                 FROM __tags_groups
                                 WHERE 1
                                    $is_enabled_filter
                                    $show_flag_filter
                                    $range_filter
                                    $global_filter
                                    $auto_filter
                                    $show_in_product_list_filter
                                    $keyword_filter
                                    $mode_filter
                                 ORDER BY $order $order_direction
                                 $sql_limit");
        self::getDB()->query($query);
        $taggroups = self::getDB()->results();

        return $taggroups;
    }

    // Добавление группы тегов
    public function add_taggroup($taggroup){
        $taggroup = (array)$taggroup;
        self::getDB()->query("INSERT INTO __tags_groups SET ?%", $taggroup);
        $id = self::getDB()->insert_id();
        self::getDB()->query("UPDATE __tags_groups SET position=id, name_translit=? WHERE id=?", $this->furl->generate_url($taggroup['name'] . $id), $id);
        return $id;
    }

    // Изменение группы тегов
    public function update_taggroup($id, $group){
        $group = (array) $group;
        if (isset($group['name']))
            $group['name_translit'] = $this->furl->generate_url($group['name'] . $id);
        $query = self::getDB()->placehold("UPDATE __tags_groups SET ?% WHERE id=? LIMIT 1", $group, intval($id));
        self::getDB()->query($query);
        return $id;
    }

    // Удаление группы тегов
    public function delete_taggroup($id){
        if(!$tag = $this->get_taggroup(intval($id)))
            return false;

        $query = self::getDB()->placehold("DELETE FROM __tags_groups WHERE id=? LIMIT 1", $id);
        self::getDB()->query($query);
        /**$query = self::getDB()->placehold("DELETE FROM __tags WHERE group_id=?", $id);
        self::getDB()->query($query);
        //Вообще неправильно ||
        //                   \/
        $query = self::getDB()->placehold("DELETE FROM __tags_products WHERE tag_id=?", $id);
        self::getDB()->query($query);
        $query = self::getDB()->placehold("DELETE FROM __tags_categories WHERE tag_id=?", $id);
        self::getDB()->query($query);**/
        return true;
    }

##############################################
######## Функция обработки значений тегов
##############################################
    // Функция возвращает значение тега
    public function get_tag($id){
        $where = "";
        if (is_numeric($id))
            $where = self::getDB()->placehold("t.id=?", intval($id));
        else
            $where = self::getDB()->placehold("t.name=?", strval($id));
        // Выбираем тег
        $query = self::getDB()->placehold("SELECT t.id, t.group_id, t.name, t.is_enabled, t.position, t.is_auto, t.is_popular, tg.prefix, tg.postfix, tg.show_prefix_in_frontend_filter, show_in_product_list
                                 FROM __tags t
                                    INNER JOIN __tags_groups tg ON t.group_id=tg.id
                        WHERE $where");
        self::getDB()->query($query);
        $tagvalue = self::getDB()->result();

        return $tagvalue;
    }

    // Добавление тега
    public function add_tag($tagvalue){
        $tagvalue = (array)$tagvalue;

        self::getDB()->query("INSERT INTO __tags SET ?%", $tagvalue);
        $id = self::getDB()->insert_id();
        self::getDB()->query("UPDATE __tags SET position=id WHERE id=?", $id);
        return $id;
    }

    // Изменение тега
    public function update_tag($id, $value){
        $query = self::getDB()->placehold("UPDATE __tags SET ?% WHERE id=? LIMIT 1", $value, intval($id));
        self::getDB()->query($query);
        return $id;
    }

    // Удаление тега
    public function delete_tag($id){
        if(!$tagvalue = $this->get_tag(intval($id)))
            return false;

        // Удаляем изображения
        $images = $this->image->get_images('tags', $id);
        foreach($images as $i)
            $this->image->delete_image('tags', $id, $i->id);

        $query = self::getDB()->placehold("DELETE FROM __tags WHERE id=? LIMIT 1", $id);
        self::getDB()->query($query);
        /**$query = self::getDB()->placehold("DELETE FROM __tags_products WHERE tag_id=?", $id);
        self::getDB()->query($query);
        $query = self::getDB()->placehold("DELETE FROM __tags_categories WHERE tag_id=?", $id);
        self::getDB()->query($query);
        //Тоже неправильно ||
        //                 \/
        $query = self::getDB()->placehold("DELETE FROM __tags_sets_tags WHERE tag_id=?", $id);
        self::getDB()->query($query);**/

        return true;
    }

    /**
    * Функция возвращает количество тегов
    * Возможные значения фильтра:
    * group_id - id группы или их массив
    * is_enabled - значение активности тегов
    */
    public function count_tags($filter = array()){
        $group_id_filter = '';
        $is_enabled_filter = '';
        $show_flag_filter = '';
        $keyword_filter = '';
        $is_auto_filter = '';
        $is_popular_filter = '';
        $name_filter = '';

        if(!empty($filter['group_id']))
            $group_id_filter = self::getDB()->placehold('INNER JOIN __tags_groups tg ON t.group_id = tg.id AND tg.id in (?@)', (array)$filter['group_id']);

        if(!empty($filter['is_enabled']))
            $is_enabled_filter = self::getDB()->placehold('AND t.is_enabled=?', intval($filter['is_enabled']));

        if (!empty($filter['show_flag']))
            $show_flag_filter = self::getDB()->placehold('AND t.show_flag=?', intval($filter['show_flag']));

        if (isset($filter['is_auto']))
            $is_auto_filter = self::getDB()->placehold('AND t.is_auto=?', intval($filter['is_auto']));

        if (isset($filter['is_popular']))
            $is_popular_filter = self::getDB()->placehold('AND t.is_popular=?', intval($filter['is_popular']));

        if(!empty($filter['keyword']))
            $keyword_filter = self::getDB()->placehold('AND t.name LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%"');

        if (isset($filter['name']))
            $name_filter = self::getDB()->placehold('AND t.name=?', $filter['name']);

        $query = "SELECT count(distinct t.id) as count
                FROM __tags t
                $group_id_filter
                WHERE 1
                    $is_enabled_filter
                    $show_flag_filter
                    $is_auto_filter
                    $is_popular_filter
                    $keyword_filter
                    $name_filter";

        self::getDB()->query($query);
        return self::getDB()->result('count');
    }

    /**
    * Функция возвращает теги
    * Возможные значения фильтра:
    * id - id тега или их массив
    * group_id - id группы тегов или их массив
    * show_flag - флаг отображения
    * page - текущая страница, integer
    * limit - количество тегов на странице, integer
    */
    public function get_tags($filter = array()){
        // По умолчанию
        $limit = 5000;
        $page = 1;
        $group_id_filter = '';
        $tag_id_filter = '';
        $is_enabled_filter = '';
        $show_flag_filter = '';
        $is_auto_filter = '';
        $is_popular_filter = '';
        $keyword_filter = '';
        $name_filter = '';

        $category_tables = '';
        $category_id_filter = '';
        $products_tables = '';
        $products_filter = '';
        $tags_tables = '';
        $tags_filter = '';

        $order = 't.position';
        $order_direction = '';

        if(isset($filter['limit']))
            $limit = max(1, intval($filter['limit']));

        if(isset($filter['page']))
            $page = max(1, intval($filter['page']));

        $sql_limit = self::getDB()->placehold(' LIMIT ?, ? ', ($page-1)*$limit, $limit);

        if(!empty($filter['id']))
            $tag_id_filter = self::getDB()->placehold('AND t.id in(?@)', (array)$filter['id']);

        if(!empty($filter['group_id']))
            $group_id_filter = self::getDB()->placehold('AND tg.id in(?@)', (array)$filter['group_id']);

        if(!empty($filter['is_enabled']))
            $is_enabled_filter = self::getDB()->placehold('AND t.is_enabled=?', intval($filter['is_enabled']));

        if (isset($filter['show_flag']))
            $show_flag_filter = self::getDB()->placehold('AND t.show_flag=?', intval($filter['show_flag']));

        if (isset($filter['is_auto']))
            $is_auto_filter = self::getDB()->placehold('AND t.is_auto=?', intval($filter['is_auto']));

        if (isset($filter['is_popular']))
            $is_popular_filter = self::getDB()->placehold('AND t.is_popular=?', intval($filter['is_popular']));

        if(!empty($filter['keyword']))
            $keyword_filter = self::getDB()->placehold('AND t.name LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%"');

        if(isset($filter['name']))
            $name_filter = self::getDB()->placehold('AND t.name = ?', mysql_real_escape_string(trim($filter['name'])));

        if (isset($filter['category_id']))
        {
            $category_id_filter = self::getDB()->placehold('AND pc.category_id in (?@)', (array)$filter['category_id']);
            $category_tables = "INNER JOIN __tags_products tp ON t.id = tp.tag_id
                INNER JOIN __products_categories pc ON tp.product_id = pc.product_id";

            if (isset($filter['products_is_visible']))
                $products_tables = "INNER JOIN __products p ON pc.product_id = p.id";
        }
        else
        {
            if (isset($filter['products_is_visible']))
                $products_tables = "INNER JOIN __tags_products tp ON t.id = tp.tag_id
                    INNER JOIN __products p ON tp.product_id = p.id";
        }

        if (isset($filter['products_is_visible']))
            $products_filter = self::getDB()->placehold('AND p.is_visible=?', intval($filter['products_is_visible']));

        if(isset($filter['products_in_stock']))
            $products_filter .= self::getDB()->placehold(' AND (SELECT 1 FROM __variants pv WHERE pv.product_id=p.id AND pv.price>0 AND (pv.stock IS NULL OR pv.stock<>0) LIMIT 1) = ?', intval($filter['products_in_stock']));

        if (isset($filter['tags']))
        {
            $q_index = 0;
            $tables = array();
            $where = array();

            foreach($filter['tags'] as $group_id=>$tags)
            {
                $q_index++;
                $tables[] = "INNER JOIN __tags_products tp$q_index ON pc.product_id=tp$q_index.product_id
                    INNER JOIN __tags t$q_index ON tp$q_index.tag_id=t$q_index.id ";
                if (is_array($tags) && !empty($tags))
                    $where[] = "AND (t$q_index.group_id = $group_id AND tp$q_index.tag_id in (".join(",",$tags).")) ";
                else
                    $where[] = self::getDB()->placehold("AND (t$q_index.group_id = $group_id AND CONVERT(?,DECIMAL(10,0))<=CONVERT(replace(t$q_index.name,' ',''),DECIMAL(10,0)) AND CONVERT(replace(t$q_index.name,' ',''),DECIMAL(10,0))<=CONVERT(?,DECIMAL(10,0)))", $tags->from, $tags->to);
            }
            foreach($tables as $t)
                $tags_tables .= $t;

            foreach($where as $w)
                $tags_filter .= $w;
        }

        if (!empty($filter['sort']))
            switch($filter['sort'])
            {
                case 'name':
                    if (!empty($filter['numeric_sort']))
                        $order = 'CONVERT(t.name, UNSIGNED)';
                    else
                        $order = 't.name';
                    break;
                case 'popular':
                    $order = 't.position';
                    break;
                case 'position':
                    $order = 't.position';
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

        $query = "SELECT
                    distinct t.id,
                    t.group_id,
                    t.name,
                    t.is_enabled,
                    t.position,
                    t.is_auto,
                    t.is_popular,
                    tg.prefix,
                    tg.postfix,
                    tg.show_prefix_in_frontend_filter,
                    tg.show_in_product_list
                FROM __tags t
                    INNER JOIN __tags_groups tg ON t.group_id = tg.id
                    $category_tables
                    $products_tables
                    $tags_tables
                WHERE
                    1
                    $tag_id_filter
                    $category_id_filter
                    $products_filter
                    $group_id_filter
                    $is_enabled_filter
                    $show_flag_filter
                    $is_auto_filter
                    $is_popular_filter
                    $keyword_filter
                    $name_filter
                    $tags_filter
                ORDER BY $order $order_direction
                    $sql_limit";

        $query = self::getDB()->placehold($query);

        self::getDB()->query($query);

        return self::getDB()->results();
    }

    public function get_tags_min_value($filter = array()){
        // По умолчанию
        $limit = 5000;
        $page = 1;
        $group_id_filter = '';
        $tag_id_filter = '';
        $is_enabled_filter = '';
        $show_flag_filter = '';
        $keyword_filter = '';
        $name_filter = '';

        $category_tables = '';
        $category_id_filter = '';
        $tags_tables = '';
        $tags_filter = '';

        $order = 't.position';
        $order_direction = '';

        if(isset($filter['limit']))
            $limit = max(1, intval($filter['limit']));

        if(isset($filter['page']))
            $page = max(1, intval($filter['page']));

        $sql_limit = self::getDB()->placehold(' LIMIT ?, ? ', ($page-1)*$limit, $limit);

        if(!empty($filter['id']))
            $tag_id_filter = self::getDB()->placehold('AND t.id in(?@)', (array)$filter['id']);

        if(!empty($filter['group_id']))
            $group_id_filter = self::getDB()->placehold('AND tg.id in(?@)', (array)$filter['group_id']);

        if(!empty($filter['is_enabled']))
            $is_enabled_filter = self::getDB()->placehold('AND t.is_enabled=?', intval($filter['is_enabled']));

        if (isset($filter['show_flag']))
            $show_flag_filter = self::getDB()->placehold('AND t.show_flag=?', intval($filter['show_flag']));

        if(!empty($filter['keyword']))
            $keyword_filter = self::getDB()->placehold('AND t.name LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%"');

        if(isset($filter['name']))
            $name_filter = self::getDB()->placehold('AND t.name = ?', mysql_real_escape_string(trim($filter['name'])));

        if (isset($filter['category_id']))
        {
            $category_id_filter = self::getDB()->placehold('AND pc.category_id in (?@)', (array)$filter['category_id']);
            $category_tables = "INNER JOIN __tags_products tp ON t.id = tp.tag_id
                INNER JOIN __products_categories pc ON tp.product_id = pc.product_id";
        }

        if (isset($filter['tags']))
        {
            $q_index = 0;
            $tables = array();
            $where = array();

            foreach($filter['tags'] as $group_id=>$tags)
            {
                $q_index++;
                $tables[] = "INNER JOIN __tags_products tp$q_index ON pc.product_id=tp$q_index.product_id
                    INNER JOIN __tags t$q_index ON tp$q_index.tag_id=t$q_index.id ";
                if (is_array($tags) && !empty($tags))
                    $where[] = "AND (t$q_index.group_id = $group_id AND tp$q_index.tag_id in (".join(",",$tags).")) ";
                else
                    $where[] = self::getDB()->placehold("AND (t$q_index.group_id = $group_id AND CONVERT(?,DECIMAL(10,0))<=CONVERT(replace(t$q_index.name,' ',''),DECIMAL(10,0)) AND CONVERT(replace(t$q_index.name,' ',''),DECIMAL(10,0))<=CONVERT(?,DECIMAL(10,0)))", $tags->from, $tags->to);
            }
            foreach($tables as $t)
                $tags_tables .= $t;

            foreach($where as $w)
                $tags_filter .= $w;
        }

        if (!empty($filter['sort']))
            switch($filter['sort'])
            {
                case 'name':
                    $order = 't.name';
                    break;
                case 'popular':
                    $order = 't.position';
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

        $query = "SELECT
                    MIN(CONVERT(replace(t.name,' ',''),DECIMAL(10,0))) as min_value
                FROM __tags t
                    INNER JOIN __tags_groups tg ON t.group_id = tg.id
                    $category_tables
                    $tags_tables
                WHERE
                    1
                    $tag_id_filter
                    $category_id_filter
                    $group_id_filter
                    $is_enabled_filter
                    $show_flag_filter
                    $keyword_filter
                    $name_filter
                    $tags_filter
                ORDER BY $order $order_direction
                    $sql_limit";

        $query = self::getDB()->placehold($query);
        self::getDB()->query($query);

        return self::getDB()->result('min_value');
    }

    public function get_tags_max_value($filter = array()){
        // По умолчанию
        $limit = 5000;
        $page = 1;
        $group_id_filter = '';
        $tag_id_filter = '';
        $is_enabled_filter = '';
        $show_flag_filter = '';
        $keyword_filter = '';
        $name_filter = '';

        $category_tables = '';
        $category_id_filter = '';
        $tags_tables = '';
        $tags_filter = '';

        $order = 't.position';
        $order_direction = '';

        if(isset($filter['limit']))
            $limit = max(1, intval($filter['limit']));

        if(isset($filter['page']))
            $page = max(1, intval($filter['page']));

        $sql_limit = self::getDB()->placehold(' LIMIT ?, ? ', ($page-1)*$limit, $limit);

        if(!empty($filter['id']))
            $tag_id_filter = self::getDB()->placehold('AND t.id in(?@)', (array)$filter['id']);

        if(!empty($filter['group_id']))
            $group_id_filter = self::getDB()->placehold('AND tg.id in(?@)', (array)$filter['group_id']);

        if(!empty($filter['is_enabled']))
            $is_enabled_filter = self::getDB()->placehold('AND t.is_enabled=?', intval($filter['is_enabled']));

        if (isset($filter['show_flag']))
            $show_flag_filter = self::getDB()->placehold('AND t.show_flag=?', intval($filter['show_flag']));

        if(!empty($filter['keyword']))
            $keyword_filter = self::getDB()->placehold('AND t.name LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%"');

        if(isset($filter['name']))
            $name_filter = self::getDB()->placehold('AND t.name = ?', mysql_real_escape_string(trim($filter['name'])));

        if (isset($filter['category_id']))
        {
            $category_id_filter = self::getDB()->placehold('AND pc.category_id in (?@)', (array)$filter['category_id']);
            $category_tables = "INNER JOIN __tags_products tp ON t.id = tp.tag_id
                INNER JOIN __products_categories pc ON tp.product_id = pc.product_id";
        }

        if (isset($filter['tags']))
        {
            $q_index = 0;
            $tables = array();
            $where = array();

            foreach($filter['tags'] as $group_id=>$tags)
            {
                $q_index++;
                $tables[] = "INNER JOIN __tags_products tp$q_index ON pc.product_id=tp$q_index.product_id
                    INNER JOIN __tags t$q_index ON tp$q_index.tag_id=t$q_index.id ";
                if (is_array($tags) && !empty($tags))
                    $where[] = "AND (t$q_index.group_id = $group_id AND tp$q_index.tag_id in (".join(",",$tags).")) ";
                else
                    $where[] = self::getDB()->placehold("AND (t$q_index.group_id = $group_id AND CONVERT(?,DECIMAL(10,0))<=CONVERT(replace(t$q_index.name,' ',''),DECIMAL(10,0)) AND CONVERT(replace(t$q_index.name,' ',''),DECIMAL(10,0))<=CONVERT(?,DECIMAL(10,0)))", $tags->from, $tags->to);
            }
            foreach($tables as $t)
                $tags_tables .= $t;

            foreach($where as $w)
                $tags_filter .= $w;
        }

        if (!empty($filter['sort']))
            switch($filter['sort'])
            {
                case 'name':
                    $order = 't.name';
                    break;
                case 'popular':
                    $order = 't.position';
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

        $query = "SELECT
                    MAX(CONVERT(replace(t.name,' ',''),DECIMAL(10,0))) as max_value
                FROM __tags t
                    INNER JOIN __tags_groups tg ON t.group_id = tg.id
                    $category_tables
                    $tags_tables
                WHERE
                    1
                    $tag_id_filter
                    $category_id_filter
                    $group_id_filter
                    $is_enabled_filter
                    $show_flag_filter
                    $keyword_filter
                    $name_filter
                    $tags_filter
                ORDER BY $order $order_direction
                    $sql_limit";

        $query = self::getDB()->placehold($query);
        self::getDB()->query($query);

        return self::getDB()->result('max_value');
    }

    //Функция проверяет существование тега
    //Возвращает true если тег есть и false иначе
    public function tag_exists($tag_name){
        $query = self::getDB()->placehold("SELECT * FROM __tags WHERE name=?", $tag_name);
        self::getDB()->query($query);
        $r = self::getDB()->result();
        return $r!==false;
    }

    //Функция удаляет все теги у товара
    public function delete_product_tags($product_id){
        $query = self::getDB()->placehold("DELETE FROM __tags_products WHERE product_id=?", $product_id);
        self::getDB()->query($query);
        return true;
    }

    //Функция удаляет тег у товара
    public function delete_product_tag($product_id, $tag_id){
        $query = self::getDB()->placehold("DELETE FROM __tags_products WHERE product_id=? AND tag_id=?", $product_id, intval($tag_id));
        self::getDB()->query($query);
        return true;
    }

    //Функция добавляет тег для товара
    public function add_product_tag($product_id, $tag_id){
        $query = self::getDB()->placehold("INSERT IGNORE INTO __tags_products(product_id, tag_id) VALUES(?,?)", $product_id, intval($tag_id));
        self::getDB()->query($query);
        return true;
    }


    //Функция возвращает теги товара
    public function get_product_tags_with_products_count($product_id){
        $query = self::getDB()->placehold("SELECT distinct t.id, t.group_id, t.name, t.is_enabled, t.position, t.is_auto, t.is_popular, tg.prefix, tg.postfix, count(distinct tp2.product_id) as products_count
                                FROM __tags t
                                    INNER JOIN __tags_groups tg ON t.group_id=tg.id
                                    INNER JOIN __tags_products tp ON t.id=tp.tag_id
                                    INNER JOIN __tags_products tp2 ON t.id=tp2.tag_id
                                    INNER JOIN __products p ON tp2.product_id=p.id AND p.is_visible=1
                                WHERE tp.product_id=?
                                GROUP BY t.id
                                ORDER BY tg.position, t.name", $product_id);
        self::getDB()->query($query);
        return self::getDB()->results();
    }

    //Функция удаляет все теги у категории
    public function delete_category_tags($category_id){
        $query = self::getDB()->placehold("DELETE FROM __tags_categories WHERE category_id=?", $category_id);
        self::getDB()->query($query);
        return true;
    }

    //Функция добавляет тег для категории
    public function add_category_tag($category_id, $tag_id){
        $query = self::getDB()->placehold("INSERT INTO __tags_categories(category_id, tag_id) VALUES(?,?)", $category_id, $tag_id);
        self::getDB()->query($query);
        return true;
    }

    //Функция возвращает теги категории
    public function get_category_tags($category_id){
        $query = self::getDB()->placehold("SELECT t.id, t.group_id, t.name, t.is_enabled, t.position, t.is_auto, t.is_popular, tg.prefix, tg.postfix, tg.show_prefix_in_frontend_filter, tg.show_in_product_list
                                        FROM __tags t
                                            INNER JOIN __tags_groups tg ON t.group_id=tg.id
                                            INNER JOIN __tags_categories tc ON t.id=tc.tag_id
                                        WHERE tc.category_id=?", $category_id);
        self::getDB()->query($query);
        return self::getDB()->results();
    }

    //Функция возвращает количество товаров для тега
    public function get_products_count_by_tag($filter = array()){
        $tag_id_filter = "";
        $is_visible_filter = "";
        if (isset($filter['tag_id']))
            $tag_id_filter = self::getDB()->placehold('AND tp.tag_id=?', intval($filter['tag_id']));
        if (isset($filter['is_visible']))
            $is_visible_filter = self::getDB()->placehold('AND p.is_visible=?', intval($filter['is_visible']));

        $query = self::getDB()->placehold("SELECT count(product_id) as count
                                        FROM __tags_products tp
                                            INNER JOIN __products p ON tp.product_id=p.id
                                        WHERE 1
                                            $tag_id_filter
                                            $is_visible_filter");
        self::getDB()->query($query);
        return self::getDB()->result('count');
    }

##########################################################
######## Функция обработки альтернативных значений тегов
##########################################################
    /**
    * Функция возвращает альтернативные варианты значения тега
    * @param    $filter
    * @retval    array
    */
    public function get_alternative_values($filter = array()){
        $tag_id_filter = '';
        $alternative_id_filter = '';

        if(!empty($filter['tag_id']))
            $tag_id_filter = self::getDB()->placehold('AND v.tag_id in(?@)', (array)$filter['tag_id']);

        if(!empty($filter['id']))
            $alternative_id_filter = self::getDB()->placehold('AND v.id in(?@)', (array)$filter['id']);

        if(!$tag_id_filter && !$alternative_id_filter)
            return array();

        $query = self::getDB()->placehold("SELECT v.id, v.tag_id, v.name
                    FROM __tags_alternative v
                    WHERE
                        1
                        $tag_id_filter
                        $alternative_id_filter");

        self::getDB()->query($query);
        return self::getDB()->results();
    }

    public function get_alternative_value($id){
        if(empty($id))
            return false;

        $query = self::getDB()->placehold("SELECT v.id, v.tag_id , v.name
                    FROM __tags_alternative v WHERE id=?
                    LIMIT 1", $id);

        self::getDB()->query($query);
        $variant = self::getDB()->result();
        return $variant;
    }

    public function update_alternative_value($id, $variant){
        $query = self::getDB()->placehold("UPDATE __tags_alternative SET ?% WHERE id=? LIMIT 1", $variant, intval($id));
        self::getDB()->query($query);
        return $id;
    }

    public function add_alternative_value($variant){
        $query = self::getDB()->placehold("INSERT INTO __tags_alternative SET ?%", $variant);
        self::getDB()->query($query);
        return self::getDB()->insert_id();
    }

    public function delete_alternative_value($id){
        if(!empty($id))
        {
            $query = self::getDB()->placehold("DELETE FROM __tags_alternative WHERE id = ? LIMIT 1", intval($id));
            self::getDB()->query($query);
        }
    }

    ########### Слияние тегов
    public function merge_tags($parent_tag_id, $child_tag_id){
        //Перенесем альтернативные значения к родительскому тегу
        $query = self::getDB()->placehold("UPDATE __tags_alternative SET tag_id=? WHERE tag_id=?", $parent_tag_id, $child_tag_id);
        self::getDB()->query($query);

        //Назначим всем товарам со старым тегам новый тег
        $query = self::getDB()->placehold("UPDATE __tags_products SET tag_id=? WHERE tag_id=?", $parent_tag_id, $child_tag_id);
        self::getDB()->query($query);

        //Назначим всем категориям со старым тегам новый тег
        $query = self::getDB()->placehold("UPDATE __tags_categories SET tag_id=? WHERE tag_id=?", $parent_tag_id, $child_tag_id);
        self::getDB()->query($query);

        //Добавим сливаемый тег как альтернативное значение к родительскому тегу
        $tag_child = $this->get_tagvalue($child_tag_id);
        if (!isset($tag_child))
            return false;

        $alternate_value->tag_id = $parent_tag_id;
        $alternate_value->name = $tag_child->name;
        $alternate_value = $this->add_alternative_value($alternate_value);
        if (!isset($alternate_value))
            return false;

        //Удалим старый тег
        if (!$this->delete_tagvalue($child_tag_id))
            return false;

        return true;
    }

##########################################################
######## Функции обработки наборов групп тегов TAGS_SETS
##########################################################
    public function get_tags_set($id){
        $where = "";
        if (is_numeric($id))
            $where = self::getDB()->placehold("AND id=?", intval($id));
        else
            $where = self::getDB()->placehold("AND name=?", strval($id));
        // Выбираем набор тегов
        $query = self::getDB()->placehold("SELECT id, name, is_visible, position FROM __tags_sets WHERE 1 $where LIMIT 1");
        self::getDB()->query($query);
        $tags_set = self::getDB()->result();

        return $tags_set;
    }

    public function count_tags_sets($filter = array()){
        $is_visible_filter = '';
        $keyword_filter = '';
        $name_filter = '';

        if (isset($filter['is_visible']))
            $is_visible_filter = self::getDB()->placehold('AND is_visible=?', intval($filter['is_visible']));

        if(!empty($filter['keyword']))
            $keyword_filter = self::getDB()->placehold('AND name LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%"');

        if (!empty($filter['name']))
            $name_filter = self::getDB()->placehold('AND name=?', $filter['name']);

        $query = "SELECT count(distinct id) as count
                FROM __tags_sets
                WHERE 1
                    $is_visible_filter
                    $keyword_filter
                    $name_filter";

        self::getDB()->query($query);
        return self::getDB()->result('count');
    }

    public function get_tags_sets($filter = array()){
        $limit = 5000;
        $page = 1;
        $is_visible_filter = "";
        $keyword_filter = '';
        $order = 'position';
        $order_direction = '';

        if(isset($filter['limit']))
            $limit = max(1, intval($filter['limit']));

        if(isset($filter['page']))
            $page = max(1, intval($filter['page']));

        $sql_limit = self::getDB()->placehold(' LIMIT ?, ? ', ($page-1)*$limit, $limit);

        if (isset($filter['is_visible']))
            $is_visible_filter = self::getDB()->placehold('AND is_visible=?', intval($filter['is_visible']));

        if(!empty($filter['keyword']))
            $keyword_filter .= self::getDB()->placehold('AND name LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%"');

        if (!empty($filter['sort']))
            switch($filter['sort'])
            {
                case 'name':
                    $order = 'name';
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

        // Выбираем наборы тегов
        $query = self::getDB()->placehold("SELECT id, name, is_visible, position FROM __tags_sets WHERE 1 $is_visible_filter $keyword_filter ORDER BY $order $order_direction $sql_limit");
        self::getDB()->query($query);
        $tags_sets = self::getDB()->results();
        return $tags_sets;
    }

    public function get_tags_set_tags($filter = array()){
        $set_id_filter = "";
        $in_filter_filter = "";
        $auto_filter = "";
        $category_id_filter = "";
        $category_inner_filter = "";
        $mode_filter = "";

        if (isset($filter['set_id']))
            $set_id_filter = self::getDB()->placehold("AND tst.set_id=?", intval($filter['set_id']));

        if (isset($filter['in_filter']))
            $in_filter_filter = self::getDB()->placehold('AND tst.in_filter=?', intval($filter['in_filter']));

        if (isset($filter['is_auto']))
            $auto_filter = self::getDB()->placehold('AND tg.is_auto=?', intval($filter['is_auto']));

        if (isset($filter['category_id']))
        {
            $category_inner_filter = self::getDB()->placehold("LEFT JOIN __categories c ON tst.set_id=c.set_id");
            $category_id_filter = self::getDB()->placehold("AND c.id in (?@)", (array)$filter['category_id']);
        }

        if (isset($filter['mode']))
            $mode_filter = self::getDB()->placehold("AND tg.mode=?", strval($filter['mode']));

        $query = self::getDB()->placehold("SELECT distinct tst.tag_id, tst.set_id, tst.in_filter, tst.default_expand, tg.name, tg.prefix, tg.postfix, tg.name_translit, tg.mode, tg.help_text, tg.id as group_id, tg.show_in_frontend, tg.numeric_sort, tg.diapason_step, tg.show_prefix_in_frontend_filter, tg.show_in_product_list
                                    FROM __tags_sets_tags tst
                                        INNER JOIN __tags_groups tg ON tst.tag_id=tg.id
                                        $category_inner_filter
                                        WHERE 1 $set_id_filter $in_filter_filter $auto_filter $category_id_filter $mode_filter
                                        ORDER BY tst.position");
        self::getDB()->query($query);
        return self::getDB()->results();
    }

    public function add_tags_set($tags_set){
        $query = self::getDB()->placehold("INSERT INTO __tags_sets SET ?%", (array)$tags_set);
        self::getDB()->query($query);
        $id = self::getDB()->insert_id();
        $query = self::getDB()->placehold("UPDATE __tags_sets SET position=id WHERE id=? LIMIT 1", $id);
        self::getDB()->query($query);
        return $id;
    }

    public function update_tags_set($id, $tags_set){
        $query = self::getDB()->placehold("UPDATE __tags_sets SET ?% WHERE id in(?@) LIMIT ?", (array)$tags_set, (array)$id, count((array)$id));
        self::getDB()->query($query);
        return $id;
    }

    public function update_tags_set_tags($id, $tags, $in_filters){
        $id = intval($id);
        $query = self::getDB()->placehold("DELETE FROM __tags_sets_tags WHERE set_id=?", $id);
        self::getDB()->query($query);

        if(is_array($features))
        {
            $values = array();
            $pos = 0;
            foreach($features as $feature)
            {
                $values[] = "($id , ".intval($feature).", ".intval($pos).", ".intval($in_filters[$pos]).", ".intval($expanded[$pos]).")";
                $pos++;
            }

            $query = self::getDB()->placehold("INSERT INTO __tags_sets_tags (set_id, tag_id, position, in_filter, default_expand) VALUES ".implode(', ', $values));
            self::getDB()->query($query);
        }
    }

    // Удаление набора групп тегов
    public function delete_tags_set($id){
        if(!$tag = $this->get_tags_set(intval($id)))
            return false;

        $query = self::getDB()->placehold("DELETE FROM __tags_sets WHERE id=? LIMIT 1", $id);
        self::getDB()->query($query);
        /**$query = self::getDB()->placehold("DELETE FROM __tags_sets_tags WHERE set_id=?", $id);
        self::getDB()->query($query);**/

        return true;
    }

    // Удаление набора групп тегов
    public function delete_tags_set_tags($id){
        if(!$tag = $this->get_tags_set(intval($id)))
            return false;
        $query = self::getDB()->placehold("DELETE FROM __tags_sets_tags WHERE set_id=?", $id);
        self::getDB()->query($query);
        return true;
    }

    public function add_tags_set_tag($tag){
        $query = self::getDB()->placehold("INSERT INTO __tags_sets_tags SET ?%", (array)$tag);
        self::getDB()->query($query);
        $id = self::getDB()->insert_id();
        return $id;
    }

    //Пересоздание автотегов
    public function recreate_auto_properties(){
        self::getDB()->query("SELECT * FROM __currencies WHERE is_enabled = 1 AND use_admin = 1 LIMIT 1");
        $admin_currency = self::getDB()->result();

        /// Пересоздадим автосвойства у брендов
        self::getDB()->query("SELECT id FROM __tags_groups WHERE name=? AND is_auto=?", "Бренд", 1);
        $tag_group_id = self::getDB()->result('id');

        self::getDB()->query("SELECT id FROM __brands ORDER BY position");
        $brands_ids = self::getDB()->results('id');
        foreach($brands_ids as $brand_id)
        {
            $brand = $this->brands->get_brand($brand_id);
            if (!$brand)
                continue;

            $brand_tag = $this->tags->get_tag($brand->tag_id);

            if ($brand->tag_id == 0 || !$brand_tag)
            {
                $new_tag = new StdClass;
                $new_tag->group_id = $tag_group_id;
                $new_tag->name = $brand->name;
                $new_tag->is_enabled = 1;
                $new_tag->is_auto = 1;
                $brand->tag_id = $this->tags->add_tag($new_tag);
                $this->brands->update_brand($brand->id, array('tag_id'=>$brand->tag_id));
            }
        }

        /// Пересоздадим автосвойства у товаров
        self::getDB()->query("SELECT id FROM __products ORDER BY position");
        $products_ids = self::getDB()->results('id');

        self::getDB()->query("SELECT id FROM __tags_groups WHERE name=? AND is_auto=?", "Цена", 1);
        $price_group_id = self::getDB()->result('id');

        self::getDB()->query("SELECT id FROM __tags_groups WHERE name=? AND is_auto=?", "Есть в наличии", 1);
        $stock_group_id = self::getDB()->result('id');

        foreach($products_ids as $product_id)
        {
            $product = $this->products->get_product($product_id);

            if (!$product)
                continue;

            //УДАЛИМ ВСЕ АВТОТЕГИ ТОВАРА
            self::getDB()->query("SELECT tp.tag_id
                                FROM __tags_products tp
                                INNER JOIN __tags t ON tp.tag_id = t.id
                            WHERE tp.product_id=? AND t.is_auto=1", $product->id);
            $autotags_ids = self::getDB()->results('tag_id');
            if (!empty($autotags_ids))
                self::getDB()->query("DELETE FROM __tags_products WHERE product_id=? AND tag_id IN (?@)", $product->id, $autotags_ids);

            //Проставим тег бренда если выбран Бренд и у него есть тег
            if ($product->brand_id > 0)
            {
                $product_brand = $this->brands->get_brand($product->brand_id);
                if ($product_brand && $product_brand->tag_id > 0)
                    $this->tags->add_product_tag($product->id, $product_brand->tag_id);
            }

            self::getDB()->query("SELECT category_id FROM __products_categories WHERE product_id=?", $product->id);
            $product_categories = self::getDB()->results('category_id');

            //Проставим теги категорий в которых входит товар
            foreach($product_categories as $category_id)
            {
                self::getDB()->query("SELECT tc.tag_id FROM __tags_categories tc
                                    INNER JOIN __tags t ON tc.tag_id = t.id
                                WHERE tc.category_id=? AND t.is_auto=1", $category_id);
                foreach(self::getDB()->results('tag_id') as $t_id)
                    $this->tags->add_product_tag($product->id, $t_id);
            }

            //Проставим теги цены
            $variants = $this->variants->get_variants(array('product_id'=>$product->id, 'is_visible'=>1));
            $in_stock = false;
            $in_order = false;
            if(is_array($variants))
                foreach($variants as $variant)
                {
                    if ($variant->stock > 0)
                        $in_stock = true;
                    if ($variant->stock < 0)
                        $in_order = true;
                    $tag_id = 0;
                    if ($tag = $this->tags->get_tags(array('group_id'=>$price_group_id,'name'=>$this->currencies->convert($variant->price, empty($product->currency_id) ? $admin_currency->id : $product->currency_id, false))))
                        $tag_id = intval(reset($tag)->id);
                    else
                    {
                        $tag = new StdClass;
                        $tag->group_id = $price_group_id;
                        $tag->name = $this->currencies->convert($variant->price, empty($product->currency_id) ? $admin_currency->id : $product->currency_id, false);
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

        self::getDB()->query("DELETE FROM __tags
            WHERE
                is_auto=1 AND
                (SELECT COUNT(tp.id) FROM __tags_products tp WHERE tp.tag_id=id)=0 AND
                (SELECT COUNT(tc.id) FROM __tags_categories tc WHERE tc.tag_id=id)=0 AND
                group_id not in (?@)", array($tag_group_id, $price_group_id, $stock_group_id));

        /*self::getDB()->query("SELECT t.id, count(tp.product_id) FROM __tags t LEFT JOIN __tags_products tp ON t.id = tp.tag_id WHERE is_auto=? GROUP BY t.id HAVING count(tp.product_id)=0", 1);
        $autotags_for_delete = self::getDB()->results('id');
        foreach($autotags_for_delete as $tag_del_id)
            $this->tags->delete_tag($tag_del_id);*/
    }

    //Удаление не привязанных никуда тегов
    public function delete_empty_tags($tags_ids = array()){
        /**if (empty($tags_ids))
            return;**/

        self::getDB()->query("DELETE FROM __tags
            WHERE is_auto=0 AND
                (SELECT COUNT(tp.id) FROM __tags_products tp WHERE tp.tag_id=__tags.id)=0 AND
                (SELECT COUNT(tc.id) FROM __tags_categories tc WHERE tc.tag_id=__tags.id)=0
                AND id in (?@)", $tags_ids);

        /**self::getDB()->query("SELECT t.id FROM __tags t
            LEFT JOIN __tags_products tp ON t.id=tp.tag_id
            LEFT JOIN __tags_categories tc ON t.id=tc.tag_id
            WHERE t.id in (?@)
            GROUP BY t.id
            HAVING count(tp.product_id)=0 AND count(tc.category_id)=0", $tags_ids);
        $ids = self::getDB()->results('id');
        foreach($ids as $tag_id)
            $this->delete_tag($tag_id);**/
    }
}