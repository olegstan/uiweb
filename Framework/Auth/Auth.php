<?php
namespace Framework\Auth;

use Framework\Auth\User\User;
use Framework\Pattern\PatternTraits\NonStaticTrait;
use Framework\Pattern\PatternTraits\SingletonTrait;

/**
 * Class Auth
 * @package Framework\Auth
 */
class Auth
{
    use SingletonTrait, NonStaticTrait;

    /**
     * @var Session
     */
    private static $session;
    /**
     * @var Cookie
     */
    private static $cookie;
    /**
     * @var bool
     */
    public static $is_auth;
    /**
     * @var bool
     */
    public static $is_admin;
    /**
     * @var User
     */
    public static $user;

    /**
     *
     */
    public function __construct()
    {

    }

    public function __handle()
    {
        self::$session = Session::getInstance();
        self::$cookie = Cookie::getInstance();
        return $this;
    }

    public static function getSession()
    {
        return self::$session = Session::getInstance();
    }

    public static function getCookie()
    {
        return self::$cookie = Cookie::getInstance();
    }

    /**
     * @return bool
     */
    public static function isAuth()
    {
        /**
         * @var int $id
         */
        if($id = self::getSession()->isAuth() || $id = self::getCookie()->isAuth()){
            return self::login($id);
        }else{
            return false;
        }
    }

    /**
     * @param $id
     * @return bool
     */
    public static function login($id, $remember = false)
    {
        /**
         * @var User $user
         */
        self::$user = (new User())->findById($id)->get();

        if(self::$user){
            self::$session->login($id);
            if($remember){
                self::$cookie->login($id);
            }
            return true;
        }else{
            return false;
        }
    }

    public static function getUser()
    {
        return self::$user;
    }

    /**
     * @return void
     */
    public static function logout()
    {
        self::$session->logout();
        self::$cookie->logout();
    }
}