<?php
namespace app\controllers;

use core\Controller;

class StatisticsControllerAdmin extends Controller
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

        $this->db->query("SELECT id,keyword,amount,unix_timestamp(last_updated) as last_updated,products_count,categories_count FROM __search_history ORDER BY amount desc");
        $histories = $this->db->results();
        $this->design->assign('histories', $histories);

        if ($this->request->method('post'))
        {
            $mode = $this->request->post('mode');

            switch($mode){
                case "main":

                    break;
                case "catalog":

                    break;
            }

            $this->design->assign('mode', $mode);
            $this->design->assign('message_success', 'saved');
        }
        else
            if (!empty($this->param_url))
            {
                $str = trim($this->param_url, '/');
                $params = explode('/', $str);

                switch (count($params))
                {
                    case 2:
                        if ($params[0] == "get")
                        {
                            $template = $params[1];
                            $response = array('success'=>true, 'data'=>$this->design->fetch($this->design->getTemplateDir('admin').'statistics/'.$params[1].'.tpl'));
                            header("Content-type: application/json; charset=UTF-8");
                            header("Cache-Control: must-revalidate");
                            header("Pragma: no-cache");
                            header("Expires: -1");
                            print json_encode($response);
                            die();
                        }
                        if ($params[0] == "mode")
                        {
                            $this->design->assign('mode', $params[1]);
                        }
                        break;
                }
            }

        if($this->page)
        {
            $this->design->assign('meta_title', $this->page->meta_title);
            $this->design->assign('meta_keywords', $this->page->meta_keywords);
            $this->design->assign('meta_description', $this->page->meta_description);
        }

        return $this->design->fetch($this->design->getTemplateDir('admin').'statistics.tpl');
    }
}