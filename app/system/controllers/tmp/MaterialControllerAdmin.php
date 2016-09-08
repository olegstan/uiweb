<?php
namespace app\controllers;

use core\Controller;

class MaterialControllerAdmin extends Controller
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

        $main_module =  $this->furl->get_module_by_name('MaterialsControllerAdmin');
        $edit_module = $this->furl->get_module_by_name('MaterialControllerAdmin');
        $this->design->assign('main_module', $main_module);

        if ($this->request->method('post') && !isset($_FILES['uploaded-images']) && !isset($_FILES['gallery-uploaded-images']) && !isset($_FILES['uploaded-attachments']))
        {
            $material = new stdClass();
            $material->id = $this->request->post('id', 'integer');
            $material->parent_id = $this->request->post('parent_id', 'integer');

            $material->name = $this->request->post('name');
            $material->title = $this->request->post('title', 'string');
            $material->url = $this->request->post('url');
            $material->is_visible = $this->request->post('is_visible', 'boolean');

            $material_date = $this->request->post('date_date');
            $material_time = $this->request->post('date_time');
            $datetime = DateTime::createFromFormat('d.m.Y G:i', $material_date." ".$material_time);
            $material->date = $datetime->format('Y-m-d G:i');

            /*$material_date = $this->request->post('date_date');
            $datetime = DateTime::createFromFormat('d.m.Y', $material_date);
            $material->date = $datetime->format('Y-m-d');*/

            $material->meta_title = $this->request->post('meta_title');
            $material->meta_keywords = $this->request->post('meta_keywords');
            $material->meta_description = $this->request->post('meta_description');

            $material->description = $this->request->post('description');
            $material->css_class = $this->request->post('css_class');
            $material->script_text = $this->request->post('script_text');

            $material->gallery_mode = $this->request->post('gallery_mode');
            $material->gallery_tile_width = $this->request->post('gallery_tile_width');
            $material->gallery_tile_height = $this->request->post('gallery_tile_height');
            $material->gallery_list_width = $this->request->post('gallery_list_width');
            $material->gallery_list_height = $this->request->post('gallery_list_height');

            $attachments_position = $this->request->post('attachments_position');

            $close_after_save = $this->request->post('close_after_save', 'integer');
            $add_after_save = $this->request->post('add_after_save', 'integer');
            $recreate_seo = $this->request->post('recreate_seo', 'integer');

            if ((empty($material->meta_keywords) && !empty($material->description)) || $recreate_seo)
            {
                $str_name = "";
                /*if (!empty($material->name))
                    $str_name = mb_strtolower(strip_tags(html_entity_decode($material->name)), 'utf-8');*/
                $str = mb_strtolower(strip_tags(html_entity_decode($material->description, ENT_COMPAT, 'utf-8')), 'utf-8');
                $str = preg_replace("/[^a-zа-я0-9\s]/u", " ", $str);
                $str = preg_replace("/\s+/u", "  ", $str);                //заменим пробелы на двойные пробелы, чтоб следующая регулярка работала, иначе между словами будет общий пробел и условие не пройдет
                $str = preg_replace("/(\s[^\s]{1,3}\s)+/u", " ", $str);    //remove words with length<=3
                $str = preg_replace("/(\s\s)+/u", " ", $str);            //remove double spaces
                $str = trim($str, 'p ');
                $str = preg_replace("/\s+/u", ", ", $str);
                $str = empty($str_name)?$str:(empty($str)?$str_name:$str_name.", ".$str);
                if (mb_strlen($str, 'utf-8') <= 200)
                    $str = mb_substr($str, 0, 200, 'utf-8');
                else
                {
                    $space_pos = mb_strpos($str, ' ', 200, 'utf-8');
                    if ($space_pos !== false)
                        $str = mb_substr($str, 0, $space_pos, 'utf-8');
                }
                $material->meta_keywords = $str;
            }

            if ((empty($material->meta_description) && !empty($material->description)) || $recreate_seo)
            {
                $str = preg_replace("/[^a-zA-Zа-яА-Я0-9\s]/u", " ", strip_tags(html_entity_decode($material->description, ENT_COMPAT, 'utf-8')));
                $str = preg_replace("/\s\s+/u", " ", $str);
                $str = trim($str, 'p ');
                $str_name = "";
                /*if (!empty($material->name))
                    $str_name = strip_tags(html_entity_decode($material->name));*/
                $str = empty($str_name)?$str:(empty($str)?$str_name:$str_name.", ".$str);
                if (mb_strlen($str, 'utf-8') <= 200)
                    $str = mb_substr($str, 0, 200, 'utf-8');
                else
                {
                    $space_pos = mb_strpos($str, ' ', 200, 'utf-8');
                    if ($space_pos !== false)
                        $str = mb_substr($str, 0, $space_pos, 'utf-8');
                }
                $material->meta_description = $str;
            }

            if ((empty($material->meta_title) && !empty($material->name)) || $recreate_seo)
                $material->meta_title = $material->name;

            if ($recreate_seo)
                $material->url = '';

            if (empty($material->name))
            {
                $this->design->assign('message_error', 'empty_name');

                if (empty($material->id))
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
            }
            else
            {
                if(empty($material->id))
                {
                    $material->id = $this->materials->add_material($material);
                    $this->design->assign('message_success', 'added');

                    $temp_id = $this->request->post('temp_id');
                    if ($temp_id)
                    {
                        $images = $this->image_temp->get_images($temp_id);
                        if (!empty($images)){
                            foreach($images as $i){
                                $fname = $this->config->root_dir . '/' . $this->config->original_tempimages_dir . $i->filename;
                                $this->image->add_internet_image('materials', $material->id, $this->furl->generate_url($material->name), $fname);
                                $this->image_temp->delete_image($i->temp_id, $i->id);
                            }
                        }
                    }
                }
                else
                {
                    $this->materials->update_material($material->id, $material);
                    $this->design->assign('message_success', 'updated');
                }

                if (!empty($attachments_position))
                {
                    $ip_arr = explode(',', $attachments_position);
                    foreach($ip_arr as $pos=>$id)
                        $this->attachments->update_attachment($id, array('position'=>$pos));
                }

                $material = $this->materials->get_material($material->id);
                $return_page = $this->request->post('return_page');

                if ($close_after_save && $main_module)
                    //header("Location: ".$this->config->root_url.$main_module->url.($material->parent_id>0?'category_id='.$material->parent_id:''));
                    header("Location: ".$this->config->root_url.$main_module->url.$this->design->url_modifier(array('add'=>array('category_id'=>$material->parent_id, 'page'=>$return_page))));

                if ($add_after_save)
                    //header("Location: ".$this->config->root_url.$edit_module->url.($material->parent_id>0?'category_id/'.$material->parent_id:''));
                    header("Location: ".$this->config->root_url.$edit_module->url.$this->design->url_modifier(array('add'=>array('category_id'=>$material->parent_id, 'page'=>$return_page))));
            }
        }
        else
            if ($this->request->method('post') && isset($_FILES['uploaded-images']))
            {
                $uploaded = $this->request->files('uploaded-images');
                $object_id = $this->request->post('object_id');

                if (is_numeric($object_id))
                {
                    $tmp_object = $this->materials->get_material($object_id);
                    foreach($uploaded as $index=>$ufile)
                        $img = $this->image->add_image('materials', $object_id, $this->furl->generate_url($tmp_object->name), $ufile['name'], $ufile['tmp_name']);
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
            elseif ($this->request->method('post') && isset($_FILES['gallery-uploaded-images']))
            {
                $uploaded = $this->request->files('gallery-uploaded-images');
                $object_id = $this->request->post('object_id');

                if (is_numeric($object_id))
                {
                    $tmp_object = $this->materials->get_material($object_id);
                    foreach($uploaded as $index=>$ufile)
                        $img = $this->image->add_image('materials-gallery', $object_id, $this->furl->generate_url($tmp_object->name), $ufile['name'], $ufile['tmp_name']);
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
            elseif ($this->request->method('post') && isset($_FILES['uploaded-attachments']))
            {
                $uploaded = $this->request->files('uploaded-attachments');
                $object_id = $this->request->post('object_id');

                if (is_numeric($object_id))
                {
                    $tmp_object = $this->materials->get_material($object_id);
                    foreach($uploaded as $index=>$ufile){
                        $img = $this->attachments->add_attachment('materials', $object_id, $this->furl->generate_url(pathinfo($ufile['name'], PATHINFO_FILENAME)), $ufile['name'], $ufile['tmp_name']);
                    }
                }
                /*else
                    foreach($uploaded as $index=>$ufile)
                        $img = $this->image_temp->add_image($object_id, $ufile['name'], $ufile['tmp_name']);*/

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
                        case "category_id":
                            $this->design->assign('materials_category_id', intval($v));
                            unset($this->params_arr[$p]);
                            $json_answer = false;
                            break;
                        case "page":
                            $this->design->assign('page', intval($v));
                            unset($this->params_arr[$p]);
                            break;
                    }
                }

                if (!empty($id))
                    $material = $this->materials->get_material($id);
                else
                {
                    $temp_id = uniqid();
                    $temp_id_gallery = uniqid();
                    $this->design->assign('temp_id', $temp_id);
                    $this->design->assign('temp_id_gallery', $temp_id_gallery);

                    $images = $this->image_temp->get_images($temp_id);
                    if (!empty($images)){
                        foreach($images as $i){
                            $fname = $this->config->root_dir . '/' . $this->config->original_tempimages_dir . $i->filename;
                            $this->image_temp->delete_image($i->temp_id, $i->id);
                        }
                    }

                    $images_gallery = $this->image_temp->get_images($temp_id_gallery);
                    if (!empty($images_gallery)){
                        foreach($images_gallery as $i){
                            $fname = $this->config->root_dir . '/' . $this->config->original_tempimages_dir . $i->filename;
                            $this->image_temp->delete_image($i->temp_id, $i->id);
                        }
                    }
                }

                if (!empty($mode) && ((isset($material) && !empty($material)) || !is_numeric($id)))
                    switch($mode){
                        case "delete":
                            $this->materials->delete_material($id);
                            $response['success'] = true;
                            break;
                        case "toggle":
                            $this->materials->update_material($id, array('is_visible'=>1-$material->is_visible));
                            $response['success'] = true;
                            break;
                        case "get_images":
                            $this->design->assign('object', $material);
                            $images_object_name = $this->params_arr['object'];

                            if (is_numeric($id))
                                $images = $this->image->get_images(/*'materials'*/$images_object_name, $id);
                            else
                            {
                                $images = $this->image_temp->get_images($id);
                                $this->design->assign('temp_id', $id);
                            }

                            if ($images_object_name == 'materials')
                                $this->design->assign('images', $images);
                            if ($images_object_name == 'materials-gallery')
                                $this->design->assign('images_gallery', $images);
                            $this->design->assign('images_object_name', /*'materials'*/$images_object_name);
                            $response['success'] = true;
                            if ($images_object_name == 'materials')
                                $response['data'] = $this->design->fetch($this->design->getTemplateDir('admin').'object-images.tpl');
                            if ($images_object_name == 'materials-gallery')
                                $response['data'] = $this->design->fetch($this->design->getTemplateDir('admin').'object-images-gallery.tpl');
                            break;
                        case "delete_image":
                            $image_id = intval($this->params_arr['image_id']);

                            if (is_numeric($id)){
                                $images_object_name = $this->params_arr['object'];
                                $this->image->delete_image(/*'materials'*/$images_object_name, $id, $image_id);
                            }
                            else
                                $this->image_temp->delete_image($id, $image_id);

                            $response['success'] = true;
                            break;
                        case "upload_internet_image":
                            $image_url = base64_decode($this->params_arr['image_url']);

                            if (is_numeric($id)){
                                $images_object_name = $this->params_arr['object'];
                                $this->image->add_internet_image(/*'materials'*/$images_object_name, $id, $this->furl->generate_url($material->name), $image_url);
                            }
                            else
                                $this->image_temp->add_internet_image($id, $image_url);

                            $response['success'] = true;
                            break;
                        case "get_attachments":
                            $this->design->assign('object', $material);

                            if (is_numeric($id))
                                $attachments = $this->attachments->get_attachments('materials', $id);
                            /*else
                            {
                                $attachments = $this->image_temp->get_images($id);
                                $this->design->assign('temp_id', $id);
                            }*/

                            $this->design->assign('attachments', $attachments);
                            $this->design->assign('attachments_object_name', 'materials');
                            $response['success'] = true;
                            $response['data'] = $this->design->fetch($this->design->getTemplateDir('admin').'object-attachments.tpl');
                            break;
                        case "delete_attachment":
                            $attachment_id = intval($this->params_arr['attachment_id']);

                            if (is_numeric($id))
                                $this->attachments->delete_attachment('materials', $id, $attachment_id);
                            /*else
                                $this->image_temp->delete_image($id, $image_id);*/

                            $response['success'] = true;
                            break;
                        case "upload_internet_attachment":
                            $attachment_url = base64_decode($this->params_arr['attachment_url']);

                            if (is_numeric($id))
                                $this->attachments->add_internet_attachment('materials', $id, $this->furl->generate_url($material->name), $attachment_url);
                            /*else
                                $this->image_temp->add_internet_image($id, $image_url);*/

                            $response['success'] = true;
                            break;
                        case "update_attachment_name":
                            $attachment_id = intval($this->params_arr['attachment_id']);
                            $attachment_name = strval($this->params_arr['attachment_name']);

                            if (is_numeric($id))
                                $this->attachments->update_attachment($attachment_id, array('name' => $attachment_name));

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

        if (isset($material))
        {
            $this->design->assign('material', $material);
            $images = $this->image->get_images('materials', $material->id);
            $this->design->assign('images', $images);
            $images_gallery = $this->image->get_images('materials-gallery', $material->id);
            $this->design->assign('images_gallery', $images_gallery);
            // Аттачи
            $attachments = $this->attachments->get_attachments('materials', $material->id);
            $this->design->assign('attachments', $attachments);
        }

        $this->design->assign('current_params', $this->params_arr);

        $categories = $this->materials->get_categories_tree();
        $this->design->assign('categories', $categories);

        return $this->design->fetch($this->design->getTemplateDir('admin').'material.tpl');
    }
}