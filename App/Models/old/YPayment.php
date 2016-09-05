<?php
namespace app\models;

use app\layer\LayerModel;

class YPayment extends LayerModel
{
    function get_payment($id){
        $query = $this->db->placehold("SELECT id, order_id, invoice_id, sum, payment_type, unix_timestamp(payment_datetime) as payment_datetime FROM __ymoney_payments WHERE id=? LIMIT 1", intval($id));
        $this->db->query($query);
        $payment = $this->db->result();
          return $payment;
    }

    public function get_payments($filter = array()){
        // �� ���������
        $limit = 10;
        $page = 1;
        $order_id_filter = '';
        $viewed_filter = '';
        if(isset($filter['limit']))
            $limit = max(1, intval($filter['limit']));
        if(isset($filter['page']))
            $page = max(1, intval($filter['page']));
            
        $sql_limit = $this->db->placehold(' LIMIT ?, ? ', ($page-1)*$limit, $limit);

        if(!empty($filter['order_id']))
            $order_id_filter = $this->db->placehold('AND order_id=?', intval($filter['order_id']));

        $query = "SELECT id, order_id, invoice_id, sum, payment_type, unix_timestamp(payment_datetime) as payment_datetime FROM __ymoney_payments WHERE 1 $order_id_filter ORDER BY payment_datetime desc $sql_limit";

        $this->db->query($query);
        return $this->db->results();
    }

    public function count_payments($filter = array()){
        $order_id_filter = '';
        if(!empty($filter['order_id']))
            $order_id_filter = $this->db->placehold('AND order_id=?', intval($filter['order_id']));

        $query = "SELECT count(id) as kol
                    FROM __ymoney_payments WHERE 1 $order_id_filter";

        $this->db->query($query);
        return $this->db->result('kol');
    }

    public function update_payment($id, $payment)
    {
        $query = $this->db->placehold("UPDATE __ymoney_payments SET ?% WHERE id in(?@)", $payment, (array)$id);
        $this->db->query($query);
        return $id;
    }

    public function add_payment($payment)
    {
        $query = $this->db->placehold('INSERT IGNORE INTO __ymoney_payments
        SET ?%',
        $payment);

        if(!$this->db->query($query))
            return false;

        $id = $this->db->insert_id();
        return $id;
    }

    public function delete_payment($id)
    {
        if(!empty($id))
        {
            $query = $this->db->placehold("DELETE FROM __ymoney_payments WHERE id=? LIMIT 1", intval($id));
            $this->db->query($query);
        }
    }
}