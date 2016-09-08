<?php
namespace app\models;

use app\layer\LayerModel;

class Currency extends LayerModel
{
    protected $table = 'mc_currencies';



    private $currencies = array();
    private $currency;

//    public function __construct()
//    {
//        parent::__construct();
//
//        if(isset($this->settings->price_decimals_point))
//            $this->decimals_point = $this->settings->price_decimals_point;
//
//        if(isset($this->settings->price_thousands_separator))
//            $this->thousands_separator = $this->settings->price_thousands_separator;
//
//        $this->design->smarty->registerPlugin('modifier', 'convert', array($this, 'convert'));
//        //$this->design->smarty->registerPlugin('modifier', 'convert2', array($this, 'convert2'));
//
//        $this->init_currencies();
//    }


    private function init_currencies()
    {
        $this->currencies = array();
        // Выбираем из базы валюты
        $query = "SELECT id, name, sign, sign_simple, code, rate_from, rate_to, cents, position, is_enabled, use_main, use_admin, auto_update FROM __currencies ORDER BY position";
        $this->db->query($query);

        $results = $this->db->results();

        foreach($results as $c)
        {
            $this->currencies[$c->id] = $c;
        }

        $this->currency = reset($this->currencies);
    }

    public function get_currencies($filter = array())
    {
        $currencies = array();
        foreach($this->currencies as $id=>$currency)
            if((isset($filter['is_enabled']) && $filter['is_enabled'] == 1 && $currency->is_enabled) ||
                empty($filter['is_enabled']) ||
                (isset($filter['use_admin']) && $filter['use_admin'] == 1 && $currency->use_admin) ||
                (isset($filter['use_main']) && $filter['use_main'] == 1 && $currency->use_main))
                $currencies[$id] = $currency;

        return $currencies;
    }

    public function get_currency($id = null)
    {
        if (empty($id) && is_integer($id))
            return false;

        if(!empty($id) && is_integer($id) && isset($this->currencies[$id]))
            return $this->currencies[$id];

        if(!empty($id) && is_string($id))
        {
            foreach($this->currencies as $currency)
            {
                if($currency->code == $id)
                    return $currency;
            }
        }

        return $this->currency;
    }

    public function add_currency($currency)
    {
        $currency = (array)$currency;

        $query = $this->db->placehold('INSERT INTO __currencies SET ?%', $currency);
        if(!$this->db->query($query))
            return false;
        $id = $this->db->insert_id();
        $this->db->query("UPDATE __currencies SET position=id WHERE id=?", $id);

        if (is_array($currency) && isset($currency['use_admin']) && $currency['use_admin'])
            $this->db->query("UPDATE __currencies SET use_admin=0 WHERE id<>?", $id);

        if (is_array($currency) && isset($currency['use_main']) && $currency['use_main'])
            $this->db->query("UPDATE __currencies SET use_main=0 WHERE id<>?", $id);

        $this->db->query("SELECT COUNT(*) as kol FROM __currencies WHERE use_admin=1");
        $c = $this->db->result('kol');
        if ($c == 0)
            $this->db->query("UPDATE __currencies SET use_admin=1 WHERE id=?", $id);

        $this->db->query("SELECT COUNT(*) as kol FROM __currencies WHERE use_main=1");
        $c = $this->db->result('kol');
        if ($c == 0)
            $this->db->query("UPDATE __currencies SET use_main=1 WHERE id=?", $id);

        $this->init_currencies();
        return $id;
    }

    public function update_currency($id, $currency)
    {
        $currency = (array)$currency;
        $query = $this->db->placehold('UPDATE __currencies SET ?% WHERE id in (?@)', $currency, (array)$id);
        if(!$this->db->query($query))
            return false;
        if (is_array($currency) && isset($currency['use_admin']) && $currency['use_admin'])
            $this->db->query("UPDATE __currencies SET use_admin=0 WHERE id<>?", $id);
        if (is_array($currency) && isset($currency['use_main']) && $currency['use_main'])
            $this->db->query("UPDATE __currencies SET use_main=0 WHERE id<>?", $id);

        $this->db->query("SELECT COUNT(*) as kol FROM __currencies WHERE use_admin=1");
        $c = $this->db->result('kol');
        if ($c == 0)
            $this->db->query("UPDATE __currencies SET use_admin=1 WHERE id=?", $id);

        $this->db->query("SELECT COUNT(*) as kol FROM __currencies WHERE use_main=1");
        $c = $this->db->result('kol');
        if ($c == 0)
            $this->db->query("UPDATE __currencies SET use_main=1 WHERE id=?", $id);

        $this->init_currencies();
        return $id;
    }

    public function delete_currency($id)
    {
        if(!empty($id))
        {
            $query = $this->db->placehold("DELETE FROM __currencies WHERE id=? LIMIT 1", intval($id));
            $this->db->query($query);
        }
        $this->init_currencies();
    }

    public function update_currency_rate($iso)
    {
        $rate = 1;
        $cbr_file = file_get_contents('http://www.cbr.ru/scripts/XML_daily.asp?date_req='.date("d/m/Y"), 'r');
        if ($cbr_file){
            preg_match_all("#<Valute ID=\"[^\"]+[^>]+>[^>]+>([^<]+)[^>]+>[^>]+>([^<]+)[^>]+>[^>]+>[^>]+>[^>]+>([^<]+)[^>]+>[^>]+>([^<]+)#i", $cbr_file, $matches, PREG_SET_ORDER);

            foreach($matches as $cur)
                if ($cur[2] == $iso)
                    $rate = str_replace(",",".",$cur[4]);
        }
        return $rate;
    }

    /*public function convert($price, $currency_id = null, $format = true)
    {
        if(isset($currency_id))
        {
            if(is_numeric($currency_id))
                $currency = $this->get_currency((integer)$currency_id);
            else
                $currency = $this->get_currency((string)$currency_id);
        }
        elseif(isset($_SESSION['currency_id']))
            $currency = $this->get_currency($_SESSION['currency_id']);
        else
            $currency = reset($this->get_currencies(array('use_admin'=>1)));

        $result = $price;

        if(!empty($currency))
        {
            // Умножим на курс валюты
            $result = $result*$currency->rate_from/$currency->rate_to;
            // Точность отображения, знаков после запятой
            $precision = isset($currency->cents)?$currency->cents:2;
        }

        // Форматирование цены
        if($format)
            $result = number_format($result, $precision, $this->settings->decimals_point, $this->settings->thousands_separator);
        else
            $result = round($result, $precision);

        return $result;
    }*/

    public function convert($price, $currency_id = null, $format = true)
    {
        if(isset($currency_id))
            $currency = $this->get_currency((integer)$currency_id);
        /*elseif(isset($_SESSION['currency_id']))
            $currency = $this->get_currency($_SESSION['currency_id']);*/
        else
        {
            $this->db->query("SELECT * FROM __currencies WHERE is_enabled=1 AND use_main=1");
            $currency = $this->db->result();
            //$currency = reset($this->get_currencies(array('is_enabled'=>1, 'use_main'=>1)));
        }

        $this->db->query("SELECT * FROM __currencies WHERE is_enabled = 1 AND use_main = 1");
        $main_currency = $this->db->result();

        $result = $price;

        if(!empty($currency))
        {
            // Умножим на курс валюты
            $result = $result*$currency->rate_to/$currency->rate_from;

            // Точность отображения, знаков после запятой
            $precision = isset($main_currency->cents)?$main_currency->cents:2;
        }

        // Форматирование цены
        if($format)
            $result = number_format($result, $precision, $this->settings->decimals_point, $this->settings->thousands_separator);
        else
            $result = round($result, $precision);

        return $result;
    }
}