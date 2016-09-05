<?php
namespace Framework\Hash;

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
        $path = ABS . '/config/config.php';
        if(file_exists($path)){
            $config = require($path);
        }else{
            die(__FILE__ . ' ' . __LINE__ . 'Не найден файл с настройками: ' . $path);
        }

        self::$options = [
            'cost' => $config['hash_cost'],
            'salt' => $config['hash_salt']
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

    /**
     * @param $length
     */
    public function random($length)
    {

    }

}