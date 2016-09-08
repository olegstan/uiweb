<?php
namespace Framework\Db;

use Framework\Db\SqlConnectors\MysqlDbConnect;
use PDO;
use PDOStatement;

class QueryBuilder
{
    /**
     * @var PDOStatement
     */
    public $stmt;
    /**
     * @var string
     */
    public $table;
    /**
     * @var string
     */
    public $connection;
    /**
     * @var array
     */
    public $bind = [];
    /**
     * @var string
     */
    public $mode;
    /**
     * @param $query
     * @return $this
     */
    public function sql($query)
    {
        $this->query = $query;
        return $this;
    }

    /**
     * @return PDO
     */
    public function getPDO()
    {
        return MysqlDbConnect::getPDO($this->getConnection());
    }
    /**
     * @return string
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @return array
     */
    public function getColumnNames()
    {
        /**
         * написать проерку если вернулась пустой массив
         */

        $this->stmt = self::getPDO()->prepare('SHOW COLUMNS FROM `' . $this->table . '`');
        $this->stmt->execute();

        $field_names = [];
        foreach ($this->stmt->fetchAll() as $field) {
            //TODO Field
            $field_names[] = $field['Field'];
        }
        return $field_names;
    }
    /**
     * @return array
     */
    public function getTables()
    {
        $this->stmt = self::getPDO()->prepare('SHOW TABLES')->execute();

        $tables = [];
        foreach ($this->stmt->fetchAll() as $table) {
            $tables[] = $table[0];
        }
        return $tables;
    }
    /**
     * @return $this
     */
    public function execute()
    {

    }
}