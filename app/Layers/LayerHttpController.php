<?php
namespace App\Layers;

use Framework\Controller\HttpController;

class LayerHttpController extends HttpController
{
//    public function __construct()
//    {
//        parent::__construct();
//    }

    public function render($path_to_tpl, $layout = 'index.tpl')
    {
//        $this->design->assign('libraries', '/libraries/');
//
//        $this->design->assign('core', $this->getCore());
//        $this->design->assign('flash_message', $this->getCore()->flash->getLast());
//
//        $this->design->assign('path_frontend_template', '/templates/' . $this->getCore()->config['template'] . '/');
//        $this->design->assign('path_backend_template', '/minicart/templates/system/');
//
//
//        /**
//         * общая часть
//         * меню
//         * баннеры
//         */
//
//
//        /**
//         * получение баннеров
//         */
//
//        $banners = (new Banner())
//            ->query()
//            ->select()
//            ->where('is_visible = 1')
//            ->execute()
//            ->all(null, 'id')
//            ->getResult();
//
//        $this->design->assign('banners', $banners);
//
//        $company_menu = (new MaterialMenu)
//            ->query()
//            ->with(['items'])
//            ->select()
//            ->where('id = 1')
//            ->limit()
//            ->execute()
//            ->one()
//            ->getResult();
//
//        $this->design->assign('company_menu', $company_menu);
//
//        $this->design->assign('categories_frontend_all', (new Category())->get_categories_tree());
//
//        $this->design->assign('settings', $this->getCore()->settings);
//        /**
//         *
//         */
//
//
//        ///// пути до библиотек
//
//
//        //контроллер может содержать внутренние подшаблоны
//        //поэтому смарти хранит переменную коре
//        //она записывается два раза
//        //при __construct и при вызове render
//
//        $content = $this->design->fetch($path_to_tpl);
//
//        $this->design->assign('content', $content);
//
//        return $this->design->fetch(TPL . '/test/html/' . $layout);

    }
}