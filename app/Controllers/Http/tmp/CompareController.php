<?php
namespace app\controllers;

use app\layer\LayerController;

class CompareController extends LayerController
{
    private $param_url, $params_arr, $options;

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
                if (array_key_exists($x[0], $this->params_arr) && count($x)>1)
                {
                    $this->params_arr[$x[0]] = (array) $this->params_arr[$x[0]];
                    $this->params_arr[$x[0]][] = $x[1];
                }
                else
                {
                    $this->params_arr[$x[0]] = "";
                    if (count($x)>1)
                        $this->params_arr[$x[0]] = $x[1];
                }
            }
        }
    }


    function cmp_tags_arr($a, $b)
    {
        $this->db->query("SELECT id FROM __tags_groups WHERE name=? AND is_auto=?", "Цена", 1);
        $price_group_id = $this->db->result('id');

        $this->db->query("SELECT id FROM __tags_groups WHERE name=? AND is_auto=?", "Бренд", 1);
        $brand_group_id = $this->db->result('id');

        if ($a == $b) {
            return 0;
        }

        if ($a == $price_group_id)
            return -1;

        if ($b == $price_group_id)
            return 1;

        if ($a == $brand_group_id)
            if ($b == $price_group_id)
                return 1;
            else
                return -1;

        if ($b == $brand_group_id)
            return 1;

        return ($a < $b) ? -1 : 1;
    }

    function fetch()
    {
        $ids = array();
        $ajax = false;
        $action = "";
        $format = "";

        $compare_module = $this->furl->get_module_by_name('CompareController');

        foreach($this->params_arr as $p=>$v)
        {
            switch ($p)
            {
                case "ajax":
                    $ajax = true;
                    unset($this->params_arr[$p]);
                    break;
                case "id":
                    $ids = explode(",", $v);
                    break;
                case "action":
                    $action = $v;
                    unset($this->params_arr[$p]);
                    break;
                case "format":
                    $format = $v;
                    break;
                /*case "ids":
                    $ids = explode(",", $v);
                    break;*/
            }
        }

        switch($action){
            case "add":
                $product_id = intval($this->params_arr['product_id']);
                if (!empty($product_id))
                    $this->compare->add_item($product_id);
                $format = "get_href";
                break;
            case "delete":
                $product_id = intval($this->params_arr['product_id']);
                if (empty($ids))
                {
                    if (!empty($product_id))
                        $this->compare->remove_item($product_id);
                }
                else
                    $this->compare->remove_item($product_id);
                    foreach($ids as $index=>$pid)
                        if ($pid == $product_id)
                            unset($ids[$index]);
                $format = "get_href";
                break;
        }



        $products = array();

        if (!empty($ids))
        {
            $products = $this->products->get_products(array('is_visible'=>1, 'id'=>$ids));
            $this->design->assign('ids', join(",",$ids));
        }
        else
        {
            $id_comp = $this->compare->get_compare();
            if (!empty($id_comp))
                $products = $this->products->get_products(array('is_visible'=>1, 'id'=>$id_comp));
        }

        if (!empty($products))
        {
            $product_tags_groups_ids = array();
            foreach($products as $p)
            {
                $p->brand = $p->brand_id ? $this->brands->get_brand($p->brand_id) : false;
                $p->images = $this->image->get_images('products', $p->id);
                $p->image = @reset($p->images);
                $p->variants = $this->variants->get_variants(array('product_id'=>$p->id));
                $p->variant = reset($p->variants);
                $p->badges = $this->badges->get_product_badges($p->id);
                $p->rating = $this->ratings->calc_product_rating($p->id);
                $p->tags = array();
                foreach($this->tags->get_product_tags($p->id) as $tag)
                {
                    if (!array_key_exists($tag->group_id, $p->tags))
                        $p->tags[$tag->group_id] = array();
                    if (!in_array(intval($tag->group_id), $product_tags_groups_ids))
                        $product_tags_groups_ids[] = intval($tag->group_id);
                    $p->tags[$tag->group_id][] = $tag;
                }
            }

            //Сортируем найденные теги чтобы сначала шла цена, потом бренд, потом остальное
            usort($product_tags_groups_ids, array($this, "cmp_tags_arr"));

            $tags_groups = array();
            foreach($this->tags->get_taggroups(array('is_enabled'=>1)) as $group)
                $tags_groups[$group->id] = $group;
            $this->design->assign('products', $products);
            $this->design->assign('product_tags_groups_ids', $product_tags_groups_ids);
            $this->design->assign('tags_groups', $tags_groups);
        }

        if ($ajax)
        {
            switch($format){
                case "get_href":
                    if (empty($ids))
                    {
                        $items = $this->compare->get_compare();
                        $ids_str = join(",", $items);
                    }
                    else
                        $ids_str = join(",", $ids);

                    if (empty($ids))
                        $data = array('success'=>true, 'compare_href'=>$this->config->root_url . $compare_module->url . (empty($ids_str) ? "" : "?id=".$ids_str), 'compare_href_label' => 'Сравнить ('.count($items).')', 'compare_href_show'=>!empty($ids_str), 'compare_items_count'=>count($items));
                    else
                        $data = array('success'=>true, 'compare_href'=>$this->config->root_url . $compare_module->url . (empty($ids_str) ? "" : "?id=".$ids_str), 'compare_href_label' => 'Сравнить ('.count($ids).')', 'compare_href_show'=>!empty($ids_str), 'compare_items_count'=>count($ids));
                    break;
                default:
                    $data = array('success'=>true, 'data'=>$this->design->fetch($this->design->getTemplateDir('frontend').'compare.tpl'), 'meta_title'=>'Сравнение', 'meta_description'=>'Сравнение', 'meta_keywords'=>'Сравнение');
                    break;
            }

            header("Content-type: application/json; charset=UTF-8");
            header("Cache-Control: must-revalidate");
            header("Pragma: no-cache");
            header("Expires: -1");
            print json_encode($data);
            die();
        }

        if($this->page)
        {
            $this->design->assign('meta_title', $this->page->meta_title);
            $this->design->assign('meta_keywords', $this->page->meta_keywords);
            $this->design->assign('meta_description', $this->page->meta_description);
        }

        return $this->design->fetch($this->design->getTemplateDir('frontend').'compare.tpl');
    }
}