<?php
namespace app\controllers;

use app\layer\LayerController;

class GlobalController extends LayerController
{
    /* Смысл класса в доступности следующих переменных в любом View */
    public $main_currency;
    public $admin_currency;
    public $all_currencies;
    public $user;
    public $group;
    public $page;
    public $global_group_permissions;

    /* Класс View похож на синглтон, храним статически его инстанс */
    private static $controller_instance;

    public function __construct()
    {
        parent::__construct();

        // Если инстанс класса уже существует - просто используем уже существующие переменные
        if(self::$controller_instance)
        {
            $this->main_currency     = &self::$controller_instance->main_currency;
            $this->admin_currency     = &self::$controller_instance->admin_currency;
            $this->all_currencies   = &self::$controller_instance->all_currencies;
            $this->user         = &self::$controller_instance->user;
            $this->group        = &self::$controller_instance->group;
            $this->page         = &self::$controller_instance->page;
            $this->global_group_permissions = &self::$controller_instance->global_group_permissions;
        }
        else
        {
            // Сохраняем свой инстанс в статической переменной,
            // чтобы в следующий раз использовать его
            self::$controller_instance = $this;

            // Все валюты
            $this->all_currencies = $this->currencies->get_currencies(array('is_enabled'=>1));

            // Выбор текущей валюты
            /*if($currency_id = $this->request->get('currency_id', 'integer'))
                $_SESSION['currency_id'] = $currency_id;
            if($currency_id = $this->request->post('currency_id', 'integer'))
                $_SESSION['currency_id'] = $currency_id;*/

            /*// Берем валюту из сессии
            if(isset($_SESSION['currency_id']))
                $this->currency = $this->currencies->get_currency($_SESSION['currency_id']);
            // Или первую из списка
            else
                $this->currency = reset($this->all_currencies);*/
            $this->db->query("SELECT * FROM __currencies WHERE is_enabled = 1 AND use_main = 1");
            $this->main_currency = $this->db->result();
            $this->db->query("SELECT * FROM __currencies WHERE is_enabled = 1 AND use_admin = 1");
            $this->admin_currency = $this->db->result();

            // Пользователь, если залогинен
            if(isset($_SESSION['user_id']))
            {
                $u = $this->users->get_user(intval($_SESSION['user_id']));
                if($u && $u->is_enabled)
                {
                    //echo "user_name=".$u->name."<br>";
                    $this->user = $u;
                    $this->group = $this->users->get_group($this->user->group_id);

                    $query = $this->db->placehold("SELECT am.name FROM __access_modules am INNER JOIN __access_permissions ap ON am.id=ap.module_id WHERE am.section=? AND ap.group_id=?", 'admin', $this->group->id);
                    $this->db->query($query);
                    $this->global_group_permissions = $this->db->results('name');
                    if (empty($this->global_group_permissions))
                        $this->global_group_permissions = array('LoginControllerAdmin');
                }
                else
                    $this->global_group_permissions = array('LoginControllerAdmin');
            }
            else
                $this->global_group_permissions = array('LoginControllerAdmin');
            $this->design->assign('global_group_permissions', $this->global_group_permissions);

            // Текущая страница (если есть)
            /*$subdir = substr(dirname(dirname(__FILE__)), strlen($_SERVER['DOCUMENT_ROOT']));
            $page_url = trim(substr($_SERVER['REQUEST_URI'], strlen($subdir)),"/");
            if(strpos($page_url, '?') > 0)
                $page_url = substr($page_url, 0, strpos($page_url, '?'));
            $this->page = $this->pages->get_page((string)$page_url);
            $this->design->assign('page', $this->page);*/

            // Передаем в дизайн то, что может понадобиться в нем
            $this->design->assign('currencies',    $this->all_currencies);
            $this->design->assign('main_currency',    $this->main_currency);
            $this->design->assign('admin_currency',    $this->admin_currency);
            if (!empty($this->user))
            {
                $this->design->assign('user',       $this->user);
                $this->design->assign('group',      $this->group);
            }

            $this->design->assign('global_group_permissions', $this->global_group_permissions);
            $this->design->assign('config',        $this->config);
            $this->design->assign('settings',    $this->settings);
            $this->design->assign('banners',    $this->banners);
        }
    }

    function page404()
    {
        $this->design->assign('meta_title', $this->settings->page404_meta_title);
        $this->design->assign('meta_description', $this->settings->page404_meta_description);
        $this->design->assign('meta_keywords', $this->settings->page404_meta_keywords);
        $content = $this->design->fetch($this->design->getTemplateDir('frontend').'404.tpl');

        // Передаем основной блок в шаблон
        $this->design->assign('content', $content);
        return $this->design->fetch($this->design->getTemplateDir('frontend').'index.tpl');
    }

    /**
     *
     * Отображение
     *
     */
    function fetch()
    {
        return false;
    }
}