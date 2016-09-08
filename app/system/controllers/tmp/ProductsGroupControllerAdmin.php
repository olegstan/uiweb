<?php
namespace app\controllers;

use core\Controller;

class ProductsGroupControllerAdmin extends Controller
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

        $main_module =  $this->furl->get_module_by_name('ProductsGroupsControllerAdmin');
        $edit_module = $this->furl->get_module_by_name('ProductsGroupControllerAdmin');
        $this->design->assign('product_module', $this->furl->get_module_by_name('ProductControllerAdmin'));
        $this->design->assign('main_module', $main_module);

        if ($this->request->method('post'))
        {
            $group_on_main = new stdClass();
            $group_on_main->id = $this->request->post('id', 'integer');
            $group_on_main->name = $this->request->post('name');
            $group_on_main->is_visible = $this->request->post('is_visible', 'boolean');
            $group_on_main->products_count = $this->request->post('products_count', 'integer');
            $group_on_main->show_mode = $this->request->post('show_mode');
            $group_on_main->css_class = $this->request->post('css_class');

            $related_ids = $this->request->post('related_ids');

            $close_after_save = $this->request->post('close_after_save', 'integer');
            $add_after_save = $this->request->post('add_after_save', 'integer');

            if (empty($group_on_main->name))
            {
                $this->design->assign('message_error', 'empty_name');
            }
            else
            {
                if(empty($group_on_main->id))
                {
                    $group_on_main->id = $this->blocks->add_block($group_on_main);
                    $this->design->assign('message_success', 'added');
                }
                else
                {
                    $old_block = $this->blocks->get_block($group_on_main->id);
                    $this->blocks->update_block($group_on_main->id, $group_on_main);
                    $this->design->assign('message_success', 'updated');
                }

                // Сопутствующие товары
                $query = $this->db->placehold('DELETE FROM __groups_related_products WHERE group_id=?', $group_on_main->id);
                $this->db->query($query);
                if(is_array($related_ids))
                {
                    $pos = 0;
                    foreach($related_ids  as $i=>$related_id)
                        $this->blocks->add_related_product($group_on_main->id, $related_id, $pos++);
                }

                $group_on_main = $this->blocks->get_block(intval($group_on_main->id));

                if ($close_after_save && $main_module)
                {
                    $href = $this->config->root_url.$main_module->url;
                    header("Location: ".$href);
                }

                if ($add_after_save)
                {
                    $href = $this->config->root_url.$edit_module->url;
                    header("Location: ".$href);
                }
            }
        }
        else
            {
                $id = 0;
                $mode = "";
                $response['success'] = false;
                $json_answer = false;

                foreach($this->params_arr as $p=>$v)
                {
                    switch ($p)
                    {
                        case "id":
                            if (is_numeric($v))
                                $id = intval($v);
                            break;
                        case "mode":
                            $mode = strval($v);
                            break;
                        case "ajax":
                            $json_answer = true;
                            unset($this->params_arr[$p]);
                            break;
                    }
                }

                if (!empty($id))
                    $group_on_main = $this->blocks->get_block($id);

                if (!empty($mode) && isset($group_on_main) && !empty($group_on_main))
                    switch($mode){
                        case "delete":
                            $this->blocks->delete_block($id);
                            $response['success'] = true;
                            break;
                        case "toggle":
                            $this->blocks->update_block($id, array('is_visible'=>1-$group_on_main->is_visible));
                            $response['success'] = true;
                            break;
                    }

                if ($json_answer)
                {
                    header("Content-type: application/json; charset=UTF-8");
                    header("Cache-Control: must-revalidate");
                    header("Pragma: no-cache");
                    header("Expires: -1");
                    print json_encode($response);
                    die();
                }
            }
        
        if (isset($group_on_main))
        {
            $this->design->assign('group_on_main', $group_on_main);

            if ($group_on_main->id){
                //Сопутствующие товары
                $related_products = $this->blocks->get_related_products(array('group_id'=>$group_on_main->id));
                if ($related_products)
                {
                    foreach($related_products as $index=>$related)
                    {
                        $related_products[$index] = $this->products->get_product($related->product_id);

                        $related_products[$index]->variants = $this->variants->get_variants(array('product_id'=>$related->product_id));
                        $related_products[$index]->in_stock = false;
                        $related_products[$index]->in_order = false;
                        foreach($related_products[$index]->variants as $rv)
                            if ($rv->stock > 0)
                                $related_products[$index]->in_stock = true;
                            else
                                if ($rv->stock < 0)
                                    $related_products[$index]->in_order = true;

                        $related_products[$index]->images = $this->image->get_images('products', $related->product_id);
                        if (isset($related_products[$index]->images[0]))
                            $related_products[$index]->image = $related_products[$index]->images[0];
                    }
                    $this->design->assign('related_products', $related_products);
                }
            }
        }

        $this->design->assign('current_params', $this->params_arr);

        return $this->design->fetch($this->design->getTemplateDir('admin').'products-group.tpl');
        }
}