<?php
namespace app\controllers;

use core\Controller;

class OrdersStatusControllerAdmin extends Controller
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

        $edit_module = $this->furl->get_module_by_name('OrdersStatusControllerAdmin');
        $main_module =  $this->furl->get_module_by_name('OrdersStatusesControllerAdmin');
        $this->design->assign('main_module', $main_module);

        if ($this->request->method('post'))
        {
            $status = new stdClass();
            $status->id = $this->request->post('id', 'integer');
            $status->name = $this->request->post('name', 'string');
            $status->group_name = $this->request->post('group_name', 'string');
            $status->is_enabled = $this->request->post('is_enabled', 'boolean');
            $status->status_type = $this->request->post('status_type', 'string');
            $status->css_class = $this->request->post('css_class', 'string');

            $close_after_save = $this->request->post('close_after_save', 'integer');
            $add_after_save = $this->request->post('add_after_save', 'integer');

            if(empty($status->id))
            {
                $status->id = $this->orders->add_status($status);
                $this->design->assign('message_success', 'added');
            }
            else
            {
                $this->orders->update_status($status->id, $status);
                $this->design->assign('message_success', 'updated');
            }

            $status= $this->orders->get_status(intval($status->id));

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
                $status = $this->orders->get_status($id);

            if (!empty($mode) && $status)
                switch($mode){
                    case "delete":
                        $response['success'] = $this->orders->delete_status($id);
                        break;
                    case "toggle":
                        $this->orders->update_status($id, array('is_enabled'=>1-$status->is_enabled));
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
        if (isset($status))
            $this->design->assign('status', $status);

        return $this->design->fetch($this->design->getTemplateDir('admin').'orders-status.tpl');
    }
}