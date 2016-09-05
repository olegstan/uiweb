<?php
namespace app\models\menu;

use app\layer\LayerModel;
use core\db\Database;
use \stdClass;

class MenuAdmin extends LayerModel
{
    protected $table = 'mc_menu_admin';

    public $items = [];
    public $is_collapsed;
    public $active;

    public function afterSelect($rules = null)
    {
        $this->url = '/admin' . $this->url;

        return $this;
    }

//    private function init_tree()
//    {
//        $tree = [];
//        $pointers = [];
//
//        $query = $this->db->query("SELECT distinct menu_id FROM __menu_admin");
//
//        foreach($this->db->results('menu_id') as $menu_id)
//        {
//            $pointers[$menu_id] = [];
//            $tree[$menu_id] = new stdClass();
//            $tree[$menu_id]->subitems = [];
//            $pointers[$menu_id][0] = &$tree[$menu_id];
//            $pointers[$menu_id][0]->path = [];
//        }
//
//        // Указатели на узлы дерева
//        /*$pointers = [];
//        $pointers[0] = &$tree;
//        $pointers[0]->path = [];*/
//
//        // Выбираем все пункты меню
//        $query = $this->db->placehold("SELECT ma.id, ma.menu_id, ma.parent_id, ma.name, ma.css_class, ma.module_id, ma.use_module_id, ma.position, ma.is_visible, ma.use_default, ma.icon,
//                                            mm.url, mm.options,
//                                            mm_sub.url as sub_url, mm_sub.options as sub_options, mm.module as module_name
//                                        FROM __menu_admin ma
//                                            LEFT JOIN __menu_modules mm ON ma.module_id=mm.id
//                                            LEFT JOIN __menu_admin ma_sub ON ma.id=ma_sub.parent_id AND ma_sub.id = (SELECT id FROM __menu_admin t WHERE t.parent_id=ma.id AND t.use_default=1)
//                                            LEFT JOIN __menu_modules mm_sub ON ma_sub.module_id=mm_sub.id
//                                        /*WHERE ma.is_visible=1*/
//                                        ORDER BY ma.parent_id, ma.position");
//        $this->db->query($query);
//        $items = $this->db->results();
//
//        $finish = false;
//        // Не кончаем, пока не кончатся пункты меню, или пока ни один из оставшихся некуда приткнуть
//        while(!empty($items)  && !$finish)
//        {
//            $flag = false;
//            // Проходим все выбранные пункты меню
//            foreach($items as $k=>$item)
//            {
//                if(isset($pointers[$item->menu_id][$item->parent_id]))
//                {
//                    // В дерево меню (через указатель) добавляем текущий пункт
//                    $pointers[$item->menu_id][$item->id] = $pointers[$item->menu_id][$item->parent_id]->subitems[] = $item;
//
//                    // Путь к текущему пункту меню
//                    $curr = clone($pointers[$item->menu_id][$item->id]);
//                    $pointers[$item->menu_id][$item->id]->path = array_merge((array)$pointers[$item->menu_id][$item->parent_id]->path, array($curr));
//
//                    // Убираем использованный пункт меню из массива пунктов
//                    unset($items[$k]);
//                    $flag = true;
//                }
//            }
//            if(!$flag) $finish = true;
//        }
//
//        // Для каждого пункта меню узнаем всех ее деток
//        foreach(array_keys($pointers) as $menu_id)
//        {
//            $ids = array_reverse(array_keys($pointers[$menu_id]));
//            foreach($ids as $id)
//            {
//                if($id>0)
//                {
//                    $pointers[$menu_id][$id]->children[] = $id;
//
//                    if(isset($pointers[$menu_id][$pointers[$menu_id][$id]->parent_id]->children))
//                        $pointers[$menu_id][$pointers[$menu_id][$id]->parent_id]->children = array_merge($pointers[$menu_id][$id]->children, $pointers[$menu_id][$pointers[$menu_id][$id]->parent_id]->children);
//                    else
//                        $pointers[$menu_id][$pointers[$menu_id][$id]->parent_id]->children = $pointers[$menu_id][$id]->children;
//                }
//            }
//            unset($pointers[$menu_id][0]);
//        }
//
//        /*$ids = array_reverse(array_keys($pointers));
//        foreach($ids as $id)
//        {
//            if($id>0)
//            {
//                $pointers[$id]->children[] = $id;
//
//                if(isset($pointers[$pointers[$id]->parent_id]->children))
//                    $pointers[$pointers[$id]->parent_id]->children = array_merge($pointers[$id]->children, $pointers[$pointers[$id]->parent_id]->children);
//                else
//                    $pointers[$pointers[$id]->parent_id]->children = $pointers[$id]->children;
//            }
//        }
//        unset($pointers[0]);*/
//
//        $this->items_tree = $tree;//$tree->subitems;
//        $this->all_items = $pointers;
//    }
//
//    // Функция возвращает дерево меню
//    public function get_menu_tree($menu_id = 0)
//    {
//        if(!isset($this->items_tree))
//            $this->init_tree();
//        return $this->items_tree[$menu_id];
//    }
//
//    public function get_items($filter = [])
//    {
//        $parent_id_filter = "";
//        if (isset($filter['parent_id']))
//            $parent_id_filter = $this->db->placehold("AND ma.parent_id=?", intval($filter['parent_id']));
//        $is_visible_filter = "";
//        if (isset($filter['is_visible']))
//            $is_visible_filter = $this->db->placehold("AND ma.is_visible=?", intval($filter['is_visible']));
//
//        $query = "SELECT ma.id, ma.parent_id, ma.name, ma.css_class, ma.module_id, ma.use_module_id, ma.position, ma.is_visible, ma.use_default, ma.icon,
//                        mm.url, mm.options,
//                        mm_sub.url as sub_url, mm_sub.options as sub_options, mm.module as module_name
//                    FROM __menu_admin ma
//                        LEFT JOIN __menu_modules mm ON ma.module_id=mm.id
//                        LEFT JOIN __menu_admin ma_sub ON ma.id=ma_sub.parent_id AND ma_sub.id = (SELECT id FROM __menu_admin t WHERE t.parent_id=ma.id AND t.use_default=1)
//                        LEFT JOIN __menu_modules mm_sub ON ma_sub.module_id=mm_sub.id
//                    WHERE 1 $parent_id_filter $is_visible_filter
//                    ORDER BY ma.parent_id, ma.position";
//        $this->db->query($query);
//        return $this->db->results();
//    }
//
//    /*public function get_item($id = null)
//    {
//        if (empty($id))
//            return false;
//
//        $query = $this->db->placehold("SELECT id, parent_id, name, css_class, module_id, use_module_id, position, is_visible
//                    FROM __menu_admin
//                    WHERE id=?", intval($id));
//        $this->db->query($query);
//
//        return $this->db->result();
//    }*/
//
//    // Функция возвращает заданный пункт меню
//    public function get_item($id)
//    {
//        if(!isset($this->all_items))
//            $this->init_tree();
//
//        $this->db->query("SELECT menu_id FROM __menu_admin WHERE id=?", $id);
//        $menu_id = $this->db->result('menu_id');
//        if (empty($menu_id))
//            $menu_id = 0;
//
//
//        if(is_numeric($id) && array_key_exists(intval($id), $this->all_items[$menu_id]))
//            return $item = $this->all_items[$menu_id][intval($id)];
//        return false;
//    }
//
//    public function add_item($item)
//    {
//        $item = (array)$item;
//        if ($item['use_default'])
//            $this->db->query("UPDATE __menu_admin SET use_default=0 WHERE parent_id=?", $item['parent_id']);
//
//        $query = $this->db->placehold('INSERT INTO __menu_admin SET ?%', $item);
//        if(!$this->db->query($query))
//            return false;
//        $id = $this->db->insert_id();
//        $this->db->query("UPDATE __menu_admin SET position=id WHERE id=?", $id);
//        $this->init_tree();
//        return $id;
//    }
//
//    public function update_item($id, $item)
//    {
//        $item = (array)$item;
//        if (array_key_exists('use_default', $item) && $item['use_default'])
//            $this->db->query("UPDATE __menu_admin SET use_default=0 WHERE parent_id=?", $item['parent_id']);
//        $query = $this->db->placehold('UPDATE __menu_admin SET ?% WHERE id in (?@)', $item, (array)$id);
//        if(!$this->db->query($query))
//            return false;
//        $this->init_tree();
//        return $id;
//    }
//
//    public function delete_item($id)
//    {
//        if(!empty($id))
//        {
//            $query = $this->db->placehold("DELETE FROM __menu_admin WHERE id=? LIMIT 1", intval($id));
//            $this->db->query($query);
//        }
//        $this->init_tree();
//    }
//
//    public function get_selected_menu_item($module_name)
//    {
//        $query = $this->db->placehold('SELECT ma.id FROM __menu_admin ma INNER JOIN __menu_modules mm ON ma.module_id=mm.id WHERE mm.module=? LIMIT 0,1', $module_name);
//        $this->db->query($query);
//        $item = $this->db->result();
//        if ($item)
//            return $item->id;
//        $query = $this->db->placehold('SELECT ma.id FROM __menu_admin ma INNER JOIN __menu_modules mm ON ma.use_module_id=mm.id WHERE mm.module=? LIMIT 0,1', $module_name);
//        $this->db->query($query);
//        $item = $this->db->result();
//        if ($item)
//            return $item->id;
//        return false;
//    }
    
}