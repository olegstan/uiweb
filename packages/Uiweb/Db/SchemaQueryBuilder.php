<?php
namespace Framework\Db;

class SchemaQueryBuilder extends QueryBuilder
{
    /**
     * @var string
     */
    public $engine;
    /**
     * @var string
     */
    public $charset;
    /**
     * @var int
     */
    public $increment;

    /**
     * @var array
     */
    public $part = [
        'create' => '',
        'update' => '',
        'drop' => '',
        'truncate' => '',
        'exists' => '',
    ];

    /**
     * @param $connection
     * @param $table
     */
    public function __construct($connection, $table, $engine, $charset, $increment)
    {
        $this->connection = $connection;
        $this->table = $table;
        $this->engine = $engine;
        $this->charset = $charset;
        $this->increment = $increment;
    }

    public function exists()
    {
        $this->stmt = self::getPDO()->prepare('SHOW TABLES LIKE \'' . $this->table . '\'');
        $this->stmt->execute();

        $this->mode = 'exists';
        return $this->stmt->fetchAll() ? true : false;
    }

    public function create($fields)
    {
        $this->part['create'] = 'CREATE TABLE ' . $this->table . ' (';

        $count = count($fields) - 1;
        foreach ($fields as $k => $field) {
            $comma = ($count != $k) ? ', ' : ' ';
            /** @var Field $field */
            $this->part['create'] .= $field->getQuery() . $comma;
        }

        $this->part['create'] .= ')';// TODO ENGINE=InnoDB AUTO_INCREMENT=236 DEFAULT CHARSET=utf8

        $this->mode = 'create';
        return $this;
    }

    public function update($fields)
    {
        $this->part['update'] = 'ALTER TABLE ' . $this->table . ' ';

        $count = count($fields) - 1;
        foreach ($fields as $k => $field) {
            $comma = ($count != $k) ? ', ' : ' ';
            /** @var Field $field */
            switch($field->action){
                case 'add':
                    $this->part['update'] .= $field->getQueryAdd() . $comma;
                    break;
                case 'drop':
                    $this->part['update'] .= $field->getQueryDrop() .  $comma;
                    break;
            }
        }

        $this->mode = 'update';
        return $this;
    }

    public function drop()
    {
        $this->part['drop'] = 'DROP TABLE ' . $this->table;

        $this->mode = 'drop';
        return $this;
    }

    public function truncate()
    {
        $this->part['truncate'] = 'TRUNCATE TABLE ' . $this->table;

        $this->mode = 'truncate';
        return $this;
    }

    /**
     * @return $this
     */
    public function execute()
    {
        $query = '';

        switch($this->mode){
            case 'create':
                $query .= $this->part['create'];
                break;
            case 'update':
                $query .= $this->part['update'];
                break;
            case 'exists':
                $query .= $this->part['exists'];
                break;
            case 'drop':
                $query .= $this->part['drop'];
                break;
            case 'truncate':
                $query .= $this->part['truncate'];
                break;
            default:
                die('Ошибка');
                break;
        }

//        $this->getCore()->debug->addQuery($query);
        $this->stmt = self::getPDO()->prepare($query);

        //ловим ошибки
//        $this->i_query_start = microtime(true);

        if (!$this->stmt->execute($this->bind)) {
            $error = $this->stmt->errorInfo();

            if (isset($error[1])) {
                // 1054 - Unknown column
                // 1064 - Syntax error
                // 1062 - Duplicate entry
                // 1048 - mot Null
                // 1046 - table does not exist
                // 1265 - Data truncated
                //if ($error[1] == 1062 || $error[1] ==  1048 || $error[1] ==  1265){

                echo $query  . '<br>';
                echo $error[2]  . '<br>';
                die();
                //$error_action = '500';
                //die((new ErrorController())->$error_action('Ошибка запроса к БД ' . $error[2] . '<br>' . $query));
            }
        }

//        $this->i_query_end = microtime(true);
//        $this->i_query_seconds = number_format(($this->i_query_end - $this->i_query_start), 5);
//
//        $this->getCore()->debug->addQuery($this->i_query_seconds . ' seconds');
        //$this->getCore()->debug->addQuery($this->i_query_seconds . ' time');

        return $this;
    }
}