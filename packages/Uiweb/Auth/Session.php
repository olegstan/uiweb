<?php
namespace Uiweb\Auth;

use Uiweb\Auth\User\User;
use Uiweb\Config;

class Session
{

    /**
     * @var array
     */
    public static $session;
    /**
     * @var bool
     */
    public static $is_loaded;

    public function __construct()
    {
        self::load();
    }

    /**
     * 
     */
    public static function load()
    {
        if(!self::$is_loaded){
            self::$is_loaded = true;
            self::start();
            self::$session =& $_SESSION;
        }
    }

    /**
     * 
     */
    public static function start()
    {
        //PHP_SESSION_DISABLED - 0 if sessions are disabled.
        //PHP_SESSION_NONE - 1 if sessions are enabled, but none exists.
        //PHP_SESSION_ACTIVE - 2 if sessions are enabled, and one exists.

        switch(session_status()){
            case PHP_SESSION_DISABLED:
                //0 if sessions are disabled
                die('Механизм сессий выключен на сервере');
                break;
            case PHP_SESSION_NONE:
                //1 if sessions are enabled, but none exists
                switch(Config::get('session', 'handler')){
                    case 'files':
                        //проверяем существует ли сессия
                        $session_path = ABS . '/tmp/sessions';

                        if (is_writable($session_path) && is_readable($session_path)) {
                            session_save_path($session_path);
                        }else{
                            die('Директория с сессиями недоступна для записи или для чтения');
                        }
                        break;
                    case 'mysql':

                        break;
                    case 'redis':
                        session_set_save_handler('redis');
                        session_save_path('tcp://localhost:6379');
                        break;
                }
                session_name('uiweb');
                session_start();
//
//                $this->is_auth = $this->isAuth();
                break;
            case PHP_SESSION_ACTIVE:
                //2 if sessions are enabled, and one exists
                break;
            default:
                die('Механизм сессий не работает');
        }
    }

    /**
     * @return bool
     */
    public function isAuth()
    {
        self::load();
        return isset(self::$session['auth']) && self::$session['auth'] === 1;
    }

    /**
     * @param User $user
     */
    public function login(User $user)
    {
        self::load();
        self::$session['auth'] = 1;
        self::$session['id'] = $user->id;
    }

    /**
     * 
     */
    public function logout()
    {
        self::load();
        self::$session['auth'] = 0;
        unset(self::$session['id']);
    }

    /**
     * @param $key
     * @param $value
     */
    public static function set($key, $value)
    {
        self::load();
        self::$session[$key] = $value;
    }

    /**
     * @param $key
     */
    public static function delete($key)
    {
        self::load();
        unset(self::$session[$key]);
    }

    /**
     * @param $key
     * @return mixed|null
     */
    public static function get($key)
    {
        self::load();
        if(isset(self::$session[$key])){
            return self::$session[$key];
        }else{
            return null;
        }
    }

    /**
     * @param $key
     * @return bool
     */
    public static function has($key)
    {
        self::load();
        if(isset(self::$session[$key])){
            return true;
        }else{
            return false;
        }
    }

    /**
     * @param $root
     * @param $key
     * @param $value
     */
    public static function multiSet($root, $key, $value)
    {
        self::load();
        self::$session[$root][$key] = $value;
    }

    /**
     * @param $root
     * @param $key
     * @return null
     */
    public static function multiGet($root, $key)
    {
        self::load();
        if(isset(self::$session[$root][$key])){
            return self::$session[$root][$key];
        }else{
            return null;
        }
    }

    /**
     * @param $root
     * @param $key
     * @return bool
     */
    public static function multiHas($root, $key)
    {
        self::load();
        if(isset(self::$session[$root][$key])){
            return true;
        }else{
            return false;
        }
    }

    /**
     * @param $root
     * @param $key
     */
    public static function multiDelete($root, $key)
    {
        self::load();
        unset(self::$session[$root][$key]);
    }
}