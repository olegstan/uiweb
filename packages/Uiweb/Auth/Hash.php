<?php
namespace Uiweb\Hash;

use Uiweb\Config;

class Hash
{
    /**
     * @var
     */
    public static $options;

    /**
     * @param $str
     * @return bool|string
     */
    public static function password($str)
    {
        self::$options = [
            'cost' => Config::get('auth.hash.cost'),
            'salt' => Config::get('auth.hash.salt')
        ];

        return password_hash($str, PASSWORD_BCRYPT, self::$options);
    }

    /**
     * @param $str
     * @param $hash
     * @return bool
     */
    public static function verify($str, $hash)
    {
        return password_verify($str, $hash);
    }
}