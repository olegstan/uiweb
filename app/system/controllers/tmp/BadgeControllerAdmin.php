<?php
namespace app\controllers;

use core\Controller;

class BadgeControllerAdmin extends Controller
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

        $edit_module = $this->furl->get_module_by_name('BadgeControllerAdmin');
        $main_module =  $this->furl->get_module_by_name('BadgesControllerAdmin');
        $this->design->assign('main_module', $main_module);

        if ($this->request->method('post'))
        {
            $badge = new stdClass();
            $badge->id = $this->request->post('id', 'integer');
            $badge->name = $this->request->post('name');
            $badge->is_visible = $this->request->post('is_visible', 'boolean');
            $badge->css_class = $this->request->post('css_class', 'string');
            $badge->css_class_product = $this->request->post('css_class_product', 'string');

            $close_after_save = $this->request->post('close_after_save', 'integer');
            $add_after_save = $this->request->post('add_after_save', 'integer');

            if (empty($badge->name))
            {
                $this->design->assign('message_error', 'empty_name');
            }
            else
            {
                if(empty($badge->id))
                {
                    $badge->id = $this->badges->add_badge($badge);
                    $this->design->assign('message_success', 'added');
                }
                else
                {
                    $this->badges->update_badge($badge->id, $badge);
                    $this->design->assign('message_success', 'updated');
                }

                $badge = $this->badges->get_badge($badge->id);
                $return_page = $this->request->post('return_page');

                if ($close_after_save && $main_module)
                    header("Location: ".$this->config->root_url.$main_module->url);

                if ($add_after_save)
                    header("Location: ".$this->config->root_url.$edit_module->url);
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
                $badge = $this->badges->get_badge($id);

            if (!empty($mode) && !empty($badge))
                switch($mode){
                    case "delete":
                        $this->badges->delete_badge($id);
                        $response['success'] = true;
                        break;
                    case "toggle":
                        $this->badges->update_badge($id, array('is_visible'=>1-$badge->is_visible));
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

        if (isset($badge))
            $this->design->assign('badge', $badge);

        $this->design->assign('current_params', $this->params_arr);
        return $this->design->fetch($this->design->getTemplateDir('admin').'badge.tpl');
    }
}