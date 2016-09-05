<?php
namespace app\controllers;

use core\Controller;

class BrandControllerAdmin extends Controller
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

        $edit_module = $this->furl->get_module_by_name('BrandControllerAdmin');
        $main_module =  $this->furl->get_module_by_name('BrandsControllerAdmin');
        $brand_frontend_module = $this->furl->get_module_by_name('BrandController');
        $this->design->assign('main_module', $main_module);
        $this->design->assign('brand_frontend_module', $brand_frontend_module);

        if ($this->request->method('post') && !isset($_FILES['uploaded-images']))
        {
            $brand = new stdClass();
            $brand->id = $this->request->post('id', 'integer');
            $brand->url = $this->request->post('url');
            $brand->name = $this->request->post('name');
            $brand->frontend_name = $this->request->post('frontend_name');

            $brand->meta_title = $this->request->post('meta_title');
            $brand->meta_keywords = $this->request->post('meta_keywords');
            $brand->meta_description = $this->request->post('meta_description');

            $brand->description = $this->request->post('description');
            $brand->description2 = $this->request->post('description2');

            $brand->is_visible = $this->request->post('is_visible', 'boolean');
            $brand->css_class = $this->request->post('css_class');
            $brand->is_popular = $this->request->post('is_popular', 'boolean');
            $brand->tag_id = $this->request->post('tag_id');

            $images_position = $this->request->post('images_position');

            $close_after_save = $this->request->post('close_after_save', 'integer');
            $add_after_save = $this->request->post('add_after_save', 'integer');
            $recreate_seo = $this->request->post('recreate_seo', 'integer');

            if ((empty($brand->meta_keywords) && !empty($brand->description)) || $recreate_seo)
            {
                $str = mb_strtolower(html_entity_decode($brand->description, ENT_COMPAT, 'utf-8'), 'utf-8');
                $str = preg_replace("/[^a-zа-я0-9\s]/u", " ", $str);
                $str = preg_replace("/\s+/u", "  ", $str);                //заменим пробелы на двойные пробелы, чтоб следующая регулярка работала, иначе между словами будет общий пробел и условие не пройдет
                $str = preg_replace("/(\s[^\s]{1,3}\s)+/u", " ", $str);    //remove words with length<=3
                $str = preg_replace("/(\s\s)+/u", " ", $str);            //remove double spaces
                $str = trim($str, 'p ');
                $str = preg_replace("/\s+/u", ", ", $str);
                if (mb_strlen($str, 'utf-8') <= 200)
                    $str = mb_substr($str, 0, 200, 'utf-8');
                else
                {
                    $space_pos = mb_strpos($str, ' ', 200, 'utf-8');
                    if ($space_pos !== false)
                        $str = mb_substr($str, 0, $space_pos, 'utf-8');
                }
                $brand->meta_keywords = $str;
            }

            if ((empty($brand->meta_description) && !empty($brand->description)) || $recreate_seo)
            {
                $str = preg_replace("/[^a-zA-Zа-яА-Я0-9\s]/u", " ", html_entity_decode($brand->description, ENT_COMPAT, 'utf-8'));
                $str = preg_replace("/\s\s+/u", " ", $str);
                $str = trim($str, 'p ');
                if (mb_strlen($str, 'utf-8') <= 200)
                    $str = mb_substr($str, 0, 200, 'utf-8');
                else
                {
                    $space_pos = mb_strpos($str, ' ', 200, 'utf-8');
                    if ($space_pos !== false)
                        $str = mb_substr($str, 0, $space_pos, 'utf-8');
                }
                $brand->meta_description = $str;
            }

            if ((empty($brand->meta_title) && (!empty($brand->name) || !empty($brand->frontend_name))) || $recreate_seo)
                if (!empty($brand->frontend_name))
                    $brand->meta_title = $brand->frontend_name;
                else
                    $brand->meta_title = $brand->name;

            if ($recreate_seo)
                $brand->url = '';

            if(empty($brand->id))
            {
                $brand->id = $this->brands->add_brand($brand);
                $this->design->assign('message_success', 'added');

                $temp_id = $this->request->post('temp_id');
                if ($temp_id)
                {
                    $images = $this->image_temp->get_images($temp_id);
                    if (!empty($images)){
                        foreach($images as $i){
                            $fname = $this->config->root_dir . '/' . $this->config->original_tempimages_dir . $i->filename;
                            $this->image->add_internet_image('brands', $brand->id, $this->furl->generate_url($brand->name), $fname);
                            $this->image_temp->delete_image($i->temp_id, $i->id);
                        }
                    }
                }
            }
            else
            {
                $old_brand = $this->brands->get_brand($brand->id);
                $this->brands->update_brand($brand->id, $brand);

                //Если название изменилось, то обновим название тега
                if (($old_brand->name != $brand->name) && ($brand->tag_id > 0))
                    $this->tags->update_tag($brand->tag_id, array('name'=>$brand->name));

                $this->design->assign('message_success', 'updated');
            }

            if (!empty($images_position))
            {
                $ip_arr = explode(',', $images_position);
                foreach($ip_arr as $pos=>$id)
                    $this->image->update_image($id, array('position'=>$pos));
            }

            $brand = $this->brands->get_brand(intval($brand->id));
            $brand_tag = $this->tags->get_tag($brand->tag_id);

            if ($brand->tag_id == 0 || !$brand_tag)
            {
                $this->db->query("SELECT id FROM __tags_groups WHERE name=? AND is_auto=?", "Бренд", 1);
                $tag_group_id = $this->db->result('id');

                $new_tag = new StdClass;
                $new_tag->group_id = $tag_group_id;
                $new_tag->name = $brand->name;
                $new_tag->is_enabled = 1;
                $new_tag->is_auto = 1;
                $brand->tag_id = $this->tags->add_tag($new_tag);
                $this->brands->update_brand($brand->id, array('tag_id'=>$brand->tag_id));

                $this->db->query("SELECT id FROM __products WHERE brand_id=?", $brand->id);
                $products_ids = $this->db->results("id");
                foreach($products_ids as $product_id)
                    $this->tags->add_product_tag($product_id, $brand->tag_id);
            }

            $return_page = $this->request->post('return_page');

            if ($close_after_save && $main_module)
                header("Location: ".$this->config->root_url.$main_module->url.($return_page>1?'?page='.$return_page:''));

            if ($add_after_save)
                header("Location: ".$this->config->root_url.$edit_module->url.($return_page>1?'?page='.$return_page:''));
        }
        else
            if ($this->request->method('post') && isset($_FILES['uploaded-images']))
            {
                $uploaded = $this->request->files('uploaded-images');
                $object_id = $this->request->post('object_id');

                if (is_numeric($object_id))
                {
                    $tmp_object = $this->brands->get_brand($object_id);
                    foreach($uploaded as $index=>$ufile)
                        $img = $this->image->add_image('brands', $object_id, $this->furl->generate_url($tmp_object->name), $ufile['name'], $ufile['tmp_name']);
                }
                else
                    foreach($uploaded as $index=>$ufile)
                        $img = $this->image_temp->add_image($object_id, $ufile['name'], $ufile['tmp_name']);

                header("Content-type: application/json; charset=UTF-8");
                header("Cache-Control: must-revalidate");
                header("Pragma: no-cache");
                header("Expires: -1");
                print json_encode(1);
                die();
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
                            else
                                $id = strval($v);
                            break;
                        case "mode":
                            $mode = strval($v);
                            break;
                        case "ajax":
                            $json_answer = true;
                            unset($this->params_arr[$p]);
                            break;
                        case "page":
                            $this->design->assign('page', intval($v));
                            unset($this->params_arr[$p]);
                            break;
                    }
                }

                if (!empty($id))
                    $brand = $this->brands->get_brand($id);
                else
                {
                    $temp_id = uniqid();
                    $this->design->assign('temp_id', $temp_id);

                    $images = $this->image_temp->get_images($temp_id);
                    if (!empty($images)){
                        foreach($images as $i){
                            $fname = $this->config->root_dir . '/' . $this->config->original_tempimages_dir . $i->filename;
                            $this->image_temp->delete_image($i->temp_id, $i->id);
                        }
                    }
                }

                if (!empty($mode) && ((isset($brand) && !empty($brand)) || !is_numeric($id)))
                    switch($mode){
                        case "delete":
                            if ($brand->tag_id > 0)
                                $this->tags->delete_tag($brand->tag_id);
                            $this->brands->delete_brand($id);
                            $response['success'] = true;
                            break;
                        case "toggle":
                            $this->brands->update_brand($id, array('is_visible'=>1-$brand->is_visible));
                            $response['success'] = true;
                            break;
                        case "toggle_popular":
                            $this->brands->update_brand($id, array('is_popular'=>1-$brand->is_popular));
                            $response['success'] = true;
                            break;
                        case "get_images":
                            $this->design->assign('object', $brand);

                            if (is_numeric($id))
                                $images = $this->image->get_images('brands', $id);
                            else
                            {
                                $images = $this->image_temp->get_images($id);
                                $this->design->assign('temp_id', $id);
                            }

                            $this->design->assign('images', $images);
                            $this->design->assign('images_object_name', 'brands');
                            $response['success'] = true;
                            $response['data'] = $this->design->fetch($this->design->getTemplateDir('admin').'object-images.tpl');
                            break;
                        case "delete_image":
                            $image_id = intval($this->params_arr['image_id']);

                            if (is_numeric($id))
                                $this->image->delete_image('brands', $id, $image_id);
                            else
                                $this->image_temp->delete_image($id, $image_id);

                            $response['success'] = true;
                            break;
                        case "upload_internet_image":
                            $image_url = base64_decode($this->params_arr['image_url']);

                            if (is_numeric($id))
                                $this->image->add_internet_image('brands', $id, $this->furl->generate_url($brand->name), $image_url);
                            else
                                $this->image_temp->add_internet_image($id, $image_url);

                            $response['success'] = true;
                            break;
                    }

                if ($json_answer)
                {
                    header("Content-type: application/json; charset=UTF-8");
                    header("Cache-Control: must-revalidate");
                    header("Pragma: no-cache");
                    header("Expires: -1");
                    if ($mode == "get_tags")
                        print json_encode($response['data']);
                    else
                        print json_encode($response);
                    die();
                }
            }
        if (isset($brand))
        {
            $this->design->assign('brand', $brand);
            $images = $this->image->get_images('brands', $brand->id);
            $this->design->assign('images', $images);
            $brand_tag = $this->tags->get_tag($brand->tag_id);
            $this->design->assign('brand_tag', $brand_tag);
        }

        return $this->design->fetch($this->design->getTemplateDir('admin').'brand.tpl');
    }
}