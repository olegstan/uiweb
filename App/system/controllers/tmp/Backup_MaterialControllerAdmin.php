<?php
namespace app\controllers;

use core\Controller;

class MaterialControllerAdmin extends Controller
{
    private $param_url, $options;

    public function set_params($url = null, $options = null)
    {
        $this->param_url = $url;
        $this->options = $options;
    }

    function fetch()
    {
        if (!(isset($_SESSION['admin']) && $_SESSION['admin']=='admin'))
            header("Location: http://".$_SERVER['SERVER_NAME']."/admin/login/");

        $main_module =  $this->furl->get_module_by_name('MaterialsControllerAdmin');
        $edit_module = $this->furl->get_module_by_name('MaterialControllerAdmin');
        $this->design->assign('main_module', $main_module);

        if ($this->request->method('post') && !isset($_FILES['uploaded-images']))
        {
            $material = new stdClass();
            $material->id = $this->request->post('id', 'integer');
            $material->parent_id = $this->request->post('parent_id', 'integer');
            $material->name = $this->request->post('name', 'string');
            $material->title = $this->request->post('title', 'string');
            $material->url = $this->request->post('url');
            $material->is_visible = $this->request->post('is_visible', 'boolean');

            $material_date = $this->request->post('date_date');
            $datetime = DateTime::createFromFormat('d.m.Y', $material_date);
            $material->date = $datetime->format('Y-m-d');

            $material->meta_title = $this->request->post('meta_title');
            $material->meta_keywords = $this->request->post('meta_keywords');
            $material->meta_description = $this->request->post('meta_description');

            $material->description = $this->request->post('description');
            $material->css_class = $this->request->post('css_class');
            $material->script_text = $this->request->post('script_text');

            $close_after_save = $this->request->post('close_after_save', 'integer');
            $add_after_save = $this->request->post('add_after_save', 'integer');

            if (empty($material->meta_keywords) && !empty($material->description))
            {
                $str_name = "";
                if (!empty($material->name))
                    $str_name = mb_strtolower(strip_tags(html_entity_decode($material->name)), 'utf-8');
                $str = mb_strtolower(strip_tags(html_entity_decode($material->description)), 'utf-8');
                $str = preg_replace("/[^a-zа-я0-9\s]/u", " ", $str);
                $str = preg_replace("/\s+/u", "  ", $str);                //заменим пробелы на двойные пробелы, чтоб следующая регулярка работала, иначе между словами будет общий пробел и условие не пройдет
                $str = preg_replace("/(\s[^\s]{1,3}\s)+/u", " ", $str);    //remove words with length<=3
                $str = preg_replace("/(\s\s)+/u", " ", $str);            //remove double spaces
                $str = trim($str, 'p ');
                $str = preg_replace("/\s+/u", ", ", $str);
                $str = empty($str_name)?$str:(empty($str)?$str_name:$str_name.", ".$str);
                $str = mb_substr($str, 0, 200, 'utf-8');
                $material->meta_keywords = $str;
            }

            if (empty($material->meta_description) && !empty($material->description))
            {
                $str = preg_replace("/[^a-zA-Zа-яА-Я0-9\s]/u", " ", strip_tags(html_entity_decode($material->description)));
                $str = preg_replace("/\s\s+/u", " ", $str);
                $str = trim($str, 'p ');
                $str_name = "";
                if (!empty($material->name))
                    $str_name = strip_tags(html_entity_decode($material->name));
                $str = empty($str_name)?$str:(empty($str)?$str_name:$str_name.", ".$str);
                $str = mb_substr($str, 0, 200, 'utf-8');
                $material->meta_description = $str;
            }

            if (empty($material->meta_title) && !empty($material->name))
                $material->meta_title = $material->name;

            if (empty($material->name))
            {
                $this->design->assign('message_error', 'empty_name');
            }
            else
            {
                if(empty($material->id))
                {
                    $material->id = $this->materials->add_material($material);
                    $this->design->assign('message_success', 'added');
                }
                else
                {
                    $this->materials->update_material($material->id, $material);
                    $this->design->assign('message_success', 'updated');
                }

                $material = $this->materials->get_material($material->id);

                if ($close_after_save && $main_module)
                    header("Location: ".$this->config->root_url.$main_module->url.($material->parent_id>0?'category_id='.$material->parent_id:''));

                if ($add_after_save)
                        header("Location: ".$this->config->root_url.$edit_module->url.($material->parent_id>0?'category_id/'.$material->parent_id:''));
            }
        }
        else
            if ($this->request->method('post') && isset($_FILES['uploaded-images']))
            {
                $uploaded = $this->request->files('uploaded-images');
                $object_id = $this->request->post('object_id');

                foreach($uploaded as $index=>$ufile)
                    $img = $this->image->add_image('materials', $object_id, $ufile['name'], $ufile['tmp_name']);

                header("Content-type: application/json; charset=UTF-8");
                header("Cache-Control: must-revalidate");
                header("Pragma: no-cache");
                header("Expires: -1");
                print json_encode(1);
                die();
            }
            else
                if (!empty($this->param_url))
                {
                    $str = trim($this->param_url, '/');
                    $params = explode('/', $str);

                    switch (count($params))
                    {
                        case 1:
                            if (is_numeric($params[0]))
                                $material = $this->materials->get_material(intval($params[0]));
                            break;
                        case 2:
                            $id = $params[1];
                            if (is_numeric($id))
                                $id = intval($id);
                            $response['success'] = false;
                            $json_answer = true;
                            switch($params[0]){
                                case "delete":
                                    $tmp_item = $this->materials->get_material($id);
                                    if ($tmp_item){
                                        $this->materials->delete_material($id);
                                        $response['success'] = true;
                                    }
                                    break;
                                case "toggle":
                                    $tmp_item = $this->materials->get_material($id);
                                    if ($tmp_item){
                                        $this->materials->update_material($id, array('is_visible'=>1-$tmp_item->is_visible));
                                        $response['success'] = true;
                                    }
                                    break;
                                case "get_images":
                                    $material = $this->materials->get_material(intval($id));
                                    $this->design->assign('object', $material);
                                    $images = $this->image->get_images('materials', $id);
                                    $this->design->assign('images', $images);
                                    $this->design->assign('images_object_name', 'materials');
                                    $response['success'] = true;
                                    $response['data'] = $this->design->fetch($this->design->getTemplateDir('admin').'object-images.tpl');
                                    break;
                                case "category_id":
                                    $this->design->assign('category_id', $id);
                                    $json_answer = false;
                                    break;
                            }

                            if ($json_answer)
                            {
                                header("Content-type: application/json; charset=UTF-8");
                                header("Cache-Control: must-revalidate");
                                header("Pragma: no-cache");
                                header("Expires: -1");
                                if ($params[0] == "get_tags")
                                    print json_encode($response['data']);
                                else
                                    print json_encode($response);
                                die();
                            }
                            break;
                        case 3:
                            if (is_numeric($params[1])){
                                $id = intval($params[1]);
                                $tmp_item = $this->materials->get_material($id);
                                $response['success'] = false;
                                if ($tmp_item)
                                    switch($params[0]){
                                        case "delete_image":
                                            $image_id = intval($params[2]);
                                            $this->image->delete_image('materials', $id, $image_id);
                                            $response['success'] = true;
                                            break;
                                        case "upload_internet_image":
                                            $image_url = base64_decode($params[2]);
                                            $this->image->add_internet_image('materials', $id, $image_url);
                                            $response['success'] = true;
                                            break;
                                    }

                                header("Content-type: application/json; charset=UTF-8");
                                header("Cache-Control: must-revalidate");
                                header("Pragma: no-cache");
                                header("Expires: -1");
                                print json_encode($response);
                                die();
                            }
                            else
                                switch($params[1]){
                                    case "page":
                                        $material = $this->materials->get_material(intval($params[0]));
                                        $this->design->assign('page', intval($params[2]));
                                        break;
                                }
                            break;
                    }
                }
        if (isset($material))
            $this->design->assign('material', $material);

        $categories = $this->materials->get_categories_tree();
        $this->design->assign('categories', $categories);

        return $this->design->fetch($this->design->getTemplateDir('admin').'material.tpl');
    }
}