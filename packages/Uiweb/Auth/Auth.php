<?php
namespace Uiweb\Auth;

use Uiweb\App;
use Uiweb\Auth\User\User;

/**
 * Class Auth
 * @package Uiweb\Auth
 */
class Auth
{
    /**
     * @var Session
     */
    private static $session;
    /**
     * @var Cookie
     */
    private static $cookie;
    /**
     * @var User
     */
    public static $user;

    /**
     * @return Session
     */
    public static function getSession()
    {
        if(!self::$session){
            self::$session = App::resolve(Session::class);
        }
        return self::$session;
    }

    /**
     * @return Cookie
     */
    public static function getCookie()
    {
        if(!self::$cookie){
            self::$cookie = App::resolve(Cookie::class);
        }
        return self::$cookie;
    }

    /**
     * @return bool
     */
    public static function check()
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
     * @param bool $remember
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

    /**
     * @return User
     */
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