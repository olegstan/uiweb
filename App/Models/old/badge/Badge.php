<?php
namespace app\models\badge;

use app\layer\LayerModel;

class Badge extends LayerModel
{
    protected $table = 'mc_badges';






    public function get_badge($id)
    {
        $where_filter = '';
        if (is_numeric($id))
            $where_filter = self::getDB()->placehold("id=?", intval($id));
        else
            $where_filter = self::getDB()->placehold("name=?", strval($id));
        self::getDB()->query("SELECT id, name, is_visible, position, css_class, css_class_product FROM __badges WHERE $where_filter LIMIT 1");
        return self::getDB()->result();
    }

    public function get_badges($filter = array())
    {
        $limit = 100;
        $page = 1;
        $is_visible_filter = '';
        $keyword_filter = '';

        if(isset($filter['limit']))
            $limit = max(1, intval($filter['limit']));

        if(isset($filter['page']))
            $page = max(1, intval($filter['page']));

        $sql_limit = self::getDB()->placehold(' LIMIT ?, ? ', ($page-1)*$limit, $limit);

        if(isset($filter['is_visible']))
            $is_visible_filter = self::getDB()->placehold('AND is_visible=?', intval($filter['is_visible']));

        if(!empty($filter['keyword']))
            $keyword_filter = self::getDB()->placehold('AND name LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%"');

        $order = 'name';
        $order_direction = '';

        $query = "SELECT id, name, is_visible, position, css_class, css_class_product FROM __badges WHERE 1 $is_visible_filter $keyword_filter ORDER BY $order $order_direction $sql_limit";

        self::getDB()->query($query);

        return self::getDB()->results();
    }

    public function count_badges($filter = array())
    {
        $is_visible_filter = '';
        $keyword_filter = '';

        if(isset($filter['is_visible']))
            $is_visible_filter = self::getDB()->placehold('AND is_visible = ?', intval($filter['is_visible']));

        if(!empty($filter['keyword']))
            $keyword_filter = self::getDB()->placehold('AND name LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%"');

        $query = "SELECT count(distinct id) as count
                FROM __badges
                WHERE 1
                    $is_visible_filter
                    $keyword_filter";

        self::getDB()->query($query);
        return self::getDB()->result('count');
    }

    public function update_badge($id, $badge)
    {
        $query = self::getDB()->placehold("UPDATE __badges SET ?% WHERE id in(?@)", $badge, (array)$id);
        self::getDB()->query($query);
        return $id;
    }

    public function add_badge($badge)
    {
        $query = self::getDB()->placehold('INSERT INTO __badges SET ?%', $badge);
        if(!self::getDB()->query($query))
            return false;

        $id = self::getDB()->insert_id();
        self::getDB()->query("UPDATE __badges SET position=id WHERE id=?", $id);
        return $id;
    }

    public function delete_badge($id)
    {
        if(!empty($id))
        {
            $images = $this->image->get_images('badges', $id);
            foreach($images as $i)
                $this->image->delete_image('badges', $id, $i->id);

            $query = self::getDB()->placehold("DELETE FROM __badges WHERE id=? LIMIT 1", intval($id));
            self::getDB()->query($query);
        }
    }

    //Функция удаляет все бейджи у товара
    public function delete_product_badges($product_id){
        $query = self::getDB()->placehold("DELETE FROM __badges_products WHERE product_id=?", $product_id);
        self::getDB()->query($query);
        return true;
    }

    //Функция добавляет бейдж для товара
    public function add_product_badge($product_id, $badge_id){
        $query = self::getDB()->placehold("INSERT IGNORE INTO __badges_products(product_id, badge_id) VALUES(?,?)", $product_id, intval($badge_id));
        self::getDB()->query($query);
        return true;
    }

    //Функция удаляет бейдж у товара
    public function delete_product_badge($product_id, $badge_id){
        $query = self::getDB()->placehold("DELETE FROM __badges_products WHERE product_id=? AND badge_id=?", $product_id, intval($badge_id));
        self::getDB()->query($query);
        return true;
    }

    //Функция возвращает бейджи товара
    public function get_product_badges($product_id){
        $query = self::getDB()->placehold("SELECT distinct b.id, b.name, b.is_visible, b.position, b.css_class, b.css_class_product
                                FROM __badges b
                                    INNER JOIN __badges_products bp ON b.id=bp.badge_id
                                WHERE bp.product_id=?
                                ORDER BY b.position", $product_id);
        self::getDB()->query($query);
        return self::getDB()->results();
    }
}