<?php
namespace app\controllers;

use app\layer\LayerController;

class YandexController extends LayerController
{
    private $param_url, $options;

    public function __construct()
    {
        parent::__construct();
    }

    public function set_params($url = null, $options = null)
    {
        $this->param_url = $url;
        $this->options = $options;
    }

    /**
     *
     * Отображение
     *
     */
    function fetch()
    {
        header("Content-type: text/xml; charset=UTF-8");

        // Заголовок
        print "<?xml version='1.0' encoding='UTF-8'?>" . "\r\n" .
        "<!DOCTYPE yml_catalog SYSTEM 'shops.dtd'>" . "\r\n" .
        "<yml_catalog date='".date('Y-m-d H:m')."'>" . "\r\n" .
        "<shop>" . "\r\n" .
        "<name>".$this->settings->site_name."</name>" . "\r\n" .
        "<company>".$this->settings->company_name."</company>" . "\r\n" .
        "<url>".$this->config->root_url."</url>" . "\r\n";

        // Валюты
        $currencies = $this->currencies->get_currencies(array('is_enabled'=>1));
        foreach($currencies as $c)
        {
            if ($c->use_main)
                $main_currency = $c;
            if ($c->use_admin)
                $admin_currency = $c;
        }
        print "<currencies>" . "\r\n";
        foreach($currencies as $c)
            print "<currency id='".$c->code."' rate='".$c->rate_to/$c->rate_from."'/>" . "\r\n";
        print "</currencies>" . "\r\n";

        $categories = $this->categories->get_categories_filter(array('is_visible'=>1, 'limit'=>10000));
        print "<categories>" . "\r\n";
        foreach($categories as $c)
        {
            print "<category id='$c->id'";
            if ($c->parent_id>0)
                print " parentId='$c->parent_id'";
            print ">".htmlspecialchars($c->name)."</category>" . "\r\n";
        }
        print "</categories>" . "\r\n";

        $stock_filter = "";
        if (!$this->settings->yandex_export_all_product)
            $stock_filter = "AND (v.stock > 0 OR v.stock is NULL)";

        $product_module = $this->furl->get_module_by_name('ProductController');

        $this->db->query("SELECT v.price, v.id as variant_id, v.product_id as product_id, IFNULL(v.stock, ?) as stock, p.name as product_name, v.name as variant_name, p.url, p.annotation, p.annotation2, p.currency_id, pc.category_id, b.name as brand_name
                    FROM __variants v
                        INNER JOIN __products p ON v.product_id = p.id
                        LEFT JOIN __products_categories pc ON p.id = pc.product_id AND pc.position=(SELECT MIN(position) FROM __products_categories WHERE product_id=p.id LIMIT 1)
                        LEFT JOIN __categories c ON pc.category_id=c.id
                        LEFT JOIN __brands b ON p.brand_id = b.id
                    WHERE v.is_visible and p.is_visible and c.is_visible and v.price>0 $stock_filter
                    GROUP BY v.id", $this->settings->max_order_amount>0 ? $this->settings->max_order_amount : 999);

        $offers = $this->db->results();

        if ($this->settings->yandex_export_add_tag_adult)
            print "<adult>true</adult>" . "\r\n";

        print "<offers>" . "\r\n";

        foreach($offers as $offer)
        {
            $available = 'true';
            if (isset($offer) && $offer->stock <=0)
                $available = 'false';

            if ($offer->currency_id)
                $price = round($this->currencies->convert($offer->price, $offer->currency_id, false), 2);
            else
                $price = round($this->currencies->convert($offer->price, $admin_currency->id, false), 2);

            $images = $this->image->get_images('products', $offer->product_id);
            $image = @reset($images);

            print "<offer id='$offer->variant_id' available='$available'>" . "\r\n" .
                "<url>" . $this->config->root_url . $product_module->url . $offer->url . $this->settings->postfix_product_url . "?variant=" . $offer->variant_id . "</url>" . "\r\n" .
                "<price>$price</price>" . "\r\n" .
                "<currencyId>".$main_currency->code."</currencyId>" . "\r\n" .
                "<categoryId>".$offer->category_id."</categoryId>" . "\r\n";

            if (!empty($image))
                print "<picture>".$this->design->resize_modifier($image->filename, 'products', 900, 900)."</picture>" . "\r\n";

            print "<name>".htmlspecialchars($offer->product_name)."</name>" . "\r\n";

            if (!empty($offer->brand_name))
                print "<vendor>".htmlspecialchars($offer->brand_name)."</vendor>" . "\r\n";

            if (!empty($offer->annotation))
                print "<description>".htmlspecialchars(strip_tags($offer->annotation))."</description>" . "\r\n";
            else
                if (!empty($offer->annotation2))
                    print "<description>".htmlspecialchars(strip_tags($offer->annotation2))."</description>" . "\r\n";
                else
                    print "<description></description>" . "\r\n";

            $sales_notes_text = "";

            if ($offer->stock == 0)
                $sales_notes_text = "Под заказ";

            if ($this->settings->cart_order_min_price > 0)
            {
                if (!empty($sales_notes_text))
                    $sales_notes_text .= ". ";
                $sales_notes_text .= "Минимальная сумма заказа " . $this->settings->cart_order_min_price . " " . $main_currency->sign_simple;
            }

            if (!empty($sales_notes_text))
                print "<sales_notes>" . $sales_notes_text . "</sales_notes>" . "\r\n";

            $product_tags = $this->tags->get_product_tags($offer->product_id);
            if (!empty($product_tags))
                foreach($product_tags as $t)
                {
                    if (!$t->export2yandex)
                        continue;
                    print "<param name=\"" . $t->group_name . "\"" . (empty($t->postfix) ? "" : " unit=\"".$t->postfix."\"") . ">" . $t->name . "</param>" . "\r\n";
                }

            print "</offer>" . "\r\n";
        }

        print "</offers>" . "\r\n";

        print "</shop>" . "\r\n" .
            "</yml_catalog>";

        die();
    }
}