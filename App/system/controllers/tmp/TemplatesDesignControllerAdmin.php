<?php
namespace app\controllers;

use core\Controller;

class TemplatesDesignControllerAdmin extends Controller
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

        if ($this->request->method('post'))
        {
            $theme = $this->request->post('theme');
            $this->settings->theme = $theme;
        }

        $templates = array();

        $dir    = $this->config->root_dir . 'templates/';
        $files = scandir($dir);
        foreach($files as $f)
        {
            if ($f == "." || $f == ".." || !is_dir($dir.$f))
                continue;
            if (!file_exists($dir . $f . "/template.xml"))
                continue;
            $template = array();

            $xml = simplexml_load_file ($dir . $f . '/template.xml');

            $template['system_name'] = $f;
            $template['name'] = strval($xml->name[0]);
            $template['version'] = strval($xml->version[0]);
            $template['description'] = strval($xml->description[0]);
            $template['image'] = $this->config->root_url . '/templates/' . $f . "/" . strval($xml->image[0]);
            $template['developer'] = strval($xml->developer[0]);

            $templates[$template['system_name']] = $template;
        }

        ksort($templates);

        $this->design->assign('templates', $templates);

        return $this->design->fetch($this->design->getTemplateDir('admin').'templates-design.tpl');
    }
}