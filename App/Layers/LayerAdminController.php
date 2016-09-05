<?php
namespace App\Layers;

use Framework\Controller\HttpController;

class LayerAdminController extends HttpController
{
//    public function __construct()
//    {
//        parent::__construct();
//
//        $request_uri = str_replace('/admin', '', $this->getCore()->request_uri);
//
//        $current_menu = (new MenuAdmin())
//            ->query()
//            ->select()
//            ->where('url = :url', [':url' => $request_uri])
//            ->limit()
//            ->execute()
//            ->one()
//            ->getResult();
//
//        if($current_menu) {
//            $menus = (new MenuAdmin())
//                ->query()
//                ->select()
//                ->where('parent_id = 0 AND menu_id = 0 AND is_visible = 1')
//                ->order('position')
//                ->execute()
//                ->all(null, 'id');
//
//            $menus_result = $menus->getResult();
//            $menus_ids = $menus->getId();
//
//            foreach ($menus as $menu) {
//                if ($menu->id === $current_menu->id) {
//                    $menu->active = true;
//                }
//            }
//
//            if ($menus_result) {
//                $second_menus = (new MenuAdmin())
//                    ->query()
//                    ->select()
//                    ->where('parent_id IN (' . implode(',', $menus_ids) . ')')
//                    ->order('position')
//                    ->execute()
//                    ->all(null, 'id');
//
//                $second_menu_result = $second_menus->getResult();
//                $second_menu_ids = $second_menus->getId();
//
//                foreach ($second_menu_result as $second_menu) {
//                    if (isset($menus_result[$second_menu->parent_id])) {
//                        if ($second_menu->id === $current_menu->id) {
//                            $second_menu->active = true;
//                        }
//                        $menus_result[$second_menu->parent_id]->items[] = $second_menu;
//                    }
//                }
//
//                if ($second_menu_result) {
//                    $third_menus = (new MenuAdmin())
//                        ->query()
//                        ->select()
//                        ->where('parent_id IN (' . implode(',', $second_menu_ids) . ')')
//                        ->order('position')
//                        ->execute()
//                        ->all(null, 'id');
//
//                    $third_menu_result = $third_menus->getResult();
//
//                    foreach ($third_menu_result as $third_menu) {
//                        if (isset($second_menu_result[$third_menu->parent_id])) {
//                            if ($third_menu->id === $current_menu->id) {
//                                $third_menu->active = true;
//                            }
//                            $second_menu_result[$third_menu->parent_id]->items[] = $third_menu;
//                        }
//                    }
//                }
//            }
//
//            /**
//             * назначаем открытое меню для родителя
//             */
//
//            if (isset($second_menu_result[$current_menu->parent_id])) {
//                $menus_result[$second_menu_result[$current_menu->parent_id]->parent_id]->is_collapsed = true;
//                $menus_result[$second_menu_result[$current_menu->parent_id]->parent_id]->active = true;
//
//                $second_menu_result[$current_menu->parent_id]->is_collapsed = true;
//                $second_menu_result[$current_menu->parent_id]->active = true;
//            } else if (isset($menus_result[$current_menu->parent_id])) {
//                $menus_result[$current_menu->parent_id]->is_collapsed = true;
//                $menus_result[$current_menu->parent_id]->active = true;
//            } else if ($menus_result[$current_menu->id]) {
//                $menus_result[$current_menu->id]->is_collapsed = true;
//                $menus_result[$current_menu->id]->active = true;
//            }
//
//            $this->design->assign('menus', $menus_result);
//        }
//        }else{
//            $error = '404';
//            die((new ErrorController())->$error('В контроллере нет такого меню с указанным урлом ' . $request_uri));
//        }





//        $menu_tree = (new MenuAdmin())
//            ->query()
//            ->select([
//                'mc_menu_admin.id',
//                'mc_menu_admin.menu_id',
//                'mc_menu_admin.parent_id',
//                'mc_menu_admin.name',
//                'mc_menu_admin.css_class',
//                'mc_menu_admin.module_id',
//                'mc_menu_admin.use_module_id',
//                'mc_menu_admin.is_visible',
//                'mc_menu_admin.use_default',
//                'mc_menu_admin.icon',
//                'mc_menu_modules.url'
//            ])
//            ->leftJoin('mc_menu_modules', 'mc_menu_admin.module_id = mc_menu_modules.id')
//            ->where('parent_id = 0 AND menu_id = 0 AND is_visible = 1')
//            ->order('mc_menu_admin.position')
//            ->execute()
//            ->all()
//            ->getResult();
//
//        echo '<pre>';
//        var_dump($menu_tree);
//        echo '</pre>';
//        die();


//        $menu_tree_up = (new MenuAdmin())->get_menu_tree(1);
//        $menu_tree_dop = (new MenuAdmin())->get_menu_tree(2);

//        $this->check_visible_menu_items($menu_tree->subitems);
//        $this->make_url_menu_items($menu_tree->subitems);

//
//        if (!$active_menu_item)
//            f = reset($menu_tree->subitems)->id;
//
        //$active_menu = (new MenuAdmin())->get_item($active_menu_item);

//        $this->design->assign('menu_tree_up', $menu_tree_up);
//        $this->design->assign('menu_tree_dop', $menu_tree_dop);
//        $this->design->assign('active_menu_item', $active_menu_item);
//        $this->design->assign('active_menu', $active_menu);
//    }
//
//    public function render($path_to_tpl, $layout = 'index.tpl')
//    {
//        $this->design->assign('settings', $this->getCore()->settings);
//
//        $this->design->assign('libraries', '/libraries/');
//
//        $this->design->assign('core', $this->getCore());
//        $this->design->assign('flash_message', $this->getCore()->flash->getLast());
//
//        $this->design->assign('path_frontend_template', '/templates/' . $this->getCore()->config['template'] . '/');
//        $this->design->assign('path_backend_template', '/minicart/templates/system/');
//
//        $content = $this->design->fetch($path_to_tpl);
//        $this->design->assign('content', $content);
//        return $this->design->fetch(SYSTEM_TPL . '/system/html/layouts/' . $layout);
//    }
}