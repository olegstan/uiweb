<?php
namespace core;

use core\Core;
use app\models\Banner;
use app\models\image\Image;
use app\models\material\Material;
use core\helper\Url;
use \Smarty;

class Design
{
    public $smarty;

    public function __construct()
    {

        // Создаем и настраиваем Smarty
        $this->smarty = new Smarty();

        /**
         * создаём директории compiled и cache для выбранного шаблона
         */

        if($this->getCore()->request->is_admin){
            $this->smarty->setCompileDir(ABS . '/tmp/compiled/system');
            $this->smarty->setCacheDir(ABS . '/tmp/cache/system');
            $this->smarty->setTemplateDir(MINICART . '/templates/system/html');
        }else{
            $template = $this->getCore()->config['template'];

            $path_compiled = ABS . '/tmp/compiled/' . $template;
            $path_cache = ABS . '/tmp/cache/' . $template;

            if(is_writable($path_compiled) && !file_exists($path_compiled)){
                mkdir($path_compiled , 0777);
            }

            if(is_writable($path_cache) && !file_exists($path_cache)){
                mkdir($path_cache, 0777);
            }

            $path_template = ABS . '/templates/' . $template . '/html/';

            $this->smarty->setCompileDir($path_compiled);
            $this->smarty->setCacheDir($path_cache);
            $this->smarty->setTemplateDir($path_template);
        }

//        $this->smarty->compile_check = $this->config->smarty_compile_check;
//        $this->smarty->caching = $this->config->smarty_caching;
//        $this->smarty->cache_lifetime = $this->config->smarty_cache_lifetime;
//        $this->smarty->debugging = $this->config->smarty_debugging;
//        $this->smarty->error_reporting = E_ALL & ~E_NOTICE;

//        $this->smarty->compile_check = true;
//        $this->smarty->caching = true;
//        $this->smarty->cache_lifetime = true;
//        $this->smarty->debugging = false;

        /*$this->smarty->compile_check = true;
        $this->smarty->caching = true;
        $this->smarty->cache_lifetime = true;*/
        $this->smarty->debugging = false;
//        $this->smarty->error_reporting = E_ALL & ~E_NOTICE;

        // Берем тему из настроек
//        $theme = $this->settings->theme;

        //Определим какая ветка дизайна нужна
        /*$url = $_SERVER['REQUEST_URI'];
        $module = $this->furl->get_module($url);
        if ($module !== false)
            $this->branch = $module->branch;

        if ($this->branch == "admin")
            $this->smarty->compile_dir = $this->config->root_dir.'system/compiled/';
        else
            $this->smarty->compile_dir = $this->config->root_dir.'compiled/'.$theme;
        //$this->smarty->template_dir = $this->config->root_dir.'/templates/'.$theme.'/html';

        $this->smarty->addTemplateDir(array('admin' => $this->config->root_dir.'system/templates/html',
                                            'frontend' => $this->config->root_dir.'templates/'.$theme.'/html'));*/

        /*$this->smarty->addTemplateDir(array('admin' => $this->config->root_dir.'templates/admin/html',
                                    'frontend' => $this->config->root_dir.'templates/frontend/'.$theme.'/html'));*/

        //$this->smarty->assign('path_admin_template', $this->config->root_url.'/system/templates/');
        //$this->smarty->assign('path_frontend_template', $this->config->root_url.'/templates/'.$theme);

        // Создаем папку для скомпилированных шаблонов текущей темы
        //if(!is_dir($this->smarty->compile_dir))
            //mkdir($this->smarty->compile_dir, 0777);

        //$this->smarty->cache_dir = 'cache';


        $this->smarty->registerPlugin('modifier', 'resize', [$this, 'resize_modifier']);
        $this->smarty->registerPlugin('modifier', 'annotation', [$this, 'annotation_modifier']);
        $this->smarty->registerPlugin('function', 'menu', [$this, 'menu_function']);
        $this->smarty->registerPlugin('function', 'menuname', [$this, 'menuname_function']);
        $this->smarty->registerPlugin('modifier', 'cut', [$this, 'cut_modifier']);
        $this->smarty->registerPlugin('modifier', 'attachment', [$this, 'attachment_modifier']);
        $this->smarty->registerPlugin('function', 'url', [$this, 'url_modifier']);
        $this->smarty->registerPlugin('function', 'route', [$this, 'route_modifier']);
        $this->smarty->registerPlugin('modifier', 'preannotation', [$this, 'preannotation_modifier']);
        $this->smarty->registerPlugin('modifier', 'escape_string', [$this, 'escape_string_modifier']);
        $this->smarty->registerPlugin('modifier', 'tag', [$this, 'tag_modifier']);
        $this->smarty->registerPlugin('modifier', 'convert', [$this, 'convert']);



        /*$this->smarty->registerPlugin('modifier', 'resize_temp', array($this, 'resize_temp_modifier'));
        $this->smarty->registerPlugin('modifier', 'token', array($this, 'token_modifier'));
        $this->smarty->registerPlugin('modifier', 'plural', array($this, 'plural_modifier'));
        $this->smarty->registerPlugin('modifier', 'first', array($this, 'first_modifier'));
        $this->smarty->registerPlugin('modifier', 'date', array($this, 'date_modifier'));
        $this->smarty->registerPlugin('modifier', 'time', array($this, 'time_modifier'));
        $this->smarty->registerPlugin('modifier', 'phone_mask', array($this, 'phone_mask_modifier'));
        $this->smarty->registerPlugin('function', 'breadcrumbs', array($this, 'breadcrumbs_function'));
        $this->smarty->registerPlugin('function', 'makeurl', array($this, 'makeurl_function'));
        $this->smarty->registerPlugin('function', 'material', array($this, 'material_function'));
        $this->smarty->registerPlugin('function', 'banner', array($this, 'banner_function'));
        $this->smarty->registerPlugin('function', 'bannercss', array($this, 'bannercss_function'));
        $this->smarty->registerPlugin('function', 'bannerlink', array($this, 'bannerlink_function'));
        $this->smarty->registerPlugin('function', 'bannerlinkwindow', array($this, 'bannerlinkwindow_function'));
        $this->smarty->registerPlugin('modifier', 'ucfirst', array($this, 'ucfirst_modifier'));
        */
    }

    public function getCore()
    {
        return Core::getCore();
    }

    /**
     * @param $object
     * @param int $width
     * @param int $height
     * @return string
     *
     *  Image
     */

    public function resize_modifier(Image $object, $width = 0, $height = 0)
    {
        $object->addResizeParams($width, $height);

        /*if(substr($resized_filename_encoded, 0, 7) == 'http://')
            $resized_filename_encoded = rawurlencode($resized_filename_encoded);*/

        $object->resized_filename_encoded = rawurlencode($object->resized_filename);

        if($object->is_default){
            return '/files/images/resized/default/' . $object->resized_filename_encoded;
        }else{
            $inner_path = $object->object_id[0] . '/' . $object->object_id[1] . '/';
            return '/files/images/resized/' . $object->type . '/' . $inner_path .  $object->resized_filename_encoded;
        }
    }

    public function route_modifier($params)
    {
        if($params['controller'] && $params['action']){
            return $this->getCore()->root . '/' . $params['controller'] . '/' . $params['action'];
        }else{
            return $this->getCore()->root;
        }
    }

    public function url_modifier($params)
    {



        /*$params_arr = array();
        if (!empty($params['current_params']))
            $params_arr = (array)$params['current_params'];

        $new_key = "";
        $new_value = "";

        $add = array();

        if (!empty($params['add']))
            $add = (array)$params['add'];

        $param_str = "";
        if (!empty($params_arr))
            foreach($params_arr as $key=>$value)
            {
                if (empty($key) || empty($value) || array_key_exists($key, $add))
                    continue;
                foreach((array)$value as $v)
                {
                    if (!empty($param_str))
                        $param_str .= "&";
                    $param_str .= "$key=$v";
                }
            }

        if (!empty($add))
            foreach($add as $key=>$value)
            {
                if (empty($key) || empty($value))
                    continue;
                if (!empty($param_str))
                    $param_str .= "&";
                $param_str .= "$key=$value";
            }

        return (empty($param_str)?"":"?").$param_str;*/



    }

    public function assign($var, $value)
    {
        return $this->smarty->assign($var, $value);
    }

    public function fetch($template)
    {
        // Передаем в дизайн то, что может понадобиться в нем
        //$this->smarty->assign('config', $this->config);
        //$this->smarty->assign('settings',    $this->settings);
        return $this->smarty->fetch($template);
    }

    /*public function getTemplateDir($template_name)
    {
        return $this->smarty->getTemplateDir($template_name);
    }

    public function set_templates_dir($dir)
    {
        $this->smarty->template_dir = $dir;
    }

    public function set_compiled_dir($dir)
    {
        $this->smarty->compile_dir = $dir;
    }*/

    public function get_var($name)
    {
        return $this->smarty->getTemplateVars($name);
    }

    public function escape_string_modifier($str)
    {
        return htmlspecialchars(stripslashes($str));
    }

    public function attachment_modifier($filename, $object)
    {
        $inner_path = $filename[0].'/'.$filename[1].'/';
        return $this->config->root_url.'/'.$this->config->attachments_dir.$object.'/'.$inner_path.$filename;
    }



    public function resize_temp_modifier($filename, $width=0, $height=0)
    {
        $resized_filename = $this->image->add_resize_params($filename, $width, $height);
        $resized_filename_encoded = $resized_filename;

        if(substr($resized_filename_encoded, 0, 7) == 'http://')
            $resized_filename_encoded = rawurlencode($resized_filename_encoded);

        $resized_filename_encoded = rawurlencode($resized_filename_encoded);
        $inner_path = $filename[0].'/'.$filename[1].'/';

        return $this->config->root_url.'/'.$this->config->resized_tempimages_dir.$resized_filename_encoded;//.'?'.$this->config->token($resized_filename);
    }

    public function token_modifier($text)
    {
        return $this->config->token($text);
    }



    public function plural_modifier($number, $singular, $plural1, $plural2=null)
    {
        $number = abs($number);
        if(!empty($plural2))
        {
        $p1 = $number%10;
        $p2 = $number%100;
        if($number == 0)
            return $plural1;
        if($p1==1 && !($p2>=11 && $p2<=19))
            return $singular;
        elseif($p1>=2 && $p1<=4 && !($p2>=11 && $p2<=19))
            return $plural2;
        else
            return $plural1;
        }else
        {
            if($number == 1)
                return $singular;
            else
                return $plural1;
        }

    }

    public function first_modifier($params = array())
    {
        if(!is_array($params))
            return false;
        return reset($params);
    }

    public function cut_modifier($array, $num=1)
    {
        if($num>=0)
            return array_slice($array, $num, count($array)-$num, true);
        else
            return array_slice($array, 0, count($array)+$num, true);
    }

    public function date_modifier($date, $format = null)
    {
        //return date(empty($format)?$this->settings->date_format:$format, strtotime($date));
        return date(empty($format)?$this->settings->date_format:$format, $date);
    }

    public function time_modifier($date, $format = null)
    {
        //return date(empty($format)?'H:i':$format, strtotime($date));
        return date(empty($format)?'H:i':$format, $date);
    }

    public function phone_mask_modifier($phone, $mask = null)
    {
        if (!isset($mask) || empty($mask))
            $mask = "999-99-99";
        if (empty($phone))
            return "";
        $return_str = "";
        $phone_index = 0;
        for( $mask_index=0 ; $mask_index<mb_strlen($mask, 'utf-8') ; $mask_index++)
        {
            if ($mask[$mask_index] == '9')
            {
                $return_str .= $phone[$phone_index];
                $phone_index++;
            }
            else
                $return_str .= $mask[$mask_index];
        }
        return $return_str;
    }

    public function preannotation_modifier($text)
    {
        $p = mb_strpos($text, '<hr id="system-readmore" />', 0, 'utf-8');
        if ($p === false)
            return $text;
        return ($p > 0 ? mb_substr($text, 0, $p-1, 'utf-8') : "");
    }

    public function annotation_modifier($text)
    {
        $text = mb_eregi_replace('<hr id="system-readmore" />', '', $text);
        return $text;
    }

    public function tag_modifier($tag, $convert = false)
    {
//        if ($convert) {
//            return (!empty($tag->prefix) ? $tag->prefix . ': ' : '') . $this->currencies->convert($tag->name, null, true) . (!empty($tag->postfix) ? ' ' . $tag->postfix : '');
//        }else {
            return (!empty($tag->prefix) ? $tag->prefix . ': ' : '') . $tag->name . (!empty($tag->postfix) ? ' ' . $tag->postfix : '');
//        }
    }

    public function convert($price, $currency_id = null, $format = true)
    {
        return $price;
//        if(isset($currency_id))
//            $currency = $this->get_currency((integer)$currency_id);
//        /*elseif(isset($_SESSION['currency_id']))
//            $currency = $this->get_currency($_SESSION['currency_id']);*/
//        else
//        {
//            $this->db->query("SELECT * FROM __currencies WHERE is_enabled=1 AND use_main=1");
//            $currency = $this->db->result();
//            //$currency = reset($this->get_currencies(array('is_enabled'=>1, 'use_main'=>1)));
//        }
//
//        $this->db->query("SELECT * FROM __currencies WHERE is_enabled = 1 AND use_main = 1");
//        $main_currency = $this->db->result();
//
//        $result = $price;
//
//        if(!empty($currency))
//        {
//            // Умножим на курс валюты
//            $result = $result*$currency->rate_to/$currency->rate_from;
//
//            // Точность отображения, знаков после запятой
//            $precision = isset($main_currency->cents)?$main_currency->cents:2;
//        }
//
//        // Форматирование цены
//        if($format)
//            $result = number_format($result, $precision, $this->settings->decimals_point, $this->settings->thousands_separator);
//        else
//            $result = round($result, $precision);
//
//        return $result;
    }

    public function breadcrumbs_function($params)
    {
        $return_str = "";
        $module = "";
        $id = 0;
        $type = "";
        $show_self_element = true;
        if (!empty($params['module']))
            $module = $params['module'];
        if (!empty($params['type']))
            $type = $params['type'];
        if (!empty($params['id']))
            $id = intval($params['id']);
        if (isset($params['show_self_element']))
            $show_self_element = $params['show_self_element'];
        if (method_exists($this->$module, 'get_breadcrumbs') && $id)
            $return_str = $this->$module->get_breadcrumbs($id, $type, $show_self_element);
        return $return_str;
    }

    private function process_horizontal_submenu($subitems, &$return_str)
    {
        $return_str .= '<ul class="dropdown-menu '.$item->css_class.'">';
        foreach($subitems as $item)
        {
            if (!$item->visible)
                continue;
            if ($item->is_main)
                $item_url = $this->config->root_url;
            else
                $item_url = $this->makeurl_function(array('module'=>'materials', 'item'=>$item));
            $selected = (("http://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']) == $item_url || ($item->is_main && ($item_url."/")==("http://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']))) ? "active" : "";
            if (!isset($item->subitems))
                $return_str .= '<li class="menu-item '.$selected.'"><a href="'.$item_url.'" data-type="'.$item->object_type.'">'.$item->name.'</a></li>';
            else
            {
                $return_str .= '<li class="menu-item dropdown dropdown-submenu '.$selected.'"><a href="'.$item_url.'" data-type="'.$item->object_type.'" class="dropdown-toggle" data-toggle="dropdown">'.$item->name.'</a>';
                $this->process_horizontal_submenu($item->subitems, $return_str, $item->css_class);
                $return_str .= '</li>';
            }
        }
        $return_str .= '</ul>';
    }

    private function build_horizontal_menu($id)
    {
        $return_str = '<ul class="horizontal-menu nav navbar-nav">';
        $tree = (new Material())->get_menu_items_tree();
        $new_tree = array();
        foreach($tree as $t)
        {
            if ($t->menu_id != $id)
                continue;
            $new_tree[] = $t;
        }
        foreach($new_tree as $item)
        {
            if (!$item->visible)
                continue;
            if ($item->is_main)
            {
                $item_url = $this->config->root_url;
                $item->object_type = "href";
            }
            else
                $item_url = $this->makeurl_function(array('module'=>'materials', 'item'=>$item));
            $selected = (("http://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']) == $item_url || ($item->is_main && ($item_url."/")==("http://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']))) ? "active" : "";
            if (!isset($item->subitems))
                if ($item->object_type == "separator")
                    $return_str .= '<li class="menu-item '.$selected.'"><a href="" data-type="'.$item->object_type.'">'.$item->name.'</a></li>';
                else
                    $return_str .= '<li class="menu-item '.$selected.'"><a href="'.$item_url.'" data-type="'.$item->object_type.'">'.$item->name.'</a></li>';
            else
            {
                if ($item->object_type == "separator")
                    $return_str .= '<li class="menu-item dropdown '.$selected.'"><a href="" data-type="'.$item->object_type.'" class="dropdown-toggle" data-toggle="dropdown">'.$item->name.' <b class="caret"></b></a>';
                else
                    $return_str .= '<li class="menu-item dropdown '.$selected.'"><a href="'.$item_url.'" data-type="'.$item->object_type.'" class="dropdown-toggle" data-toggle="dropdown">'.$item->name.' <b class="caret"></b></a>';
                $this->process_horizontal_submenu($item->subitems, $return_str, $item->css_class);
                $return_str .= '</li>';
            }
        }
        $return_str .= '</ul>';

        return $return_str;
    }

    private function is_vertical_menu_active($subitems)
    {
        $result = false;
        foreach($subitems as $item)
        {
            if (!$item->visible)
                continue;
            if ($item->is_main)
                $item_url = $this->config->root_url;
            else
                $item_url = $this->makeurl_function(array('module'=>'materials', 'item'=>$item));
            $selected = (("http://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']) == $item_url || ($item->is_main && ($item_url."/")==("http://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']))) ? true : false;
            if (!isset($item->subitems))
            {
                if ($selected)
                    $result = $selected;
            }
            else
                $result = $this->is_vertical_menu_active($item->subitems);
        }
        return $result;
    }

    private function process_vertical_submenu($subitems, &$return_str, $css_class, $opened = false)
    {
        $return_str .= '<ol class="'.$css_class.'" style="display:'.($opened ? 'block' : 'none').';">';
        foreach($subitems as $item)
        {
            if (!$item->visible)
                continue;
            if ($item->is_main)
                $item_url = $this->config->root_url;
            else
                $item_url = $this->makeurl_function(array('module'=>'materials', 'item'=>$item));
            $selected = (("http://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']) == $item_url || ($item->is_main && ($item_url."/")==("http://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']))) ? "active" : "";
            if (!isset($item->subitems))
                $return_str .= '<li class="collapsed '.$selected.' '.$item->css_class.'"><div><a href="'.$item_url.'">'.$item->name.'</a></div></li>';
            else
            {
                $selected_children = $this->is_vertical_menu_active($item->subitems);
                if ($selected_children && empty($selected))
                    $selected = "active";
                $return_str .= '<li class="'.($selected_children || $selected ? '' : 'collapsed').' havechild '.$selected.' '.$item->css_class.'"><i class="fa fa-minus" style="display: '.($selected_children || $selected ? 'block' : 'none').';"></i><i class="fa fa-plus" style="display: '.($selected_children || $selected ? 'none' : 'block').';"></i><div><a href="'.$item_url.'">'.$item->name.'</a></div>';
                $this->process_vertical_submenu($item->subitems, $return_str, $item->css_class, $selected_children || $selected);
                $return_str .= '</li>';
            }
        }
        $return_str .= '</ol>';
    }

    private function build_vertical_menu($id)
    {
        $tree = (new Material())->get_menu_items_tree();
        $new_tree = array();
        $ol_class = '';
        foreach($tree as $t)
        {
            if ($t->menu_id != $id)
                continue;
            $new_tree[] = $t;
            if (isset($t->subitems))
                $ol_class = 'havechilds';
        }
        if (empty($ol_class))
            $return_str = '<div class="vertical-menu"><ol>';
        else
            $return_str = '<div class="vertical-menu"><ol class="'.$ol_class.'">';
        foreach($new_tree as $item)
        {
            if (!$item->visible)
                continue;
            if ($item->is_main)
                $item_url = $this->config->root_url;
            else
                $item_url = $this->makeurl_function(array('module'=>'materials', 'item'=>$item));
            $selected = (("http://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']) == $item_url || ($item->is_main && ($item_url."/")==("http://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']))) ? "active" : "";
            if (!isset($item->subitems))
                $return_str .= '<li class="collapsed '.$selected.' '.$item->css_class.'"><div><a href="'.$item_url.'">'.$item->name.'</a></div></li>';
            else
            {
                $selected_children = $this->is_vertical_menu_active($item->subitems);
                if ($selected_children && empty($selected))
                    $selected = "active";
                $return_str .= '<li class="'.($selected_children || $selected ? '' : 'collapsed').' havechild '.$selected.' '.$item->css_class.'"><i class="fa fa-minus" style="display: '.($selected_children || $selected ? 'block' : 'none').';"></i><i class="fa fa-plus" style="display: '.($selected_children || $selected ? 'none' : 'block').';"></i><div><a href="'.$item_url.'">'.$item->name.'</a></div>';
                $this->process_vertical_submenu($item->subitems, $return_str, $item->css_class, $selected_children || $selected);
                $return_str .= '</li>';
            }
        }
        $return_str .= '</ol></div>';

        return $return_str;
    }

    public function menu_function($params)
    {
        $return_str = "";
        $id = 0;
        if (!empty($params['id']))
            $id = intval($params['id']);
        $menu_type = "horizontal";
        if (!empty($params['menu_type']) && in_array($params['menu_type'], array('horizontal','vertical')))
            $menu_type = $params['menu_type'];

        if ($id > 0)
        {
            $menu = (new Material())->get_menu($id);
            if ($menu_type == "horizontal")
                $return_str = $this->build_horizontal_menu($id);
            if ($menu_type == "vertical")
                $return_str = $this->build_vertical_menu($id);
        }
        return $return_str;
    }

    public function menuname_function($params)
    {
        $return_str = "";
        $id = 0;
        if (!empty($params['id']))
            $id = intval($params['id']);

        if ($id > 0)
        {
            $menu = (new Material())->get_menu($id);
            $return_str = $menu->name;
        }
        return $return_str;
    }

    public function makeurl_function($params)
    {
        $return_str = "";
        $module = "";
        if (!empty($params['module']))
            $module = $params['module'];
        if (!empty($params['item']))
            $item = $params['item'];
        if (method_exists($this->$module, 'makeurl') && isset($item))
            $return_str = $this->$module->makeurl($item);
        return $return_str;
    }

    public function material_function($params)
    {
        $return_str = "";
        $id = 0;
        if (!empty($params['id']))
            $id = intval($params['id']);

        if ($id > 0)
        {
            $material = (new Material())->get_material($id);
            if ($material)
                $return_str = $material->description;
        }
        return $return_str;
    }

    public function material_category_function($params)
    {
        $return_str = "";
        $id = 0;
        $count = 5;
        $header = "";
        $footer = "";
        if (!empty($params['id']))
            $id = intval($params['id']);
        if (!empty($params['count']))
            $count = intval($params['count']);
        if (!empty($params['header']))
            $header = strval($params['header']);
        if (!empty($params['footer']))
            $footer = strval($params['footer']);

        if ($id > 0)
        {
            $material_category = (new Material())->get_category($id);
            if (!$material_category)
                return $return_str;

            if (empty($header))
                if (!empty($material_category->title))
                    $header = $material_category->title;
                else
                    $header = $material_category->name;

            if (empty($footer))
                $footer = $header;

            $filter = array();
            $filter['visible'] = 1;
            $filter['limit'] = $count;
            $filter['category_id'] = $material_category->children;

            switch($material_category->sort_type){
                case 'position':
                    $filter['sort'] = 'position';
                    break;
                case 'newest_desc':
                    $filter['sort'] = 'newest';
                    $filter['sort_type'] = 'desc';
                    break;
            }

            $materials_count = (new Material())->count_materials($filter);

            if ($materials_count == 0)
                return $return_str;

            $items_per_page = $this->settings->materials_num;

            $filter['page'] = 1;
            //$filter['limit'] = $items_per_page;

            $materials = (new Material())->get_materials($filter);
            foreach($materials as $index=>$material)
            {
                $materials[$index]->images = (new Image())->get_images('materials', $material->id);
                $materials[$index]->image = reset($materials[$index]->images);
            }

            $tmp_category = new \stdClass;
            $tmp_category->object_type = 'material-category';
            $tmp_category->object_id = $material_category->id;

            $return_str = '<div id="material-category-'.$material_category->id.'" class="articles">';
            $return_str .= '<div class="head"><a href="'.$this->makeurl_function(array('module'=>'materials', 'item'=>$tmp_category)).'">'.$header.'</a></div>';
            $return_str .= '<ul>';
            foreach($materials as $index=>$material)
            {
                $return_str .= '<li>';
                $return_str .= '<div class="date">'.date($this->settings->date_format, $material->date).'</div>';
                $return_str .= '<a href="'.$this->makeurl_function(array('module'=>'materials', 'item'=>$material)).'" class="news-header">'.(!empty($material->title) ? $material->title : $material->name).'</a>';
                $return_str .= '<div class="news-description">';
                if (!empty($material->image))
                {
                    $return_str .= '<a href="'.$this->makeurl_function(array('module'=>'materials', 'item'=>$material)).'" class="news-header" alt="'.(!empty($material->title) ? $material->title : $material->name).'">';
                    $return_str .= '<img src="'.$this->resize_modifier($material->image->filename, 'materials', 201, 150).'" alt="'.(!empty($material->title) ? $material->title : $material->name).'"/>';
                    $return_str .= '</a>';
                }
                $return_str .= $this->preannotation_modifier($material->description);
                $return_str .= '<p><a href="'.$this->makeurl_function(array('module'=>'materials', 'item'=>$material)).'" class="readmore">Подробнее</a></p>';
                $return_str .= '</div>';
                $return_str .= '</li>';
            }
            $return_str .= '<a href="'.$this->makeurl_function(array('module'=>'materials', 'item'=>$tmp_category)).'" class="allnews">'.$footer.' ('.$materials_count.')</a>';
            $return_str .= '</ul></div>';
        }
        return $return_str;
    }


    public function ucfirst_modifier($text)
    {
        return ucfirst($text);
    }
}