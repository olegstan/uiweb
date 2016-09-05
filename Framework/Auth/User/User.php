<?php
namespace Framework\Auth\User;

use Framework\Model\Types\DatabaseModel;

class User extends DatabaseModel
{
    protected $table = 'users';

    /**
     * @var int
     */
    public $id;
    /**
     * @var string
     */
    public $password;
    /**
     * @var string
     */
    public $auth_key;
    /**
     * @var
     */
    public $last_login_dt;

    /**
     *
     * новейшие методы
     */

    public function isAdmin()
    {
//        if($this->group_id == 1){
//            return true;
//        }else{
//            return false;
//        }
    }

//    public static function groups(Collection $collection, $rules = null)
//    {
//        switch($rules['type']){
//            case 'one':
//
//                break;
//            case 'all':
//                $users = $collection->getResult();
//
//                if($users){
//                    $groups = (new UserGroup())
//                        ->query()
//                        ->select()
//                        ->execute()
//                        ->all(null, 'id')
//                        ->getResult();
//
//                    if($groups){
//                        foreach ($users as $user) {
//                            $user->group = $groups[$user->group_id];
//                        }
//                    }
//                }
//                break;
//        }
//    }




    /**
     *
     * новейшие методы
     */



    // осторожно, при изменении соли испортятся текущие пароли пользователей
    private $salt = '8e86a279d6e182b3c811c559e6b15484';

    function get_users($filter = array())
    {
        $limit = 1000;
        $page = 1;
        $group_id_filter = '';
        $keyword_filter = '';

        if(isset($filter['limit']))
            $limit = max(1, intval($filter['limit']));

        if(isset($filter['page']))
            $page = max(1, intval($filter['page']));

        if(isset($filter['group_id']))
            $group_id_filter = $this->db->placehold('AND u.group_id in(?@)', (array)$filter['group_id']);

        if(isset($filter['keyword']))
        {
            $keywords = explode(' ', $filter['keyword']);
            foreach($keywords as $keyword)
                $keyword_filter .= $this->db->placehold('AND u.name LIKE "%'.mysql_real_escape_string(trim($keyword)).'%" OR u.email LIKE "%'.mysql_real_escape_string(trim($keyword)).'%" OR CONCAT("+7", u.phone_code, u.phone) LIKE "%'.mysql_real_escape_string(trim($keyword)).'%" OR CONCAT("+7", u.phone2_code, u.phone2) LIKE "%'.mysql_real_escape_string(trim($keyword)).'%"');
        }

        $sql_limit = $this->db->placehold(' LIMIT ?, ? ', ($page-1)*$limit, $limit);
        // Выбираем пользователей
        $query = $this->db->placehold("SELECT u.id, u.email, u.phone, u.phone_code, u.phone2, u.phone2_code, u.password, u.name, u.group_id, u.is_enabled, u.mail_confirm, u.sms_confirm,
            unix_timestamp(u.created) as created, u.delivery_address, u.organization_name, u.yur_postcode, u.yur_city, u.yur_address, u.yur_inn, u.yur_kpp, u.yur_bank_name, u.yur_bank_city,
            u.yur_bank_bik, u.yur_bank_corr_schet, u.yur_bank_rasch_schet, g.discount FROM __access_users u
                                        LEFT JOIN __access_groups g ON u.group_id=g.id
                                        WHERE 1 $group_id_filter $keyword_filter ORDER BY u.created desc $sql_limit");
        $this->db->query($query);
        return $this->db->results();
    }

    function count_users($filter = array())
    {
        $group_id_filter = '';
        $keyword_filter = '';

        if(isset($filter['group_id']))
            $group_id_filter = $this->db->placehold('AND u.group_id in(?@)', (array)$filter['group_id']);

        if(isset($filter['keyword']))
        {
            $keywords = explode(' ', $filter['keyword']);
            foreach($keywords as $keyword)
                $keyword_filter .= $this->db->placehold('AND u.name LIKE "%'.mysql_real_escape_string(trim($keyword)).'%" OR u.email LIKE "%'.mysql_real_escape_string(trim($keyword)).'%"');
        }

        // Выбираем пользователей
        $query = $this->db->placehold("SELECT count(*) as count FROM __access_users u
                                        LEFT JOIN __access_groups g ON u.group_id=g.id
                                        WHERE 1 $group_id_filter $keyword_filter");
        $this->db->query($query);
        return $this->db->result('count');
    }

    function get_user($id)
    {
        if(is_numeric($id))
            $where = $this->db->placehold(' WHERE u.id=? ', intval($id));
        else
            $where = $this->db->placehold(' WHERE (u.email=? OR u.phone=?)', $id, $id);

        // Выбираем пользователя
        $query = $this->db->placehold("SELECT u.id, u.email, u.phone, u.phone_code, u.phone2, u.phone2_code, u.password, u.name, u.group_id, u.is_enabled, u.mail_confirm, u.sms_confirm,
            unix_timestamp(u.created) as created, u.delivery_address, u.reset_url, u.organization_name, u.yur_postcode, u.yur_city, u.yur_address, u.yur_inn, u.yur_kpp, u.yur_bank_name, u.yur_bank_city,
            u.yur_bank_bik, u.yur_bank_corr_schet, u.yur_bank_rasch_schet, g.discount FROM __access_users u LEFT JOIN __access_groups g ON u.group_id=g.id $where LIMIT 1", $id);
        $this->db->query($query);
        $user = $this->db->result();
        if(empty($user))
            return false;
        $user->discount *= 1; // Убираем лишние нули, чтобы было 5 вместо 5.00
        return $user;
    }

    public function add_user($user)
    {
        $user = (array)$user;
        if(isset($user['password']))
            $user['password'] = md5($this->salt.$user['password'].md5($user['password']));

        $query = "";
        if ($user['email'] && $user['phone'])
            $query = $this->db->placehold("SELECT count(*) as count FROM __access_users WHERE email=? OR (phone_code=? AND phone=?) OR (phone2_code=? AND phone2=?)", $user['email'], $user['phone_code'], $user['phone'], $user['phone_code'], $user['phone']);
        else
            if ($user['email'])
                $query = $this->db->placehold("SELECT count(*) as count FROM __access_users WHERE email=?", $user['email']);
            else
                $query = $this->db->placehold("SELECT count(*) as count FROM __access_users WHERE phone=?", $user['phone']);
        if ($query)
        {
            $this->db->query($query);

            if($this->db->result('count') > 0)
                return false;
        }

        $query = $this->db->placehold("INSERT INTO __access_users SET ?%", $user);
        $this->db->query($query);
        return $this->db->insert_id();
    }

    public function update_user($id, $user)
    {
        $user = (array)$user;
        if(isset($user['password']))
            $user['password'] = md5($this->salt.$user['password'].md5($user['password']));
        $query = $this->db->placehold("UPDATE __access_users SET ?% WHERE id=? LIMIT 1", $user, intval($id));
        $this->db->query($query);
        return $id;
    }

    /*
    *
    * Удалить пользователя
    * @param $post
    *
    */
    public function delete_user($id)
    {
        if(!empty($id))
        {
            $query = $this->db->placehold("UPDATE __orders SET user_id=NULL WHERE id=? LIMIT 1", intval($id));
            $this->db->query($query);

            $query = $this->db->placehold("DELETE FROM __access_users WHERE id=? LIMIT 1", intval($id));
            if($this->db->query($query))
                return true;
        }
        return false;
    }

    function get_groups()
    {
        // Выбираем группы
        $query = $this->db->placehold("SELECT g.id, g.name, g.group_name, g.discount, g.css_class, g.position FROM __access_groups AS g ORDER BY g.position");
        $this->db->query($query);
        return $this->db->results();
    }

    function get_group($id)
    {
        // Выбираем группу
        $query = $this->db->placehold("SELECT * FROM __access_groups WHERE id=? LIMIT 1", $id);
        $this->db->query($query);
        $group = $this->db->result();

        return $group;
    }


    public function add_group($group)
    {
        $query = $this->db->placehold("INSERT INTO __access_groups SET ?%", $group);
        $this->db->query($query);
        return $this->db->insert_id();
    }

    public function update_group($id, $group)
    {
        $query = $this->db->placehold("UPDATE __access_groups SET ?% WHERE id=? LIMIT 1", $group, intval($id));
        $this->db->query($query);
        return $id;
    }

    public function delete_group($id)
    {
        if(!empty($id))
        {
            $query = $this->db->placehold("UPDATE __access_users SET group_id=NULL WHERE group_id=? LIMIT 1", intval($id));
            $this->db->query($query);

            $query = $this->db->placehold("DELETE FROM __access_groups WHERE id=? LIMIT 1", intval($id));
            if($this->db->query($query))
                return true;
        }
        return false;
    }

    public function check_password($login, $password)
    {
        $encpassword = md5($this->salt.$password.md5($password));
        $query = $this->db->placehold("SELECT id FROM __access_users WHERE (email=? OR CONCAT('+7',phone_code,phone)=?) AND password=? LIMIT 1", $login, $login, $encpassword);
        $this->db->query($query);
        if($id = $this->db->result('id'))
            return $id;
        return false;
    }

    public function get_access_modules($filter = array()){
        $section_filter = "";
        if (isset($filter['section']))
            $section_filter = $this->db->placehold('AND section in (?@)', (array)$filter['section']);

        $query = $this->db->placehold("SELECT id, name, description, section FROM __access_modules WHERE 1 $section_filter ORDER BY section, name");
        $this->db->query($query);
        return $this->db->results();
    }

    public function check_permission($user_id, $module, $section)
    {
        $query = $this->db->placehold("SELECT 1 FROM __access_permissions ap
                                        INNER JOIN __access_modules am ON ap.module_id=am.id
                                        INNER JOIN __access_users au ON ap.group_id=au.group_id WHERE au.id=? AND am.name=? AND am.section=? AND ap.value=?",
                                    $user_id, $module, $section, 1);
        $this->db->query($query);

        return $this->db->result() !== false;
    }









}