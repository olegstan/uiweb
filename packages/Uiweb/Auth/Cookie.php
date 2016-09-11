<?php
namespace Uiweb\Auth;

use Uiweb\Auth\User\User;

/**
 * Class Cookie
 * @package Uiweb\Auth
 */
class Cookie
{
    /**
     * @var array
     */
    public $cookie;

    /**
     * Cookie constructor.
     */
    public function __construct()
    {
        $this->cookie =& $_COOKIE;
    }

    /**
     * @return bool|int
     */
    public function isAuth()
    {
        if(isset($this->cookie['auth'])){
            $data = explode(':', $this->cookie['auth']);

            //получаем логин
            $id = $data[0];
            //получаем hash
            $cookie_hash = $data[1];

            /**
             * @var User $user
             */
            $user = (new User())->findById($id)->getResult();

            //получем hash пользователя
            $password_hash = $user->password;
            $cookie_hash_user = sha1($user->auth_key . ':' . $_SERVER['REMOTE_ADDR'] . ':' . $user->last_login_dt . ':' . $password_hash);

            //проверям есть ли такой пользователь и совпадаeт ли hash
            if($user && $cookie_hash === $cookie_hash_user){
                //логиним пользователя по куке
                return $id;
            }
        }else{
            return false;
        }
    }

    /**
     * @param User $user
     */
    public function login(User $user)
    {
        $current_time = date('Y-m-d H:i:s');
        setcookie('auth', $user->id . ':' . sha1($auth_key = uniqid() . ':' . $_SERVER['REMOTE_ADDR'] . ':' . $current_time . ':' . $user->password), time() + 60*60*24, '/');

        //записываем данные в пользователя для дальнейшей авторизации по куке
        $user->last_login_dt = $current_time;
        $user->auth_key = $auth_key;
        $user->update();
    }

    /**
     * 
     */
    public function logout()
    {
        unset($this->cookie['auth']);
        setcookie('auth', null, 0, '/');
    }
}