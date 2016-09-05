<?php
namespace Framework\Db\SqlConnectors;

use Framework\Config;
use PDO;

class MysqlDbConnect
{
    private static $pdo = [];

    public static function getPDO($connection)
    {
       $config = Config::get('database', $connection);
        if (!isset(self::$pdo[$connection])) {
            try{
                self::$pdo[$connection] = new PDO($config['type'] . ":host=" . $config['host'] . ";dbname=" . $config['database'] . ";charset=" . $config['charset'], $config['user'], $config['password']);
            } catch (\PDOException $e) {
                die('Connection error: ' . $e->getMessage());
            }
        }
        return self::$pdo[$connection];
    }
}
