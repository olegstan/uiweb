<?php
namespace app\controllers;

use core\Controller;

class BannerControllerAdmin extends Controller
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

        $edit_module = $this->furl->get_module_by_name('BannerControllerAdmin');
        $main_module =  $this->furl->get_module_by_name('BannersControllerAdmin');
        $this->design->assign('main_module', $main_module);

        if ($this->request->method('post'))
        {
            $banner = new stdClass();
            $banner->id = $this->request->post('id', 'integer');
            $banner->name = $this->request->post('name');
            $banner->is_visible = $this->request->post('is_visible', 'boolean');
            $banner->show_editor = $this->request->post('show_editor', 'boolean');
            $banner->text = $this->request->post('text');
            $banner->css_class = $this->request->post('css_class');

            $banner->is_link = $this->request->post('is_link', 'boolean');
            $banner->link = $this->request->post('link');
            $banner->link_in_new_window = $this->request->post('link_in_new_window', 'boolean');

            $close_after_save = $this->request->post('close_after_save', 'integer');
            $add_after_save = $this->request->post('add_after_save', 'integer');

            if (empty($banner->name))
            {
                $this->design->assign('message_error', 'empty_name');
            }
            else
            {
                if(empty($banner->id))
                {
                    $banner->id = $this->banners->add_banner($banner);
                    $this->design->assign('message_success', 'added');
                }
                else
                {
                    $this->banners->update_banner($banner->id, $banner);
                    $this->design->assign('message_success', 'updated');
                }

                $banner = $this->banners->get_banner(intval($banner->id));

                $return_page = $this->request->post('return_page');

                if ($close_after_save && $main_module)
                    header("Location: ".$this->config->root_url.$main_module->url.($return_page>1?'?page='.$return_page:''));

                if ($add_after_save)
                    header("Location: ".$this->config->root_url.$edit_module->url.($return_page>1?'?page='.$return_page:''));
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
                    case "page":
                        $this->design->assign('page', intval($v));
                        unset($this->params_arr[$p]);
                        break;
                }
            }

            if (!empty($id))
                $banner = $this->banners->get_banner($id);

            if (!empty($mode) && $banner)
                switch($mode){
                    case "delete":
                        $this->banners->delete_banner($id);
                        $response['success'] = true;
                        break;
                    case "toggle":
                        $this->banners->update_banner($id, array('is_visible'=>1-$banner->is_visible));
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
        if (isset($banner))
            $this->design->assign('banner', $banner);

        return $this->design->fetch($this->design->getTemplateDir('admin').'banner.tpl');
    }
}