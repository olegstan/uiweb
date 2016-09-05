<?php
namespace app\models\modificator;

use app\layer\LayerModel;

class Modificator extends LayerModel
{
    protected $table = 'mc_modificators';

    ##### МОДИФИКАТОРЫ ТОВАРОВ

    public function get_modificator($id)
    {
        $this->db->query("SELECT id, name, parent_id, type, value, is_visible, description, position, multi_apply, multi_buy, multi_buy_min, multi_buy_max FROM __modificators WHERE id=? LIMIT 1", intval($id));
        return $this->db->result();
    }

    public function get_modificators($filter = array())
    {
        $limit = 100;
        $page = 1;
        $is_visible_filter = '';
        $parent_filter = '';

        if(isset($filter['limit']))
            $limit = max(1, intval($filter['limit']));

        if(isset($filter['page']))
            $page = max(1, intval($filter['page']));

        $sql_limit = $this->db->placehold(' LIMIT ?, ? ', ($page-1)*$limit, $limit);

        if(isset($filter['is_visible']))
            $is_visible_filter = $this->db->placehold('AND is_visible=?', intval($filter['is_visible']));

        if(array_key_exists('parent_id', $filter))
            if (is_numeric($filter['parent_id']))
                $parent_filter = $this->db->placehold('AND parent_id=?', intval($filter['parent_id']));
            else
                $parent_filter = $this->db->placehold('AND parent_id is null');

        $order = 'position';
        $order_direction = '';

        $query = "SELECT id, name, parent_id, type, value, is_visible, description, position, multi_apply, multi_buy, multi_buy_min, multi_buy_max FROM __modificators WHERE 1 $is_visible_filter $parent_filter ORDER BY $order $order_direction $sql_limit";

        $this->db->query($query);

        return $this->db->results();
    }

    public function count_modificators($filter = array())
    {
        $is_visible_filter = '';
        $parent_filter = '';

        if(isset($filter['is_visible']))
            $is_visible_filter = $this->db->placehold('AND is_visible = ?', intval($filter['is_visible']));

        if(isset($filter['parent_id']))
            $parent_filter = $this->db->placehold('AND parent_id=?', intval($filter['parent_id']));

        $query = "SELECT count(distinct id) as count
                FROM __modificators
                WHERE 1
                    $is_visible_filter
                    $parent_filter";

        $this->db->query($query);
        return $this->db->result('count');
    }

    public function update_modificator($id, $modificator)
    {
        $query = $this->db->placehold("UPDATE __modificators SET ?% WHERE id in(?@)", $modificator, (array)$id);
        $this->db->query($query);
        return $id;
    }

    public function add_modificator($modificator)
    {
        $query = $this->db->placehold('INSERT INTO __modificators SET ?%', $modificator);
        if(!$this->db->query($query))
            return false;

        $id = $this->db->insert_id();
        $this->db->query("UPDATE __modificators SET position=id WHERE id=?", $id);
        return $id;
    }

    public function delete_modificator($id)
    {
        if(!empty($id))
        {
            $query = $this->db->placehold("DELETE FROM __modificators WHERE id=? LIMIT 1", intval($id));
            $this->db->query($query);
        }
    }

#########################################
## GROUPS
#########################################

    public function get_modificators_group($id)
    {
        $this->db->query("SELECT id, name, position, is_visible, type FROM __modificators_groups WHERE id=? LIMIT 1", intval($id));
        return $this->db->result();
    }

    public function get_modificators_groups($filter = array())
    {
        $is_visible_filter = '';

        if (isset($filter['is_visible']))
            $is_visible_filter = $this->db->placehold('AND is_visible=?', intval($filter['is_visible']));

        $order = 'position';
        $order_direction = '';

        $query = "SELECT id, name, position, is_visible, type FROM __modificators_groups WHERE 1 $is_visible_filter ORDER BY $order $order_direction";

        $this->db->query($query);

        return $this->db->results();
    }

    public function count_modificators_groups($filter = array())
    {
        $is_visible_filter = '';

        if (isset($filter['is_visible']))
            $is_visible_filter = $this->db->placehold('AND is_visible=?', intval($filter['is_visible']));

        $query = "SELECT count(distinct id) as count
                FROM __modificators_groups
                WHERE 1 $is_visible_filter";

        $this->db->query($query);
        return $this->db->result('count');
    }

    public function update_modificator_group($id, $modificator_group)
    {
        $query = $this->db->placehold("UPDATE __modificators_groups SET ?% WHERE id in(?@)", $modificator_group, (array)$id);
        $this->db->query($query);
        return $id;
    }

    public function add_modificator_group($modificator_group)
    {
        $query = $this->db->placehold('INSERT INTO __modificators_groups SET ?%', $modificator_group);
        if(!$this->db->query($query))
            return false;

        $id = $this->db->insert_id();
        $this->db->query("UPDATE __modificators_groups SET position=id WHERE id=?", $id);
        return $id;
    }

    public function delete_modificator_group($id)
    {
        if(!empty($id))
        {
            $query = $this->db->placehold("DELETE FROM __modificators_groups WHERE id=? LIMIT 1", intval($id));
            $this->db->query($query);
        }
    }

    ##### МОДИФИКАТОРЫ ЗАКАЗОВ

    public function get_modificator_orders($id)
    {
        $this->db->query("SELECT id, name, parent_id, type, value, is_visible, description, position, multi_buy, multi_buy_min, multi_buy_max FROM __modificators_orders WHERE id=? LIMIT 1", intval($id));
        return $this->db->result();
    }

    public function get_modificators_orders($filter = array())
    {
        $limit = 100;
        $page = 1;
        $is_visible_filter = '';
        $parent_filter = '';

        if(isset($filter['limit']))
            $limit = max(1, intval($filter['limit']));

        if(isset($filter['page']))
            $page = max(1, intval($filter['page']));

        $sql_limit = $this->db->placehold(' LIMIT ?, ? ', ($page-1)*$limit, $limit);

        if(isset($filter['is_visible']))
            $is_visible_filter = $this->db->placehold('AND is_visible=?', intval($filter['is_visible']));

        if(array_key_exists('parent_id', $filter))
            if (is_numeric($filter['parent_id']))
                $parent_filter = $this->db->placehold('AND parent_id=?', intval($filter['parent_id']));
            else
                $parent_filter = $this->db->placehold('AND parent_id is null');

        $order = 'position';
        $order_direction = '';

        $query = "SELECT id, name, parent_id, type, value, is_visible, description, position, multi_buy, multi_buy_min, multi_buy_max FROM __modificators_orders WHERE 1 $is_visible_filter $parent_filter ORDER BY $order $order_direction $sql_limit";

        $this->db->query($query);

        return $this->db->results();
    }

    public function count_modificators_orders($filter = array())
    {
        $is_visible_filter = '';
        $parent_filter = '';

        if(isset($filter['is_visible']))
            $is_visible_filter = $this->db->placehold('AND is_visible = ?', intval($filter['is_visible']));

        if(isset($filter['parent_id']))
            $parent_filter = $this->db->placehold('AND parent_id=?', intval($filter['parent_id']));

        $query = "SELECT count(distinct id) as count
                FROM __modificators_orders
                WHERE 1
                    $is_visible_filter
                    $parent_filter";

        $this->db->query($query);
        return $this->db->result('count');
    }

    public function update_modificator_orders($id, $modificator)
    {
        $query = $this->db->placehold("UPDATE __modificators_orders SET ?% WHERE id in(?@)", $modificator, (array)$id);
        $this->db->query($query);
        return $id;
    }

    public function add_modificator_orders($modificator)
    {
        $query = $this->db->placehold('INSERT INTO __modificators_orders SET ?%', $modificator);
        if(!$this->db->query($query))
            return false;

        $id = $this->db->insert_id();
        $this->db->query("UPDATE __modificators_orders SET position=id WHERE id=?", $id);
        return $id;
    }

    public function delete_modificator_orders($id)
    {
        if(!empty($id))
        {
            $query = $this->db->placehold("DELETE FROM __modificators_orders WHERE id=? LIMIT 1", intval($id));
            $this->db->query($query);
        }
    }

#########################################
## GROUPS
#########################################

    public function get_modificators_orders_group($id)
    {
        $this->db->query("SELECT id, name, position, is_visible, type FROM __modificators_orders_groups WHERE id=? LIMIT 1", intval($id));
        return $this->db->result();
    }

    public function get_modificators_orders_groups($filter = array())
    {
        $is_visible_filter = '';

        if (isset($filter['is_visible']))
            $is_visible_filter = $this->db->placehold('AND is_visible=?', intval($filter['is_visible']));

        $order = 'position';
        $order_direction = '';

        $query = "SELECT id, name, position, is_visible, type FROM __modificators_orders_groups WHERE 1 $is_visible_filter ORDER BY $order $order_direction";

        $this->db->query($query);

        return $this->db->results();
    }

    public function count_modificators_orders_groups($filter = array())
    {
        $is_visible_filter = '';

        if (isset($filter['is_visible']))
            $is_visible_filter = $this->db->placehold('AND is_visible=?', intval($filter['is_visible']));

        $query = "SELECT count(distinct id) as count
                FROM __modificators_orders_groups
                WHERE 1 $is_visible_filter";

        $this->db->query($query);
        return $this->db->result('count');
    }

    public function update_modificator_orders_group($id, $modificator_group)
    {
        $query = $this->db->placehold("UPDATE __modificators_orders_groups SET ?% WHERE id in(?@)", $modificator_group, (array)$id);
        $this->db->query($query);
        return $id;
    }

    public function add_modificator_orders_group($modificator_group)
    {
        $query = $this->db->placehold('INSERT INTO __modificators_orders_groups SET ?%', $modificator_group);
        if(!$this->db->query($query))
            return false;

        $id = $this->db->insert_id();
        $this->db->query("UPDATE __modificators_orders_groups SET position=id WHERE id=?", $id);
        return $id;
    }

    public function delete_modificator_orders_group($id)
    {
        if(!empty($id))
        {
            $query = $this->db->placehold("DELETE FROM __modificators_orders_groups WHERE id=? LIMIT 1", intval($id));
            $this->db->query($query);
        }
    }
}