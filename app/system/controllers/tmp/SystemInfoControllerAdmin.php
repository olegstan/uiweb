<?php
namespace app\controllers;

use core\Controller;

class SystemInfoControllerAdmin extends Controller
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

    public function getPHPInfo()
    {
        ob_start();
        phpinfo(INFO_GENERAL | INFO_CONFIGURATION | INFO_MODULES);
        $phpInfo = ob_get_contents();
        ob_end_clean();
        preg_match_all('#<body[^>]*>(.*)</body>#siU', $phpInfo, $output);
        $output = preg_replace('#<table[^>]*>#', '<table class="table table-striped adminlist">', $output[1][0]);
        $output = preg_replace('#(\w),(\w)#', '\1, \2', $output);
        $output = preg_replace('#<hr />#', '', $output);
        $output = str_replace('<div class="center">', '', $output);
        $output = preg_replace('#<tr class="h">(.*)<\/tr>#', '<thead><tr class="h">$1</tr></thead><tbody>', $output);
        $output = str_replace('</table>', '</tbody></table>', $output);
        $output = str_replace('</div>', '', $output);
        return $output;
    }

    function fetch()
    {
        if (!(isset($_SESSION['admin']) && $_SESSION['admin']=='admin'))
            header("Location: http://".$_SERVER['SERVER_NAME']."/admin/login/");

        $mode = "";

        if (array_key_exists("mode", $this->params_arr))
            $mode = $this->params_arr["mode"];
        else
            $mode = "system";

        $this->design->assign('mode', $mode);

        if ($mode == "system")
        {
            $this->design->assign('compiled_permissions', substr(sprintf('%o', fileperms($this->config->root_dir . 'compiled')), -3));
            $this->design->assign('system_compiled_permissions', substr(sprintf('%o', fileperms($this->config->root_dir . 'system/compiled')), -3));
            $this->design->assign('files_permissions', substr(sprintf('%o', fileperms($this->config->root_dir . 'files')), -3));

            $this->design->assign('php_version', PHP_VERSION);

            $this->db->query("SELECT VERSION() as mysql_version");
            $version = $this->db->result();
            $this->design->assign('mysql_version', $version->mysql_version);

            $this->db->query("show variables like 'character_set_database'");
            $r = $this->db->result();
            $this->design->assign('database_codepage', $r->Value);
        }
        else
        {
            $this->design->assign('php_info', $this->getPHPInfo());
        }

        return $this->design->fetch($this->design->getTemplateDir('admin').'system-info.tpl');
    }
}