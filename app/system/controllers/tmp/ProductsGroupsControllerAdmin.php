<?php
namespace app\controllers;

use core\Controller;

class ProductsGroupsControllerAdmin extends Controller
{
    private $param_url, $params_arr, $options;

    public function set_params($url = null, $options = null){
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

    function fetch()
    {
        if (!(isset($_SESSION['admin']) && $_SESSION['admin']=='admin'))
            header("Location: http://".$_SERVER['SERVER_NAME']."/admin/login/");

        $products_filter = array();
        $products_filter['limit'] = 10000;
        $ajax = false;
        $output_format = "";
        foreach($this->params_arr as $p=>$v)
        {
            switch ($p)
            {
                case "save_positions":
                    $menu_items = $this->request->post('menu');

                    foreach($menu_items as $position=>$mi)
                        $this->blocks->update_block($mi['id'], array('position'=>$position));

                    header("Content-type: application/json; charset=UTF-8");
                    header("Cache-Control: must-revalidate");
                    header("Pragma: no-cache");
                    header("Expires: -1");
                    print json_encode(1);
                    die();
                    break;
                case "ajax":
                    $ajax = intval($v);
                    unset($this->params_arr[$p]);
                    break;
                case "keyword":
                    if (!empty($v))
                    {
                        $products_filter[$p] = $v;
                        $this->design->assign('keyword', $v);
                    }
                    else
                        unset($this->params_arr[$p]);
                    break;
                case "format":
                    $products_filter['limit'] = 100;
                    $output_format = $v;
                    unset($this->params_arr[$p]);
                    break;
                case "exception":
                    $products_filter[$p] = explode(",", $v);
                    break;
            }
        }

        $this->design->assign('current_params', $this->params_arr);

        if ($ajax){
            switch($output_format){
                case "product-addrelated":
                    $products = $this->products->get_products($products_filter);
                    foreach($products as $index=>$product)
                    {
                        $products[$index]->images = $this->image->get_images('products', $product->id);
                        $products[$index]->image = reset($products[$index]->images);
                        $products[$index]->variants = $this->variants->get_variants(array('product_id'=>$product->id));
                        $products[$index]->variant = reset($products[$index]->variants);

                        $products[$index]->in_stock = false;
                        $products[$index]->in_order = false;
                        foreach($products[$index]->variants as $rv)
                            if ($rv->stock > 0)
                                $products[$index]->in_stock = true;
                            else
                                if ($rv->stock < 0)
                                    $products[$index]->in_order = true;
                    }
                    $this->design->assign('products', $products);
                    $data = $this->design->fetch($this->design->getTemplateDir('admin').'product-addrelated.tpl');
                    break;
            }

            header("Content-type: application/json; charset=UTF-8");
            header("Cache-Control: must-revalidate");
            header("Pragma: no-cache");
            header("Expires: -1");
            print json_encode($data);
            die();
        }

        $this->design->assign('params_arr', $this->params_arr);
        $this->design->assign('edit_module', $this->furl->get_module_by_name('ProductsGroupControllerAdmin'));
        $this->design->assign('product_module', $this->furl->get_module_by_name('ProductControllerAdmin'));

        $groups_on_main = $this->blocks->get_blocks();
        foreach($groups_on_main as $index=>$g)
            $groups_on_main[$index]->products = $this->blocks->get_related_products(array('group_id'=>$g->id));
        $this->design->assign('groups_on_main', $groups_on_main);
        $this->design->assign('groups_on_main_count', $this->blocks->count_blocks());

        return $this->design->fetch($this->design->getTemplateDir('admin').'products-groups.tpl');
    }
}