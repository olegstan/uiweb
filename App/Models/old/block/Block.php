<?php
namespace app\models\block;

use app\layer\LayerModel;

class Block extends LayerModel
{
    protected $table = '';

###############################
## ГРУППЫ ТОВАРОВ НА ГЛАВНОЙ ##
###############################
    // Получить группы
    public function get_blocks($filter = array())
    {
        $block_id_filter = '';
        $is_visible_filter = '';

        $order = 'position';

        if(!empty($filter['id']))
            $block_id_filter = $this->db->placehold('AND id in(?@)', (array)$filter['id']);

        if(!empty($filter['is_visible']))
            $is_visible_filter = $this->db->placehold('AND is_visible=?', intval($filter['is_visible']));

        $query = "SELECT id, name, products_count, show_mode, css_class, is_visible, position
                FROM __groups_on_main
                WHERE
                    1
                    $block_id_filter
                    $is_visible_filter
                ORDER BY $order";

        $query = $this->db->placehold($query);
        $this->db->query($query);

        return $this->db->results();
    }

    // Получить количество групп
    public function count_blocks($filter = array())
    {
        $block_id_filter = '';
        $is_visible_filter = '';

        if(!empty($filter['id']))
            $block_id_filter = $this->db->placehold('AND id in(?@)', (array)$filter['id']);

        if(!empty($filter['is_visible']))
            $is_visible_filter = $this->db->placehold('AND is_visible=?', intval($filter['is_visible']));

        $query = "SELECT count(id) as count
                FROM __groups_on_main
                WHERE
                    1
                    $block_id_filter
                    $is_visible_filter";

        $query = $this->db->placehold($query);
        $this->db->query($query);
        return $this->db->result('count');
    }

    //Получить группу
    public function get_block($id)
    {
        $query = $this->db->placehold("SELECT id, name, products_count, show_mode, css_class, is_visible, position
                FROM __groups_on_main
                WHERE id=?
                LIMIT 1", intval($id));
        $this->db->query($query);
        return $this->db->result();
    }

    // Обновить группу
    public function update_block($id, $block)
    {
        $block = (array) $block;
        $query = $this->db->placehold("UPDATE __groups_on_main SET ?% WHERE id in (?@) LIMIT ?", $block, (array) $id, count((array) $id));
        if ($this->db->query($query))
            return $id;
        else
            return false;
    }

    // Добавить группу
    public function add_block($block)
    {
        $block = (array) $block;

        $this->db->query("INSERT INTO __groups_on_main SET ?%", $block);
        $id = $this->db->insert_id();
        $this->db->query("UPDATE __groups_on_main SET position=id WHERE id=?", $id);
        return $id;
    }

    // Удалить группу
    public function delete_block($id)
    {
        if(!empty($id))
        {
            // Удаляем связанные товары
            /**$related = $this->get_related_products($id);
            foreach($related as $r)
                $this->delete_related_product($id, $r->product_id);
            $this->db->query("DELETE FROM __groups_related_products WHERE group_id=?", $id);**/

            // Удаляем группу
            $query = $this->db->placehold("DELETE FROM __groups_on_main WHERE id=? LIMIT 1", intval($id));
            if($this->db->query($query))
                return true;
        }
        return false;
    }

    // Получить связанные товары
    function get_related_products($filter = array())
    {
        $group_id_filter = '';
        $is_visible_filter = '';

        if (!empty($filter['group_id']))
            $group_id_filter = $this->db->placehold('AND grp.group_id in(?@)', (array)$filter['group_id']);
        else
            return array();

        if (isset($filter['is_visible']))
            $is_visible_filter = $this->db->placehold('AND p.is_visible=? AND cats.is_visible=?', intval($filter['is_visible']), intval($filter['is_visible']));

        $query = $this->db->placehold("SELECT distinct grp.product_id, grp.group_id, grp.position
                    FROM __groups_related_products grp
                        LEFT JOIN __products p ON grp.product_id=p.id
                        LEFT JOIN __products_categories pcats ON p.id = pcats.product_id
                        LEFT JOIN __categories cats ON pcats.category_id=cats.id
                    WHERE
                    1
                    $group_id_filter
                    $is_visible_filter
                    ORDER BY grp.position
                    ");

        $this->db->query($query);
        return $this->db->results();
    }

    // Добавление связанного товара
    public function add_related_product($group_id, $product_id, $position=0)
    {
        $query = $this->db->placehold("INSERT IGNORE INTO __groups_related_products SET group_id=?, product_id=?, position=?", $group_id, $product_id, $position);
        $this->db->query($query);
        return $product_id;
    }

    // Удаление связанного товара
    public function delete_related_product($group_id, $product_id)
    {
        $query = $this->db->placehold("DELETE FROM __groups_related_products WHERE group_id=? AND product_id=? LIMIT 1", intval($group_id), intval($product_id));
        $this->db->query($query);
    }

#################################
## ГРУППЫ КАТЕГОРИЙ НА ГЛАВНОЙ ##
#################################
    // Получить группы
    public function get_categories_blocks($filter = array())
    {
        $block_id_filter = '';
        $is_visible_filter = '';

        $order = 'position';

        if(!empty($filter['id']))
            $block_id_filter = $this->db->placehold('AND id in(?@)', (array)$filter['id']);

        if(!empty($filter['is_visible']))
            $is_visible_filter = $this->db->placehold('AND is_visible=?', intval($filter['is_visible']));

        $query = "SELECT id, name, categories_count, css_class, is_visible, position
                FROM __groups_categories_on_main
                WHERE
                    1
                    $block_id_filter
                    $is_visible_filter
                ORDER BY $order";

        $query = $this->db->placehold($query);
        $this->db->query($query);

        return $this->db->results();
    }

    // Получить количество групп
    public function count_categories_blocks($filter = array())
    {
        $block_id_filter = '';
        $is_visible_filter = '';

        if(!empty($filter['id']))
            $block_id_filter = $this->db->placehold('AND id in(?@)', (array)$filter['id']);

        if(!empty($filter['is_visible']))
            $is_visible_filter = $this->db->placehold('AND is_visible=?', intval($filter['is_visible']));

        $query = "SELECT count(id) as count
                FROM __groups_categories_on_main
                WHERE
                    1
                    $block_id_filter
                    $is_visible_filter";

        $query = $this->db->placehold($query);
        $this->db->query($query);
        return $this->db->result('count');
    }

    //Получить группу
    public function get_categories_block($id)
    {
        $query = $this->db->placehold("SELECT id, name, categories_count, css_class, is_visible, position
                FROM __groups_categories_on_main
                WHERE id=?
                LIMIT 1", intval($id));
        $this->db->query($query);
        return $this->db->result();
    }

    // Обновить группу
    public function update_categories_block($id, $block)
    {
        $block = (array) $block;
        $query = $this->db->placehold("UPDATE __groups_categories_on_main SET ?% WHERE id in (?@) LIMIT ?", $block, (array) $id, count((array) $id));
        if ($this->db->query($query))
            return $id;
        else
            return false;
    }

    // Добавить группу
    public function add_categories_block($block)
    {
        $block = (array) $block;

        $this->db->query("INSERT INTO __groups_categories_on_main SET ?%", $block);
        $id = $this->db->insert_id();
        $this->db->query("UPDATE __groups_categories_on_main SET position=id WHERE id=?", $id);
        return $id;
    }

    // Удалить группу
    public function delete_categories_block($id)
    {
        if(!empty($id))
        {
            // Удаляем группу
            $query = $this->db->placehold("DELETE FROM __groups_categories_on_main WHERE id=? LIMIT 1", intval($id));
            if($this->db->query($query))
                return true;
        }
        return false;
    }

    // Получить связанные категории
    function get_related_categories($filter = array())
    {
        $group_id_filter = '';
        $is_visible_filter = '';

        if (!empty($filter['group_id']))
            $group_id_filter = $this->db->placehold('AND grc.group_id in(?@)', (array)$filter['group_id']);
        else
            return array();

        if (isset($filter['is_visible']))
            $is_visible_filter = $this->db->placehold('AND cats.is_visible=?', intval($filter['is_visible']));

        $query = $this->db->placehold("SELECT distinct grc.category_id, grc.group_id, grc.position
                    FROM __groups_related_categories grc
                        LEFT JOIN __categories cats ON grc.category_id=cats.id
                    WHERE
                    1
                    $group_id_filter
                    $is_visible_filter
                    ORDER BY grc.position
                    ");

        $this->db->query($query);
        return $this->db->results();
    }

    // Добавление связанной категории
    public function add_related_category($group_id, $category_id, $position=0)
    {
        $query = $this->db->placehold("INSERT IGNORE INTO __groups_related_categories SET group_id=?, category_id=?, position=?", $group_id, $category_id, $position);
        $this->db->query($query);
        return $category_id;
    }

    // Удаление связанной категории
    public function delete_related_category($group_id, $category_id)
    {
        $query = $this->db->placehold("DELETE FROM __groups_related_categories WHERE group_id=? AND category_id=? LIMIT 1", intval($group_id), intval($category_id));
        $this->db->query($query);
    }

}