<?php
namespace app\controllers;

use app\layer\LayerController;

class SitemapViewController extends LayerController
{
    private $param_url, $options;

    public function __construct()
    {
        parent::__construct();
    }

    public function set_params($url = null, $options = null)
    {
        $this->options = $options;

        $url = urldecode(trim($url, '/'));
        $delim_pos = mb_strpos($url, '?', 0, 'utf-8');

        if ($delim_pos === false)
        {
            $this->param_url = $url;
            $this->params_arr = array();
        }
        else
        {
            $this->param_url = trim(mb_substr($url, 0, $delim_pos, 'utf-8'), '/');
            $url = mb_substr($url, $delim_pos+1, mb_strlen($url, 'utf-8')-($delim_pos+1), 'utf-8');
            $this->params_arr = array();
            foreach(explode("&", $url) as $p)
            {
                $x = explode("=", $p);
                $this->params_arr[$x[0]] = "";
                if (count($x)>1)
                    $this->params_arr[$x[0]] = $x[1];
            }
        }
    }

    /**
     *
     * Отображение
     *
     */
    function fetch()
    {
        $categories_tree = $this->categories->get_categories_tree();
        $this->design->assign('categories_tree', $categories_tree);

        $menus = $this->materials->get_menus();
        $tree = $this->materials->get_menu_items_tree();

        foreach($menus as $index=>$m)
        {
            $menus[$index]->items = array();
            foreach($tree as $t)
                if ($t->menu_id == $m->id)
                    $menus[$index]->items[] = $t;
            if (empty($menus[$index]->items))
                unset($menus[$index]);
        }

        $this->design->assign('menus', $menus);

        $this->design->assign('meta_title', "Карта сайта");
        $this->design->assign('meta_keywords', "Карта сайта");
        $this->design->assign('meta_description', "Карта сайта");

        return $this->design->fetch($this->design->getTemplateDir('frontend').'sitemap.tpl');
    }
}