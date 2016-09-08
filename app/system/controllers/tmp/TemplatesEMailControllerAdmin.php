<?php
namespace app\controllers;

use core\Controller;

class TemplatesEMailControllerAdmin extends Controller
{
    private $param_url, $options;

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

        $ajax = false;
        $action = "";
        $id = 0;

        if ($this->request->method('post'))
        {
            $id = $this->request->post('id');
            $message_header = $this->request->post('message_header');
            $message_text = $this->request->post('message_text');
            $url = $this->request->post('url');
            $is_enabled = $this->request->post('is_enabled', 'boolean');

            if ($id)
            {
                $this->db->query('UPDATE __email_templates SET message_header=?, message_text=?, is_enabled=? WHERE id=?', $message_header, $message_text, $is_enabled, $id);
                $this->db->query("SELECT * FROM __email_templates WHERE id=?", $id);
                $template = $this->db->result();
                if ($template)
                {
                    $this->design->assign('template', $template);
                    $this->design->assign('message_success', 'success');
                }
            }
        }
        else
        {
            if (!array_key_exists('template', $this->params_arr))
                $this->params_arr['template'] = 'registration';
            foreach($this->params_arr as $p=>$v)
            {
                switch ($p)
                {
                    case "template":
                        $this->db->query("SELECT * FROM __email_templates WHERE url=?", strval($this->params_arr[$p]));
                        $template = $this->db->result();
                        if ($template)
                        {
                            $id = $template->id;
                            $this->design->assign('template', $template);
                        }
                        break;
                    case "ajax":
                        $ajax = true;
                        unset($this->params_arr[$p]);
                        break;
                }
            }
        }

        if ($ajax)
        {
            $response = array('success' => false, 'data'=>$template);

            header("Content-type: application/json; charset=UTF-8");
            header("Cache-Control: must-revalidate");
            header("Pragma: no-cache");
            header("Expires: -1");
            print json_encode($response);
            die();
        }

        return $this->design->fetch($this->design->getTemplateDir('admin').'templates-email.tpl');
    }
}