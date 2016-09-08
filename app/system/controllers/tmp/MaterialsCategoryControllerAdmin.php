<?php
namespace app\controllers;

use core\Controller;

class MaterialsCategoryControllerAdmin extends Controller
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

        $edit_module = $this->furl->get_module_by_name('MaterialsCategoryControllerAdmin');
        $main_module =  $this->furl->get_module_by_name('MaterialsCategoriesControllerAdmin');
        $this->design->assign('main_module', $main_module);

        if ($this->request->method('post') && !isset($_FILES['uploaded-images']))
        {
            $category = new stdClass();
            $category->id = $this->request->post('id', 'integer');
            $category->parent_id = $this->request->post('parent_id', 'integer');
            $category->url = $this->request->post('url');
            $category->name = $this->request->post('name');
            $category->title = $this->request->post('title');

            $category->meta_title = $this->request->post('meta_title');
            $category->meta_keywords = $this->request->post('meta_keywords');
            $category->meta_description = $this->request->post('meta_description');

            $category->description = $this->request->post('description');

            $category->is_visible = $this->request->post('is_visible', 'boolean');
            $category->css_class = $this->request->post('css_class');

            $category->show_date = $this->request->post('show_date', 'boolean');

            $category->collapsed = $this->request->post('collapsed', 'boolean');
            $category->sort_type = $this->request->post('sort_type');

            $close_after_save = $this->request->post('close_after_save', 'integer');
            $add_after_save = $this->request->post('add_after_save', 'integer');
            $recreate_seo = $this->request->post('recreate_seo', 'integer');

            if ((empty($category->meta_title) && !empty($category->name)) || $recreate_seo)
                $category->meta_title = $category->name;

            if ((empty($category->meta_keywords) && !empty($category->description)) || $recreate_seo)
            {
                $str_name = "";
                if (!empty($category->name))
                    $str_name = mb_strtolower(strip_tags(html_entity_decode($category->name, ENT_COMPAT, 'utf-8')), 'utf-8');
                $str = mb_strtolower($category->description, 'utf-8');
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
                $category->meta_keywords = $str;
            }

            if ((empty($category->meta_description) && !empty($category->description)) || $recreate_seo)
            {
                $str = preg_replace("/[^a-zA-Zа-яА-Я0-9\s]/u", " ", html_entity_decode($category->description, ENT_COMPAT, 'utf-8'));
                $str = preg_replace("/\s\s+/u", " ", $str);
                $str = trim($str, 'p ');
                $str_name = "";
                if (!empty($category->name))
                    $str_name = strip_tags(html_entity_decode($category->name, ENT_COMPAT, 'utf-8'));
                $str = empty($str_name)?$str:(empty($str)?$str_name:$str_name.", ".$str);
                if (mb_strlen($str, 'utf-8') <= 200)
                    $str = mb_substr($str, 0, 200, 'utf-8');
                else
                {
                    $space_pos = mb_strpos($str, ' ', 200, 'utf-8');
                    if ($space_pos !== false)
                        $str = mb_substr($str, 0, $space_pos, 'utf-8');
                }
                $category->meta_description = $str;
            }

            if ($recreate_seo)
                $category->url = '';

            if(empty($category->id))
            {
                $category->id = $this->materials->add_category($category);
                $this->design->assign('message_success', 'added');
            }
            else
            {
                $this->materials->update_category($category->id, $category);
                $this->design->assign('message_success', 'updated');
            }

            $category = $this->materials->get_category(intval($category->id));

            if ($close_after_save && $main_module)
                header("Location: ".$this->config->root_url.$main_module->url);

            if ($add_after_save)
                header("Location: ".$this->config->root_url.$edit_module->url);
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
                $category = $this->materials->get_category($id);

            if (!empty($mode) && $category)
                switch($mode){
                    case "delete":
                        $this->materials->delete_category($id);
                        $response['success'] = true;
                        break;
                    case "toggle":
                        $this->materials->update_category($id, array('is_visible'=>1-$category->is_visible));
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

        if (isset($category))
        {
            $this->design->assign('category', $category);
        }

        $this->design->assign('categories', $this->materials->get_categories_tree());

        return $this->design->fetch($this->design->getTemplateDir('admin').'materials-category.tpl');
    }
}