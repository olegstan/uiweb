<?php
namespace app\controllers;

use app\layer\LayerController;

class SitemapController extends LayerController
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
        $main_menu_item = null;
        $menu_items = $this->materials->get_menu_items_filter(array('is_main'=>1));
        if ($menu_items)
            $main_menu_item = reset($menu_items);

        header("Content-type: text/xml; charset=UTF-8");
        print '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        print '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        // Главная страница
        $url = $this->config->root_url;
        $lastmod = date("Y-m-d");
        print "\t<url>"."\n";
        print "\t\t<loc>$url</loc>"."\n";
        print "\t\t<lastmod>$lastmod</lastmod>"."\n";
        print "\t\t<changefreq>daily</changefreq>"."\n";
        print "\t\t<priority>1.0</priority>"."\n";
        print "\t</url>"."\n";

        $materials = $this->materials->get_materials(array('is_visible'=>1));
        foreach($materials as $material)
        {
            if (!empty($main_menu_item))
                if ($main_menu_item->object_type == "material" && $main_menu_item->object_id == $material->id)
                    continue;
            $url = $this->config->root_url . $this->materials->makeurl_short($material);
            print "\t<url>"."\n";
            print "\t\t<loc>$url</loc>"."\n";
            print "\t\t<lastmod>".date("Y-m-d",$material->updated)."</lastmod>"."\n";
            print "\t\t<changefreq>daily</changefreq>"."\n";
            print "\t\t<priority>0.4</priority>"."\n";
            print "\t</url>"."\n";
        }

        $product_module = $this->furl->get_module_by_name('ProductController');
        $products = $this->products->get_products(array('is_visible' => 1, 'limit' => 100000));
        foreach($products as $product)
        {
            $url = $this->config->root_url . $product_module->url . $product->url . $this->settings->postfix_product_url;
            print "\t<url>"."\n";
            print "\t\t<loc>$url</loc>"."\n";
            print "\t\t<lastmod>".date("Y-m-d",$product->updated)."</lastmod>"."\n";
            print "\t\t<changefreq>daily</changefreq>"."\n";
            print "\t\t<priority>0.4</priority>"."\n";
            print "\t</url>"."\n";
        }

        $products_module = $this->furl->get_module_by_name('ProductsController');
        $categories = $this->categories->get_categories_filter(array('is_visible'=>1, 'limit' => 100000));
        foreach($categories as $category)
        {
            $priority = 0.6;
            if (empty($category->parent_id))
                $priority = 0.8;
            $url = $this->config->root_url . $products_module->url . $category->url . '/';
            print "\t<url>"."\n";
            print "\t\t<loc>$url</loc>"."\n";
            print "\t\t<lastmod>".date("Y-m-d",$category->updated)."</lastmod>"."\n";
            print "\t\t<changefreq>daily</changefreq>"."\n";
            print "\t\t<priority>$priority</priority>"."\n";
            print "\t</url>"."\n";
        }

        print '</urlset>'."\n";
        die();
    }
}