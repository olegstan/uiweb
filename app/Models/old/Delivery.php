<?php
namespace app\models;

use app\layer\LayerModel;

class Delivery extends LayerModel
{

    public function get_delivery($id)
    {

        $query = $this->db->placehold("SELECT id, name, description, free_from, price, is_enabled, position, separate_payment, is_pickup, delivery_type FROM __deliveries WHERE id=? LIMIT 1", intval($id));

        $this->db->query($query);
        return $this->db->result();
    }

    public function get_deliveries($filter = array())
    {
        // По умолчанию
        $is_enabled_filter = '';

        if(!empty($filter['is_enabled']))
            $is_enabled_filter = $this->db->placehold('AND is_enabled=?', intval($filter['is_enabled']));

        $query = "SELECT id, name, description, free_from, price, is_enabled, position, separate_payment, is_pickup, delivery_type
                    FROM __deliveries WHERE 1 $is_enabled_filter ORDER BY position";

        $this->db->query($query);

        return $this->db->results();
    }

    public function update_delivery($id, $delivery)
    {
        $query = $this->db->placehold("UPDATE __deliveries SET ?% WHERE id in(?@)", $delivery, (array)$id);
        $this->db->query($query);
        return $id;
    }

    public function add_delivery($delivery)
    {
        $query = $this->db->placehold('INSERT INTO __deliveries
        SET ?%',
        $delivery);

        if(!$this->db->query($query))
            return false;

        $id = $this->db->insert_id();
        $this->db->query("UPDATE __deliveries SET position=id WHERE id=?", $id);
        return $id;
    }

    public function delete_delivery($id)
    {
        if(!empty($id))
        {
            $query = $this->db->placehold("DELETE FROM __deliveries WHERE id=? LIMIT 1", intval($id));
            $this->db->query($query);
        }
    }


    public function get_delivery_payments($id)
    {
        $query = $this->db->placehold("SELECT payment_method_id FROM __deliveries_payment WHERE delivery_id=?", intval($id));
        $this->db->query($query);
        return $this->db->results('payment_method_id');
    }

    public function update_delivery_payments($id, $payment_methods_ids)
    {
        $query = $this->db->placehold("DELETE FROM __deliveries_payment WHERE delivery_id=?", intval($id));
        $this->db->query($query);
        if(is_array($payment_methods_ids))
        foreach($payment_methods_ids as $p_id)
            $this->db->query("INSERT INTO __deliveries_payment SET delivery_id=?, payment_method_id=?", $id, $p_id);
    }

}