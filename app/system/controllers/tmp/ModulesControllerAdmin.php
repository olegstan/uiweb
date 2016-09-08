<?php
namespace app\controllers;

use core\Controller;

class ModulesControllerAdmin extends Controller
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

    private function process_menu($item, $parent_id, &$menu)
    {
        if (!array_key_exists($parent_id, $menu))
            $menu[$parent_id] = array();
        $menu[$parent_id][] = intval($item['id']);
        if (isset($item['children']))
            foreach($item['children'] as $i)
                $this->process_menu($i, intval($item['id']), $menu);
    }

    function fetch()
    {
        if (!(isset($_SESSION['admin']) && $_SESSION['admin']=='admin'))
            header("Location: http://".$_SERVER['SERVER_NAME']."/admin/login/");

        $category = "";
        $category_filter = "";

        if ($this->request->method('post'))
        {
            $is_enabled = (array)$this->request->post('is_enabled');

            foreach($is_enabled as $id=>$value)
                $this->db->query("UPDATE __external_modules SET is_enabled=? WHERE id=?", $value, $id);

            $this->design->assign('message_success', 'success');
        }
        else
            foreach($this->params_arr as $p=>$v)
            {
                switch ($p)
                {
                    case "category":
                        $category = trim($v);
                        $category_filter = $this->db->placehold('AND em.category=?', $category);
                        break;
                }
            }

        $this->design->assign('category', $category);

        $this->db->query("SELECT em.id, em.controller, em.category, em.path, em.filename, em.is_enabled, em.settings, sf.url
            FROM __external_modules em LEFT JOIN __system_furl sf ON em.controller=sf.module WHERE em.is_visible $category_filter");
        $modules = $this->db->results();
        foreach($modules as &$module)
        {
            $filename_path = $this->config->root_dir . '/modules/' . $module->path . '/module.xml';

            $module_settings = "";
            $handle = fopen($filename_path, "r");
            while (!feof($handle)) {
                $buffer = fgets($handle);
                $module_settings .= $buffer;
            }
            fclose($handle);

            $settings = new SimpleXMLElement($module_settings);
            $module->name = $settings->name;
            $module->description = $settings->description;
            $module->icon = $settings->icon;
            $module->version = $settings->version;
            $module->releaseDate = $settings->releaseDate;
            $module->is_settings_page = $settings->settings == "yes" ? 1 : 0;
            $module->openable = 0;
            foreach($settings->controllers->controller as $controller)
                if ($controller['branch'] == "admin")
                    $module->openable = $controller['openable'] == "yes" ? 1 : 0;
        }
        $this->design->assign('modules', $modules);

        //Подсчитаем количество модулей по категориям
        $modules_count = array(
            'all' => 0,
            'system' => 0,
            'cart' => 0,
            'payment' => 0,
            'delivery' => 0,
            'materials' => 0,
            'other' => 0);
        foreach(array_keys($modules_count) as $key)
        {
            $filter = "";
            if ($key != 'all')
                $filter = $this->db->placehold('AND em.category=?', $key);
            $this->db->query("SELECT count(em.id) as count
                FROM __external_modules em
                    LEFT JOIN __system_furl sf ON em.controller=sf.module
                WHERE em.is_visible $filter");
            $modules_count[$key] = $this->db->result('count');
        }
        $this->design->assign('modules_count', $modules_count);

        return $this->design->fetch($this->design->getTemplateDir('admin').'modules.tpl');
    }
}