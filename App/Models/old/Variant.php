<?php
namespace app\models;

use app\layer\LayerModel;

class Variant extends LayerModel
{
    protected $table = 'mc_variants';

    public $stock_text = '';
    public $stock_sign = '';

    public function afterSelect($rules = null)
    {
        if($this->getCore()->config['without_nulls']){
            $this->price = (int)$this->price;
            $this->price_old = (int)$this->price_old;
        }

        $this->setStockText();
        $this->setStockSign();

        return $this;
    }

    public function setStockText()
    {
        switch($this->stock){
            case -1:
                $this->stock_text = 'под заказ';
                break;
            case 0:
                $this->stock_text = 'нет';
                break;
            default:
                $this->stock_text = 'да';
                break;
        }
    }

    public function setStockSign()
    {
        if(!isset($this->stock)){
            $this->stock_sign = '∞';
        }else{
            $this->stock_sign = $this->stock;
        }
    }

    public function setStock($value)
    {
        if($value === '∞'){
            $this->stock = null;
        }else if($value > 0){
            $this->stock = $value;
        }else if($value == 0){
            $this->stock = 0;
        }else if($value == -1){
            $this->stock = -1;
        }
    }

    public function setSku($value)
    {
        if(!empty($value)){
            $this->sku = $value;
        }
    }

    public function setPrice($value)
    {
        if(!empty($value)){
            $this->price = $value;
        }
    }

    public function setPriceOld($value)
    {
        if(!empty($value)){
            $this->price_old = $value;
        }
    }

    public function setIsVisible($value)
    {
        if(!empty($value)){
            $this->is_visible = $value;
        }
    }






    /**
    * Функция возвращает варианты товара
    * @param    $filter
    * @retval    array
    */
    public function get_variants($filter = array())
    {
        $product_id_filter = '';
        $variant_id_filter = '';
        $instock_filter = '';
        $is_visible_filter = '';

        $order = "v.position";
        $order_direction = "";

        if(!empty($filter['product_id']))
            $product_id_filter = self::getDB()->placehold('AND v.product_id in(?@)', (array)$filter['product_id']);

        if(!empty($filter['id']))
            $variant_id_filter = self::getDB()->placehold('AND v.id in(?@)', (array)$filter['id']);

        if(isset($filter['is_visible']))
            $is_visible_filter = self::getDB()->placehold('AND v.is_visible=?', $filter['is_visible']);

        if(!empty($filter['in_stock']) && $filter['in_stock'])
            $variant_id_filter = self::getDB()->placehold('AND (v.stock<>0 OR v.stock IS NULL)');

        if(!$product_id_filter && !$variant_id_filter)
            return array();

        if (!empty($filter['sort']))
            $order = $filter['sort'];

        if (!empty($filter['sort_type']))
            $order_direction = $filter['sort_type'];

        $query = self::getDB()->placehold("SELECT v.id, v.product_id, v.sku, v.sku_in, v.name, v.price, NULLIF(v.price_old, 0) as price_old, IFNULL(v.stock, ?) as stock, (v.stock IS NULL) as infinity, v.position, v.is_visible
                    FROM __variants AS v
                    WHERE
                    1
                    $product_id_filter
                    $variant_id_filter
                    $is_visible_filter
                    ORDER BY $order $order_direction", $this->settings->max_order_amount>0 ? $this->settings->max_order_amount : 999);

        self::getDB()->query($query);
        return self::getDB()->results();
    }


    public function get_variant($id)
    {
        if(empty($id))
            return false;

        $query = self::getDB()->placehold("SELECT v.id, v.product_id, v.sku, v.sku_in, v.name, v.price, NULLIF(v.price_old, 0) as price_old, IFNULL(v.stock, ?) as stock, (v.stock IS NULL) as infinity, v.position, v.is_visible
                    FROM __variants v WHERE id=?
                    LIMIT 1", $this->settings->max_order_amount>0 ? $this->settings->max_order_amount : 999, $id);

        self::getDB()->query($query);
        $variant = self::getDB()->result();
        return $variant;
    }

    public function update_variant($id, $variant)
    {
        $query = self::getDB()->placehold("UPDATE __variants SET ?% WHERE id=? LIMIT 1", $variant, intval($id));
        self::getDB()->query($query);
        return $id;
    }

    public function add_variant($variant)
    {
        $query = self::getDB()->placehold("INSERT INTO __variants SET ?%", $variant);
        self::getDB()->query($query);
        return self::getDB()->insert_id();
    }

    public function delete_variant($id)
    {
        if(!empty($id))
        {
            $query = self::getDB()->placehold("DELETE FROM __variants WHERE id = ? LIMIT 1", intval($id));
            self::getDB()->query($query);
            //self::getDB()->query('UPDATE __purchases SET variant_id=NULL WHERE variant_id=?', intval($id));
        }
    }
}