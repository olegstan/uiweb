<?php
namespace app\models\order;

use app\layer\LayerModel;
use app\models\Purchase;
use app\models\user\User;
use core\Collection;
use \DateTime;

class Order extends LayerModel
{
    protected $table = 'mc_orders';

    public $purchases = [];

    public $user;

    public $total_price = 0;
    public $day_str;

    public static $days_week = [
        'Monday' => 'Пн',
        'Tuesday' => 'Вт',
        'Wednesday' => 'Ср',
        'Thursday' => 'Чт',
        'Friday' => 'Пт',
        'Saturday' => 'Сб',
        'Sunday' => 'Вс'
    ];


    public function afterSelect()
    {
        $this->created_t = (new DateTime($this->created_dt))->format('H:i:s');
        return $this;
    }


    /**
     * @param DateTime $today
     * @param DateTime $yesterday
     */

    public function getDayString(DateTime $today, DateTime $yesterday)
    {
        $created_dt = (new DateTime($this->created_dt))->format('Ymd');

        if ($created_dt == $today->format('Ymd')) {
            $this->day_str = 'Сегодня';
        }else{
            if($created_dt == $yesterday->format('Ymd')){
                $this->day_str = 'Вчера';
            }else{
                $created_year_dt = (new DateTime($this->created_dt))->format('Ymd');
                $created_day_dt = (new DateTime($this->created_dt))->format('l');
                
                if ($created_year_dt == date('Y')) {
                    $this->day_str = self::$days_week[$created_day_dt] . ' ' . (new DateTime($this->created_dt))->format('d.m');
                } else {
                    $this->day_str = self::$days_week[$created_day_dt] . ' ' . (new DateTime($this->created_dt))->format('d.m.Y');
                }
            }
        }
    }

    public static function purchases(Collection $collection, $rules = null)
    {
        switch($rules['type']){
            case 'one':

                break;
            case 'all':
                $orders = $collection->getResult();
                $orders_ids = $collection->getId();

                if($orders){
                    $purchases = (new Purchase())
                        ->query()
                        ->select()
                        ->where('order_id IN (' . implode(',', $orders_ids) . ')')
                        ->execute()
                        ->all(null, 'order_id')
                        ->getResult();

                    if($purchases){
                        foreach($purchases as $k => $purchase){
                            $orders[$k]->purchases[] = $purchase;
                            $orders[$k]->total_price += $purchase->amount * $purchase->price;
                        }

                        foreach($orders as $order){
                            if ($order->discount_type == 0) {
                                $order->total_price = $order->total_price - $order->total_price * $order->discount / 100;
                            }else {
                                $order->total_price = $order->total_price - $order->discount;
                            }
                            if (!$order->separate_delivery) {
                                $order->total_price += $order->delivery_price;
                            }
                        }
                    }
                }

                break;
        }
    }

    public static function users(Collection $collection, $rules = null)
    {
        switch($rules['type']){
            case 'one':

                break;
            case 'all':
                $orders = $collection->getResult();
                $users_ids = $collection->getField('user_id');

                if($orders){
                    $users = (new User())
                        ->query()
                        ->select()
                        ->where('id IN (' . implode(',', $users_ids) . ')')
                        ->execute()
                        ->all()
                        ->getResult();

                    if($users){
                        foreach($orders as $k => $order){
                            $order->user = $users[$order->user_id];
                        }
                    }
                }

                break;
        }
    }







    public function get_order($id)
    {
        $where = $this->db->placehold(' WHERE id=? ', intval($id));
        $query = $this->db->placehold("SELECT id, delivery_id, delivery_price, separate_delivery, payment_method_id, payment_price, paid, payment_date, closed, discount_type, discount, unix_timestamp(date) as date,
                                    user_id, name, address, phone_code, phone, phone2_code, phone2, email, comment, status_id, url, total_price, total_price_wo_discount, note, notify_order, notify_news, moderated, allow_payment, buy1click, ip FROM __orders $where LIMIT 1");

        if($this->db->query($query))
            return $this->db->result();
        else
            return false;
    }

    public function get_order_by_url($id)
    {
        $where = $this->db->placehold(' WHERE url=? ', $id);
        $query = $this->db->placehold("SELECT id, delivery_id, delivery_price, separate_delivery, payment_method_id, payment_price, paid, payment_date, closed, discount_type, discount, unix_timestamp(date) as date,
                                    user_id, name, address, phone_code, phone, phone2_code, phone2, email, comment, status_id, url, total_price, total_price_wo_discount, note, notify_order, notify_news, moderated, allow_payment, buy1click, ip FROM __orders $where LIMIT 1");
        if($this->db->query($query))
            return $this->db->result();
        else
            return false;
    }

    function get_orders($filter = array())
    {
        // По умолчанию
        $limit = 100;
        $page = 1;
        $keyword_filter = '';
        $status_id_filter = '';
        $status_type_filter = '';
        $exclude_status_id_filter = '';
        $user_filter = '';
        $modified_from_filter = '';
        $moderated_filter = '';
        $id_filter = '';
        $sort = 'date desc, id desc';
        $sort_type = '';

        if(isset($filter['limit']))
            $limit = max(1, intval($filter['limit']));

        if(isset($filter['page']))
            $page = max(1, intval($filter['page']));

        $sql_limit = $this->db->placehold(' LIMIT ?, ? ', ($page-1)*$limit, $limit);

        if(isset($filter['status_id']))
            $status_id_filter = $this->db->placehold('AND o.status_id = ?', intval($filter['status_id']));

        if (isset($filter['status_type']))
            $status_type_filter = $this->db->placehold('AND os.status_type = ?', $filter['status_type']);

        if(isset($filter['exclude_status_id']))
            $exclude_status_id_filter = $this->db->placehold('AND o.status_id <> ?', intval($filter['exclude_status_id']));

        if(isset($filter['id']))
            $id_filter = $this->db->placehold('AND o.id in(?@)', (array)$filter['id']);

        if(isset($filter['user_id']))
            $user_filter = $this->db->placehold('AND o.user_id = ?', intval($filter['user_id']));

        if(isset($filter['modified_from']))
            $modified_from_filter = $this->db->placehold('AND o.modified > ?', $filter['modified_from']);

        if (isset($filter['moderated']))
            $moderated_filter = $this->db->placehold('AND o.moderated = ?', $filter['moderated']);

        if(!empty($filter['keyword']))
        {
            $keyword = $filter['keyword'];
            $keyword_filter = $this->db->placehold('AND (( o.id = "'.mysql_real_escape_string(trim($keyword)).'") OR ( o.name LIKE "%'.mysql_real_escape_string(trim($keyword)).'%") OR (CONCAT("+7",o.phone_code,o.phone) LIKE "%'.mysql_real_escape_string(trim($keyword)).'%") OR (CONCAT("+7",o.phone2_code,o.phone2) LIKE "%'.mysql_real_escape_string(trim($keyword)).'%") OR (SELECT COUNT(*) FROM __purchases p WHERE p.order_id=o.id AND (p.sku = "'.mysql_real_escape_string(trim($keyword)).'" OR p.product_name LIKE "%'.mysql_real_escape_string(trim($keyword)).'%"))>0)');
        }

        if (!empty($filter['sort']))
            switch($filter['sort']){
                case "date":
                    $sort = "o.date";
                    break;
            }
        if (!empty($filter['sort_type']))
            $sort_type = $filter['sort_type'];

        // Выбираем заказы
        $query = $this->db->placehold("SELECT o.id, o.delivery_id, o.delivery_price, o.separate_delivery, o.payment_method_id, o.payment_price, o.paid, o.payment_date, o.closed, o.discount_type, o.discount, unix_timestamp(o.date) as date,
                                    o.user_id, o.name, o.address, o.phone_code, o.phone, o.phone2_code, o.phone2, o.email, o.comment, o.status_id, o.url, o.total_price, o.total_price_wo_discount, o.note, o.notify_order, o.notify_news, o.moderated, o.allow_payment, o.buy1click, o.ip
                                    FROM __orders AS o
                                        LEFT JOIN __orders_statuses os ON o.status_id = os.id
                                    WHERE 1
                                    $id_filter $status_id_filter $status_type_filter $exclude_status_id_filter $user_filter $keyword_filter $modified_from_filter $moderated_filter ORDER BY $sort $sort_type $sql_limit");
        $this->db->query($query);
        $orders = array();
        foreach($this->db->results() as $order)
            $orders[$order->id] = $order;
        return $orders;
    }

    function count_orders($filter = array())
    {
        $keyword_filter = '';
        $status_id_filter = '';
        $status_type_filter = '';
        $moderated_filter = '';
        $user_filter = '';

        if(isset($filter['status_id']))
            $status_id_filter = $this->db->placehold('AND o.status_id = ?', intval($filter['status_id']));

        if (isset($filter['status_type']))
            $status_type_filter = $this->db->placehold('AND os.status_type = ?', $filter['status_type']);

        if (isset($filter['moderated']))
            $moderated_filter = $this->db->placehold('AND o.moderated = ?', $filter['moderated']);

        if(isset($filter['user_id']))
            $user_filter = $this->db->placehold('AND o.user_id = ?', intval($filter['user_id']));

        if(!empty($filter['keyword']))
        {
            $keyword = $filter['keyword'];
            $keyword_filter = $this->db->placehold('AND (( o.id = "'.mysql_real_escape_string(trim($keyword)).'") OR ( o.name LIKE "%'.mysql_real_escape_string(trim($keyword)).'%") OR (CONCAT("+7",o.phone_code,o.phone) = "'.mysql_real_escape_string(trim($keyword)).'") OR (CONCAT("+7",o.phone2_code,o.phone2) = "'.mysql_real_escape_string(trim($keyword)).'") OR (SELECT COUNT(*) FROM __purchases p WHERE p.order_id=o.id AND (p.sku = "'.mysql_real_escape_string(trim($keyword)).'" OR p.product_name LIKE "%'.mysql_real_escape_string(trim($keyword)).'%"))>0)');
        }

        // Выбираем заказы
        $query = $this->db->placehold("SELECT COUNT(DISTINCT o.id) as count
                                    FROM __orders AS o
                                        LEFT JOIN __orders_statuses os ON o.status_id=os.id
                                    WHERE 1
                                    $status_id_filter $status_type_filter $moderated_filter $user_filter $keyword_filter");
        $this->db->query($query);
        return $this->db->result('count');
    }

    public function update_order($id, $order)
    {
        $query = $this->db->placehold("UPDATE __orders SET ?%, modified=now() WHERE id=? LIMIT 1", $order, intval($id));
        $this->db->query($query);
        $this->update_total_price(intval($id));
        return $id;
    }

    public function delete_order($id)
    {
        if(!empty($id))
        {
            /**$query = $this->db->placehold("DELETE FROM __purchases WHERE order_id=? LIMIT 1", $id);
            $this->db->query($query);**/

            $query = $this->db->placehold("DELETE FROM __orders WHERE id=? LIMIT 1", $id);
            $this->db->query($query);
        }
    }

    public function add_order($order)
    {
        $order = (object)$order;
        //$order->url = md5(uniqid($this->config->salt, true));
        $order->url = uniqid();
        $set_curr_date = '';
        if(empty($order->date))
            $set_curr_date = ', date=now()';
        $query = $this->db->placehold("INSERT INTO __orders SET ?%$set_curr_date", $order);
        $this->db->query($query);
        $id = $this->db->insert_id();
        return $id;
    }


    public function get_purchase($id)
    {
        $query = $this->db->placehold("SELECT * FROM __purchases WHERE id=? LIMIT 1", $id);
        $this->db->query($query);
        $purchase = $this->db->result();
        return $purchase;
    }

    public function get_purchases($filter = array())
    {
        $order_id_filter = '';
        if(!empty($filter['order_id']))
            $order_id_filter = $this->db->placehold('AND order_id in(?@)', (array)$filter['order_id']);

        $query = $this->db->placehold("SELECT * FROM __purchases WHERE 1 $order_id_filter ORDER BY id");
        $this->db->query($query);
        $purchases = $this->db->results();
        return $purchases;
    }

    public function update_purchase($id, $purchase)
    {
        $purchase = (object)$purchase;
        $old_purchase = $this->get_purchase($id);
        if(!$old_purchase)
            return false;

        if(!empty($purchase->variant_id))
        {
            $variant = $this->variants->get_variant($purchase->variant_id);
            if(empty($variant))
                return false;
            $product = $this->products->get_product(intval($variant->product_id));
            if(empty($product))
                return false;
        }

        $order = $this->get_order(intval($old_purchase->order_id));
        if(!$order)
            return false;

        $modificators_set = false;
        if (isset($purchase->modificators) && isset($purchase->modificators_count))
        {
            $modificators_set = true;
            $modificators = $purchase->modificators;
            $modificators_count = $purchase->modificators_count;
            unset($purchase->modificators);
            unset($purchase->modificators_count);
        }

        // Если заказ закрыт, нужно обновить склад при изменении покупки
        if($order->closed && !empty($purchase->amount))
        {
            if($old_purchase->variant_id != $purchase->variant_id)
            {
                if(!empty($old_purchase->variant_id))
                {
                    $query = $this->db->placehold("UPDATE __variants SET stock=stock+? WHERE id=? AND stock IS NOT NULL LIMIT 1", $old_purchase->amount, $old_purchase->variant_id);
                    $this->db->query($query);
                }
                if(!empty($purchase->variant_id))
                {
                    $query = $this->db->placehold("UPDATE __variants SET stock=stock-? WHERE id=? AND stock IS NOT NULL LIMIT 1", $purchase->amount, $purchase->variant_id);
                    $this->db->query($query);
                }
            }
            elseif(!empty($purchase->variant_id))
            {
                $query = $this->db->placehold("UPDATE __variants SET stock=stock+(?) WHERE id=? AND stock IS NOT NULL LIMIT 1", $old_purchase->amount - $purchase->amount, $purchase->variant_id);
                $this->db->query($query);
            }
        }

        if (!empty($purchase->variant_id) /*&& $old_purchase->variant_id != $purchase->variant_id*/)
        {
            $this->db->query("SELECT * FROM __currencies WHERE is_enabled = 1 AND use_admin = 1");
            $admin_currency = $this->db->result();

            if(!isset($purchase->var_amount))
                $purchase->var_amount = 1;

            //надо пересчитать цену варианта
            if (!empty($product) && !empty($product->currency_id))
                $purchase->price = $this->currencies->convert($variant->price, $product->currency_id, false);
            else
                $purchase->price = $this->currencies->convert($variant->price, $admin_currency->id, false);

            $purchase->price *= $purchase->var_amount;

            $purchase->price_for_mul = !empty($purchase->price) ? $purchase->price : null;

            $tmp_modificators = $this->get_purchases_modificators(array('purchase_id'=>$id));
            if (!empty($tmp_modificators))
            {
                //изменим цену варианта в соответствии с модификаторами
                $tmp_price = $purchase->price;
                foreach($tmp_modificators as $idx=>$m){
                    $v = $m->modificator_value;
                    $count = $m->modificator_amount;
                    if ($m->modificator_multi_apply == 0 && $purchase->amount > 1)
                        continue;
                        //$v *= $purchase->amount;
                    if ($m->modificator_type == 'plus_fix_sum')
                        $purchase->price += $v * $count;
                    if ($m->modificator_type == 'minus_fix_sum')
                        $purchase->price -= $v * $count;
                    if ($m->modificator_type == 'plus_percent')
                        $purchase->price += $tmp_price * $v * $count / 100;
                    if ($m->modificator_type == 'minus_percent')
                        $purchase->price -= $tmp_price * $v * $count / 100;
                }

                $purchase->price_for_mul = $purchase->price;
                foreach($tmp_modificators as $idx=>$m){
                    $v = $m->modificator_value;
                    $count = $m->modificator_amount;
                    if (!($m->modificator_multi_apply == 0 && $purchase->amount > 1))
                        continue;
                        //$v *= $purchase->amount;
                    if ($m->modificator_type == 'plus_fix_sum')
                        $purchase->price += $v * $count;
                    if ($m->modificator_type == 'minus_fix_sum')
                        $purchase->price -= $v * $count;
                    if ($m->modificator_type == 'plus_percent')
                        $purchase->price += $tmp_price * $v * $count / 100;
                    if ($m->modificator_type == 'minus_percent')
                        $purchase->price -= $tmp_price * $v * $count / 100;
                }

                $purchase->additional_sum = $purchase->price - $purchase->price_for_mul;
            }
        }

        $query = $this->db->placehold("UPDATE __purchases SET ?% WHERE id=? LIMIT 1", $purchase, intval($id));
        $this->db->query($query);
        $this->update_total_price($order->id);

        if ($modificators_set)
        {
            $this->delete_purchase_modificators($id);

            if (!empty($modificators))
                foreach($modificators as $index=>$m){
                    $modificator = new stdClass;
                    $modificator->purchase_id = $id;
                    $modificator->modificator_id = $m->id;
                    $modificator->modificator_name = $m->name;
                    $modificator->modificator_type = $m->type;
                    $modificator->modificator_value = $m->value;
                    $modificator->modificator_amount = $modificators_count[$index];
                    $modificator->modificator_multi_apply = $m->multi_apply;
                    $modificator->modificator_multi_buy = $m->multi_buy;

                    $modificator->id = $this->orders->add_purchase_modificator($modificator);
                }
        }

        return $id;
    }

    public function add_purchase($purchase)
    {
        $purchase = (object)$purchase;
        if(!empty($purchase->variant_id))
        {
            $variant = $this->variants->get_variant($purchase->variant_id);
            if(empty($variant))
                return false;
            $product = $this->products->get_product(intval($variant->product_id));
            if(empty($product))
                return false;
        }

        $modificators = !empty($purchase->modificators) ? $purchase->modificators : null;
        $modificators_count = !empty($purchase->modificators_count) ? $purchase->modificators_count : null;
        unset($purchase->modificators);
        unset($purchase->modificators_count);

        $order = $this->get_order(intval($purchase->order_id));
        if(empty($order))
            return false;


        if(!isset($purchase->product_id) && isset($variant))
            $purchase->product_id = $variant->product_id;

        if(!isset($purchase->product_name)  && !empty($product))
            $purchase->product_name = $product->name;

        if(!isset($purchase->sku) && !empty($variant))
            $purchase->sku = $variant->sku;

        if(!isset($purchase->variant_name) && !empty($variant))
            $purchase->variant_name = $variant->name;

        $this->db->query("SELECT * FROM __currencies WHERE is_enabled = 1 AND use_admin = 1");
        $admin_currency = $this->db->result();

        if(!isset($purchase->var_amount))
            $purchase->var_amount = 1;

        if(/*!isset($purchase->price) &&*/ !empty($variant))
        {
            if (!empty($product) && !empty($product->currency_id))
                $purchase->price = $this->currencies->convert($variant->price, $product->currency_id, false);
            else
                $purchase->price = $this->currencies->convert($variant->price, $admin_currency->id, false);

            $purchase->price *= $purchase->var_amount;
        }

        $purchase->price_for_mul = !empty($purchase->price) ? $purchase->price : null;
        $purchase->additional_sum = 0;

        if(!isset($purchase->amount))
            $purchase->amount = 1;

        if (!empty($modificators))
        {
            //изменим цену варианта в соответствии с модификаторами
            $tmp_price = $purchase->price;
            foreach($modificators as $idx=>$m){
                $v = $m->value;
                $count = $modificators_count[$idx];
                if ($m->multi_apply == 0 && $purchase->amount > 1)
                    continue;
                    //$v *= $purchase->amount;
                if ($m->type == 'plus_fix_sum')
                    $purchase->price += $v * $count;
                if ($m->type == 'minus_fix_sum')
                    $purchase->price -= $v * $count;
                if ($m->type == 'plus_percent')
                    $purchase->price += $tmp_price * $v * $count / 100;
                if ($m->type == 'minus_percent')
                    $purchase->price -= $tmp_price * $v * $count / 100;
            }

            $purchase->price_for_mul = $purchase->price;
            foreach($modificators as $idx=>$m){
                $v = $m->value;
                $count = $modificators_count[$idx];
                if (!($m->multi_apply == 0 && $purchase->amount > 1))
                    continue;
                    //$v *= $purchase->amount;
                if ($m->type == 'plus_fix_sum')
                    $purchase->price += $v * $count;
                if ($m->type == 'minus_fix_sum')
                    $purchase->price -= $v * $count;
                if ($m->type == 'plus_percent')
                    $purchase->price += $tmp_price * $v * $count / 100;
                if ($m->type == 'minus_percent')
                    $purchase->price -= $tmp_price * $v * $count / 100;
            }

            $purchase->additional_sum = $purchase->price - $purchase->price_for_mul;
        }

        // Если заказ закрыт, нужно обновить склад при добавлении покупки
        if($order->closed && !empty($purchase->amount) && !empty($variant->id))
        {
            $stock_diff = $purchase->amount;
            $query = $this->db->placehold("UPDATE __variants SET stock=stock-? WHERE id=? AND stock IS NOT NULL LIMIT 1", $stock_diff, $variant->id);
            $this->db->query($query);
        }

        $query = $this->db->placehold("INSERT INTO __purchases SET ?%", $purchase);
        $this->db->query($query);
        $purchase_id = $this->db->insert_id();
        $this->update_total_price($order->id);

        $this->orders->delete_purchase_modificators($purchase_id);

        if (!empty($modificators))
            foreach($modificators as $index=>$m){
                $modificator = new stdClass;
                $modificator->purchase_id = $purchase_id;
                $modificator->modificator_id = $m->id;
                $modificator->modificator_name = $m->name;
                $modificator->modificator_type = $m->type;
                $modificator->modificator_value = $m->value;
                $modificator->modificator_amount = $modificators_count[$index];
                $modificator->modificator_multi_apply = $m->multi_apply;
                $modificator->modificator_multi_buy = $m->multi_buy;

                $modificator->id = $this->orders->add_purchase_modificator($modificator);
            }

        return $purchase_id;
    }

    public function delete_purchase($id)
    {
        $purchase = $this->get_purchase($id);
        if(!$purchase)
            return false;

        $order = $this->get_order(intval($purchase->order_id));
        if(!$order)
            return false;

        // Если заказ закрыт, нужно обновить склад при изменении покупки
        if($order->closed && !empty($purchase->amount))
        {
            $stock_diff = $purchase->amount;
            $query = $this->db->placehold("UPDATE __variants SET stock=stock+? WHERE id=? AND stock IS NOT NULL LIMIT 1", $stock_diff, $purchase->variant_id);
            $this->db->query($query);
        }

        $query = $this->db->placehold("DELETE FROM __purchases WHERE id=? LIMIT 1", intval($id));
        $this->db->query($query);
        $this->update_total_price($order->id);
        return true;
    }


    public function close($order_id)
    {
        $order = $this->get_order(intval($order_id));
        if(empty($order))
            return false;

        if(!$order->closed)
        {
            $purchases = $this->get_purchases(array('order_id'=>$order->id));
            foreach($purchases as $purchase)
            {
                $variant = $this->variants->get_variant($purchase->variant_id);
                if(empty($variant) || ($variant->stock<$purchase->amount))
                    return false;
            }
            foreach($purchases as $purchase)
            {
                if(!$variant->infinity)
                {
                    $new_stock = $variant->stock-$purchase->amount;
                    $this->variants->update_variant($variant->id, array('stock'=>$new_stock));
                }
            }
            $query = $this->db->placehold("UPDATE __orders SET closed=1, modified=NOW() WHERE id=? LIMIT 1", $order->id);
            $this->db->query($query);
        }
        return $order->id;
    }

    public function open($order_id)
    {
        $order = $this->get_order(intval($order_id));
        if(empty($order))
            return false;

        if($order->closed)
        {
            $purchases = $this->get_purchases(array('order_id'=>$order->id));
            foreach($purchases as $purchase)
            {
                $variant = $this->variants->get_variant($purchase->variant_id);

                if($variant && !$variant->infinity)
                {
                    $new_stock = $variant->stock+$purchase->amount;
                    $this->variants->update_variant($variant->id, array('stock'=>$new_stock));
                }
            }
            $query = $this->db->placehold("UPDATE __orders SET closed=0, modified=NOW() WHERE id=? LIMIT 1", $order->id);
            $this->db->query($query);
        }
        return $order->id;
    }

    public function pay($order_id)
    {
        $order = $this->get_order(intval($order_id));
        if(empty($order))
            return false;

        if(!$this->close($order->id))
        {
            return false;
        }
        $query = $this->db->placehold("UPDATE __orders SET payment_status=1, payment_date=NOW(), modified=NOW() WHERE id=? LIMIT 1", $order->id);
        $this->db->query($query);
        return $order->id;
    }

    private function update_total_price($order_id)
    {
        $order = $this->get_order(intval($order_id));
        if(empty($order))
            return false;

        $query = $this->db->placehold("SELECT * FROM __purchases p WHERE p.order_id=?", $order_id);
        $this->db->query($query);
        $sum = 0;
        $purchases = $this->db->results();
        foreach($purchases as $p)
            $sum += $p->price_for_mul*$p->amount+$p->additional_sum;
        $sum_wo = $sum + $order->delivery_price*(1-$order->separate_delivery);

        if ($order->discount_type == 0)
            //Скидка в %
            $sum = $sum*(100-$order->discount)/100 + $order->delivery_price*(1-$order->separate_delivery);
        else
            //Скидка в валюте
            $sum = $sum - $order->discount + $order->delivery_price*(1-$order->separate_delivery);

        $payment_method = $this->payment->get_payment_method($order->payment_method_id);
        $payment_price = 0;
        if ($payment_method)
            switch($payment_method->operation_type){
                case "plus_fix_sum":
                    $sum += $payment_method->operation_value;
                    $payment_price = $payment_method->operation_value;
                    break;
                case "minus_fix_sum":
                    $sum -= $payment_method->operation_value;
                    $payment_price = -$payment_method->operation_value;
                    break;
                case "plus_percent":
                    $sum_orig = $sum;
                    $sum *= 100/(100-$payment_method->operation_value);
                    $payment_price = $sum - $sum_orig;
                    break;
                case "minus_percent":
                    $sum_orig = $sum;
                    $sum *= 100/(100+$payment_method->operation_value);
                    $payment_price = $sum - $sum_orig;
                    break;
            }

        $sum_wo += $payment_price;

        $modificators = $this->orders->get_order_modificators(array('order_id' => $order->id));
        if (!empty($modificators))
        {
            $tmp_price = $sum;
            $tmp_price_wo = $sum_wo;
            foreach($modificators as $idx=>$m){
                $v = $m->modificator_value;
                $count = $m->modificator_amount;
                if ($m->modificator_type == 'plus_fix_sum')
                {
                    $sum += $v * $count;
                    $sum_wo += $v * $count;
                }
                if ($m->modificator_type == 'minus_fix_sum')
                {
                    $sum -= $v * $count;
                    $sum_wo -= $v * $count;
                }
                if ($m->modificator_type == 'plus_percent')
                {
                    $sum += $tmp_price * $v * $count / 100;
                    $sum_wo += $tmp_price_wo * $v * $count / 100;
                }
                if ($m->modificator_type == 'minus_percent')
                {
                    $sum -= $tmp_price * $v * $count / 100;
                    $sum_wo -= $tmp_price_wo * $v * $count / 100;
                }
            }
        }

        $query = $this->db->placehold("UPDATE __orders o SET o.payment_price=?, o.total_price=?, o.total_price_wo_discount=?, modified=NOW() WHERE o.id=? LIMIT 1", $payment_price, $sum, $sum_wo, $order->id);
        $this->db->query($query);
        return $order->id;
    }


    public function get_next_order($id, $status = null)
    {
        $f = '';
        if($status!==null)
            $f = $this->db->placehold('AND status_id=?', $status);
        $this->db->query("SELECT MIN(id) as id FROM __orders WHERE id>? $f LIMIT 1", $id);
        $next_id = $this->db->result('id');
        if($next_id)
            return $this->get_order(intval($next_id));
        else
            return false;
    }

    public function get_prev_order($id, $status = null)
    {
        $f = '';
        if($status !== null)
            $f = $this->db->placehold('AND status_id=?', $status);
        $this->db->query("SELECT MAX(id) as id FROM __orders WHERE id<? $f LIMIT 1", $id);
        $prev_id = $this->db->result('id');
        if($prev_id)
            return $this->get_order(intval($prev_id));
        else
            return false;
    }

    public function get_status($id)
    {
        if(is_numeric($id))
            $where = $this->db->placehold(' WHERE id=? ', intval($id));
        else
            $where = $this->db->placehold(' WHERE name=? ', $id);

        $query = $this->db->placehold("SELECT id, name, group_name, is_enabled, position, status_type, css_class FROM __orders_statuses $where LIMIT 1");

        if($this->db->query($query))
            return $this->db->result();
        else
            return false;
    }

    public function get_statuses($filter = array())
    {
        // По умолчанию
        $limit = 100;
        $page = 1;
        $id_filter = '';
        $status_type_filter = '';
        $order = 'position';
        $order_direction = '';

        if(isset($filter['limit']))
            $limit = max(1, intval($filter['limit']));

        if(isset($filter['page']))
            $page = max(1, intval($filter['page']));

        $sql_limit = $this->db->placehold(' LIMIT ?, ? ', ($page-1)*$limit, $limit);

        if(isset($filter['id']))
            $id_filter = $this->db->placehold('AND s.id in(?@)', (array)$filter['id']);

        if(isset($filter['status_type']))
            $status_type_filter = $this->db->placehold('AND status_type=?', $filter['status_type']);

        if (!empty($filter['sort']))
            switch($filter['sort'])
            {
                case 'name':
                    $order = 'name';
                    break;
                case 'position':
                    $order = 'position';
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
        // Выбираем статусы
        $query = $this->db->placehold("SELECT id, name, group_name, is_enabled, position, status_type, css_class
                                    FROM __orders_statuses s
                                    WHERE 1
                                    $id_filter $status_type_filter ORDER BY $order $order_direction $sql_limit");
        $this->db->query($query);
        $statuses = array();
        foreach($this->db->results() as $status)
            $statuses[$status->id] = $status;
        return $statuses;
    }

    function count_statuses($filter = array())
    {
        $status_type_filter = '';

        if(isset($filter['status_type']))
            $status_type_filter = $this->db->placehold('AND status_type=?', $filter['status_type']);

        // Выбираем заказы
        $query = $this->db->placehold("SELECT COUNT(DISTINCT id) as count
                                    FROM __orders_statuses AS s
                                    WHERE 1 $status_type_filter");
        $this->db->query($query);
        return $this->db->result('count');
    }

    public function update_status($id, $status)
    {
        $query = $this->db->placehold("UPDATE __orders_statuses SET ?% WHERE id=? LIMIT 1", $status, intval($id));
        $this->db->query($query);
        return $id;
    }

    public function delete_status($id)
    {
        if(!empty($id))
        {
            $query = $this->db->placehold("UPDATE __orders SET status_id=0 WHERE status_id=? LIMIT 1", $id);
            $this->db->query($query);

            $query = $this->db->placehold("DELETE FROM __orders_statuses WHERE id=? LIMIT 1", $id);
            $this->db->query($query);
        }
        return true;
    }

    public function add_status($status)
    {
        $status = (object)$status;
        $query = $this->db->placehold("INSERT INTO __orders_statuses SET ?%", $status);
        $this->db->query($query);
        $id = $this->db->insert_id();
        $this->db->query("UPDATE __orders_statuses SET position=id WHERE id=?", $id);
        return $id;
    }

    #### PURCHASES MODIFICATORS
    public function get_purchase_modificator($id)
    {
        $query = $this->db->placehold("SELECT * FROM __purchases_modificators WHERE id=? LIMIT 1", $id);
        $this->db->query($query);
        $purchase = $this->db->result();
        return $purchase;
    }

    public function get_purchases_modificators($filter = array())
    {
        $purchase_id_filter = '';
        if(!empty($filter['purchase_id']))
            $purchase_id_filter = $this->db->placehold('AND purchase_id in(?@)', (array)$filter['purchase_id']);

        $query = $this->db->placehold("SELECT * FROM __purchases_modificators WHERE 1 $purchase_id_filter ORDER BY id");
        $this->db->query($query);
        $modificators = $this->db->results();
        return $modificators;
    }

    public function update_purchase_modificator($id, $purchase_modificator)
    {
        $purchase_modificator = (object)$purchase_modificator;
        $old_purchase_modificator = $this->get_purchase_modificator($id);
        if(!$old_purchase_modificator)
            return false;

        $order = $this->get_order(intval($old_purchase_modificator->order_id));
        if(!$order)
            return false;

        $query = $this->db->placehold("UPDATE __purchases_modificators SET ?% WHERE id=? LIMIT 1", $purchase_modificator, intval($id));
        $this->db->query($query);
        $this->update_total_price($order->id);
        return $id;
    }

    public function add_purchase_modificator($purchase_modificator)
    {
        $purchase_modificator = (object)$purchase_modificator;

        $query = $this->db->placehold("INSERT INTO __purchases_modificators SET ?%", $purchase_modificator);
        $this->db->query($query);
        $purchase_id = $this->db->insert_id();
        return $purchase_id;
    }

    public function delete_purchase_modificator($id)
    {
        $query = $this->db->placehold("DELETE FROM __purchases_modificators WHERE id=? LIMIT 1", intval($id));
        $this->db->query($query);
        return true;
    }

    public function delete_purchase_modificators($purchase_id)
    {
        $query = $this->db->placehold("DELETE FROM __purchases_modificators WHERE purchase_id=?", intval($purchase_id));
        $this->db->query($query);
        return true;
    }

    #### ORDERS MODIFICATORS
    public function get_order_modificator($id)
    {
        $query = $this->db->placehold("SELECT * FROM __orders_modificators WHERE id=? LIMIT 1", $id);
        $this->db->query($query);
        $purchase = $this->db->result();
        return $purchase;
    }

    public function get_order_modificators($filter = array())
    {
        $order_id_filter = '';
        if(!empty($filter['order_id']))
            $order_id_filter = $this->db->placehold('AND order_id in(?@)', (array)$filter['order_id']);

        $query = $this->db->placehold("SELECT * FROM __orders_modificators WHERE 1 $order_id_filter ORDER BY id");
        $this->db->query($query);
        $modificators = $this->db->results();
        return $modificators;
    }

    public function update_order_modificator($id, $order_modificator)
    {
        $order_modificator = (object)$order_modificator;
        $old_order_modificator = $this->get_order_modificator($id);
        if(!$old_order_modificator)
            return false;

        $order = $this->get_order(intval($old_order_modificator->order_id));
        if(!$order)
            return false;

        $query = $this->db->placehold("UPDATE __orders_modificators SET ?% WHERE id=? LIMIT 1", $order_modificator, intval($id));
        $this->db->query($query);
        $this->update_total_price($order->id);
        return $id;
    }

    public function add_order_modificator($order_modificator)
    {
        $order_modificator = (object)$order_modificator;

        $query = $this->db->placehold("INSERT INTO __orders_modificators SET ?%", $order_modificator);
        $this->db->query($query);
        $order_id = $this->db->insert_id();
        return $order_id;
    }

    public function delete_order_modificator($id)
    {
        $query = $this->db->placehold("DELETE FROM __orders_modificators WHERE id=? LIMIT 1", intval($id));
        $this->db->query($query);
        return true;
    }

    public function delete_order_modificators($order_id)
    {
        $query = $this->db->placehold("DELETE FROM __orders_modificators WHERE order_id=?", intval($order_id));
        $this->db->query($query);
        return true;
    }
}