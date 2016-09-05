<?php
namespace app\models;

use app\layer\LayerModel;
use core\Collection;
use app\models\user\User;

class Callback extends LayerModel
{
    protected $table = 'mc_callbacks';

    public static $days_week = [
        'Monday' => 'Пн',
        'Tuesday' => 'Вт',
        'Wednesday' => 'Ср',
        'Thursday' => 'Чт',
        'Friday' => 'Пт',
        'Saturday' => 'Сб',
        'Sunday' => 'Вс'
    ];

    public function users(Collection $collection, $rules = null)
    {
        switch($rules['type']){
            case 'one':

                break;
            case 'all':
                $callbacks = $collection->getResult();
                $users_ids = $collection->getField('user_id');

                if($callbacks){
                    $users = (new User())
                        ->query()
                        ->select()
                        ->where('id in (' . implode(',', array_unique($users_ids)) . ') AND module_id = :module_id', [':module_id' => current($reviews)->module_id])
                        ->order('position')
                        ->execute()
                        ->all('reviews', 'object_id')
                        ->getResult();

                    if($users) {
                        foreach($callbacks as $k => $callback){
                            $callbacks->user = $users[$callback->user_id];
                        }
                    }
                }
                break;
        }


        //            if ($callback->user_id > 0)
//                $callbacks[$index]->user = $this->users->get_user($callback->user_id);

    }

    public function getDayString(DateTime $today, DateTime $yesterday)
    {
        $created_dt = (new DateTime($this->created_dt))->format('Ymd');

        if ($created_dt == $today->format('Ymd')) {
            $this->day_str = 'Сегодня';
        }else{
            if($created_dt == $yesterday->format('Ymd')){
                $this->day_str = 'Вчера';
            }else{
                $created_year_dt = (new DateTime($this->created_dt))->format('Ymd');
                $created_day_dt = (new DateTime($this->created_dt))->format('l');

                if ($created_year_dt == date('Y')) {
                    $this->day_str = self::$days_week[$created_day_dt] . ' ' . (new DateTime($this->created_dt))->format('d.m');
                } else {
                    $this->day_str = self::$days_week[$created_day_dt] . ' ' . (new DateTime($this->created_dt))->format('d.m.Y');
                }
            }

        }
    }


































    public function get_callback($id)
    {

        $query = $this->db->placehold("SELECT id, unix_timestamp(created) created, user_id, user_name, phone_code, phone, call_time, message, moderated, state, ip FROM __callbacks WHERE id=? LIMIT 1", intval($id));

        $this->db->query($query);
        return $this->db->result();
    }

    public function get_callbacks($filter = array())
    {
        // По умолчанию
        $limit = 100;
        $page = 1;
        $keyword_filter = '';
        $moderated_filter = '';

        if(isset($filter['limit']))
            $limit = max(1, intval($filter['limit']));

        if(isset($filter['page']))
            $page = max(1, intval($filter['page']));

        $sql_limit = $this->db->placehold(' LIMIT ?, ? ', ($page-1)*$limit, $limit);

        if(!empty($filter['keyword']))
            $keyword_filter = $this->db->placehold('AND (c.message LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%" OR
                                                         c.user_name LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%" OR
                                                         au.name LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%" OR
                                                         c.id = "'.mysql_real_escape_string(trim($filter['keyword'])).'" OR
                                                         CONCAT("+7",c.phone_code,c.phone) LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%") ');

        if (isset($filter['moderated']))
            $moderated_filter = $this->db->placehold("AND c.moderated=?", intval($filter['moderated']));

        $order = 'created desc';


        $query = "SELECT c.id, unix_timestamp(c.created) created, c.user_id, c.user_name, c.phone_code, c.phone, c.call_time, c.message, c.moderated, c.state, c.ip
                FROM __callbacks c
                    LEFT JOIN __access_users au ON c.user_id = au.id
                WHERE 1
                    $keyword_filter
                    $moderated_filter
                ORDER BY $order
                    $sql_limit";

        $query = $this->db->placehold($query);
        $this->db->query($query);

        return $this->db->results();
    }

    public function count_callbacks($filter = array())
    {
        $keyword_filter = '';
        $moderated_filter = '';

        if(!empty($filter['keyword']))
            $keyword_filter = $this->db->placehold('AND (c.message LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%" OR
                                                         c.user_name LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%" OR
                                                         au.name LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%" OR
                                                         c.id = "'.mysql_real_escape_string(trim($filter['keyword'])).'" OR
                                                         CONCAT("+7",c.phone_code,c.phone) LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%") ');

        if (isset($filter['moderated']))
            $moderated_filter = $this->db->placehold("AND c.moderated=?", intval($filter['moderated']));

        $query = "SELECT count(distinct c.id) as count
                FROM __callbacks c
                    LEFT JOIN __access_users au ON c.user_id = au.id
                WHERE 1
                    $keyword_filter
                    $moderated_filter";
        $this->db->query($query);
        return $this->db->result('count');
    }

    public function update_callback($id, $callback)
    {
        $callback = (array) $callback;
        $callback['moderated'] = 1;

        $query = $this->db->placehold("UPDATE __callbacks SET ?% WHERE id in(?@)", $callback, (array)$id);
        $this->db->query($query);
        return $id;
    }

    public function add_callback($callback)
    {
        $callback = (array) $callback;

        if (!array_key_exists('moderated', $callback))
            $callback['moderated'] = 0;

        $query = $this->db->placehold('INSERT INTO __callbacks
        SET ?%',
        $callback);

        if(!$this->db->query($query))
            return false;

        return $this->db->insert_id();
    }

    public function delete_callback($id)
    {
        if(!empty($id))
        {
            $query = $this->db->placehold("DELETE FROM __callbacks WHERE id=? LIMIT 1", intval($id));
            $this->db->query($query);
        }
    }
}
