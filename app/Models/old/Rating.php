<?php
namespace app\models;

use app\layer\LayerModel;

class Rating extends LayerModel
{
    protected $table = 'mc_ratings';

    public $avg_rating = 0;
    public $avg_rating_real = 0;
    public $rating_count = 0;

    public function getRating()
    {

    }

    public function calc_product_rating($product_id)
    {
        $this->db->query("SELECT (ROUND(AVG(rating) * 2) / 2) as avg_rating, AVG(rating) as avg_rating_real, COUNT(id) as rating_count FROM __ratings WHERE product_id=? GROUP BY product_id", $product_id);
        return $this->db->result();
    }

    public function get_product_rate_by_user_id($product_id, $user_id)
    {
        $this->db->query("SELECT id, product_id, datetime, rating, user_id, session_id FROM __ratings WHERE product_id=? AND user_id=? LIMIT 1", $product_id, $user_id);
        return $this->db->result();
    }

    public function get_product_rate_by_session_id($product_id, $session_id)
    {
        $this->db->query("SELECT id, product_id, datetime, rating, user_id, session_id FROM __ratings WHERE product_id=? AND session_id=? LIMIT 1", $product_id, $session_id);
        return $this->db->result();
    }

    public function get_product_ratings($product_id)
    {
        $this->db->query("SELECT id, product_id, datetime, rating, user_id, session_id FROM __ratings WHERE product_id=?", $product_id);
        return $this->db->results();
    }

    public function update_rate($id, $rate)
    {
        $query = $this->db->placehold("UPDATE __ratings SET ?% WHERE id in(?@)", $rate, (array)$id);
        $this->db->query($query);
        return $id;
    }

    public function add_rate($rate)
    {
        $query = $this->db->placehold('INSERT IGNORE INTO __ratings SET ?%', $rate);
        if(!$this->db->query($query))
            return false;

        return $this->db->insert_id();
    }

    public function delete_rate($id)
    {
        if(!empty($id))
        {
            $query = $this->db->placehold("DELETE FROM __ratings WHERE id=? LIMIT 1", intval($id));
            $this->db->query($query);
        }
    }
}