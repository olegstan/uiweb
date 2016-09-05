<?php
namespace app\models;

use app\layer\LayerModel;

class Banner extends LayerModel
{
    protected $table = 'mc_banners';











    public function get_banner($id)
    {
        $this->db->query("SELECT id, name, is_visible, show_editor, text, position, is_system, css_class, is_link, link, link_in_new_window FROM __banners WHERE id=? LIMIT 1", intval($id));
        return $this->db->result();
    }

    public function get_banners($filter = array())
    {
        $limit = 100;
        $page = 1;
        $is_visible_filter = '';
        $keyword_filter = '';
        $is_system_filter = '';

        if(isset($filter['limit']))
            $limit = max(1, intval($filter['limit']));

        if(isset($filter['page']))
            $page = max(1, intval($filter['page']));

        $sql_limit = $this->db->placehold(' LIMIT ?, ? ', ($page-1)*$limit, $limit);

        if(isset($filter['is_visible']))
            $is_visible_filter = $this->db->placehold('AND is_visible=?', intval($filter['is_visible']));

        if(!empty($filter['keyword']))
            $keyword_filter = $this->db->placehold('AND name LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%"');

        if (isset($filter['is_system']))
            $is_system_filter = $this->db->placehold('AND is_system=?', intval($filter['is_system']));

        $order = 'position';
        $order_direction = '';

        if(!empty($filter['sort']))
            switch ($filter['sort'])
            {
                case 'position':
                $order = 'position';
                break;
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

        $query = "SELECT id, name, is_visible, show_editor, text, position, is_system, css_class, is_link, link, link_in_new_window FROM __banners WHERE 1 $is_visible_filter $keyword_filter $is_system_filter ORDER BY $order $order_direction $sql_limit";
        $this->db->query($query);

        return $this->db->results();
    }

    public function count_banners($filter = array())
    {
        $is_visible_filter = '';
        $keyword_filter = '';
        $is_system_filter = '';

        if(isset($filter['is_visible']))
            $is_visible_filter = $this->db->placehold('AND is_visible = ?', intval($filter['is_visible']));

        if(!empty($filter['keyword']))
            $keyword_filter = $this->db->placehold('AND name LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%"');

        if (isset($filter['is_system']))
            $is_system_filter = $this->db->placehold('AND is_system=?', intval($filter['is_system']));

        $query = "SELECT count(distinct id) as count
                FROM __banners
                WHERE 1
                    $is_visible_filter
                    $keyword_filter
                    $is_system_filter";

        $this->db->query($query);
        return $this->db->result('count');
    }

    public function update_banner($id, $banner)
    {
        $query = $this->db->placehold("UPDATE __banners SET ?% WHERE id in(?@)", $banner, (array)$id);
        $this->db->query($query);
        return $id;
    }

    public function add_banner($banner)
    {
        $query = $this->db->placehold('INSERT INTO __banners SET ?%', $banner);
        if(!$this->db->query($query))
            return false;

        $id = $this->db->insert_id();
        $this->db->query("UPDATE __banners SET position=id WHERE id=?", $id);
        return $id;
    }

    public function delete_banner($id)
    {
        if(!empty($id))
        {
            $query = $this->db->placehold("DELETE FROM __banners WHERE id=? LIMIT 1", intval($id));
            $this->db->query($query);
        }
    }
}