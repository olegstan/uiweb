<?php
namespace Framework\Db;

use Framework\Model\Collection;
use Framework\Model\Model;
use PDO;

/**
 * Class ModelQueryBuilder
 * @package Framework\Model
 * TODO regenerate phpdoc
 */
class ModelQueryBuilder extends QueryBuilder
{
    /**
     * @var string
     */
    public $class_name;
    /**
     * @var array
     */
    public $fillable = [];
    /**
     * @var array
     */
    public $guarded = [];
    /**
     * @var array
     */
    private $parts = [
        'select' => '',
        'distinct' => '',
        'from' => '',
        'insert' => '',
        'update' => '',
        'where' => '',
        'order' => '',
        'group' => '',
        'limit' => ''
    ];
    /**
     * @var array
     */
    public $fields = [];
    /**
     * @var array
     */
    public $relations = [];
    /**
     * @var array
     */
    public $joins = [];

    /**
     * @param $connection
     * @param $table
     * @param $class_name
     * @param $guarded
     * @param $fillable
     */
    public function __construct($connection, $table, $class_name, $guarded, $fillable)
    {
        $this->table = $table;
        $this->class_name = $class_name;
        $this->fillable = $fillable;
        $this->guarded = $guarded;
        $this->connection = $connection;
    }

    /**
     *
     * //работа с БД
     *
     * //три типа вывода массива
     * //fetch
     * //PDO::FETCH_NUM
     * //PDO::FETCH_ASSOC
     * //PDO::FETCH_BOTH
     *
     * //fetchAll
     * //PDO::FETCH_COLUMN
     * //PDO::FETCH_CLASS
     * //PDO::FETCH_GROUP
     * //PDO::FETCH_UNIQUE
     * //PDO::FETCH_PROPS_LATE
     *
     * //fetchColumn
     *
     * //select по ID по массиву ID или по значению
     *
     **/

    

//
//    private $i_query_start = 0;
//    private $i_query_end = 0;
//    private $i_query_seconds = 0;
//    private static $i_query_time = 0;

    /**
     * @return $this
     */
    public function distinct()
    {
        $this->parts['distinct'] = 'DISTINCT ';
        return $this;
    }

    /**
     * @param array $fields
     * @return $this
     */
    public function select(array $fields = [])
    {
        $this->parts['select'] = 'SELECT ';

        if(!empty($this->parts['distinct'])){
            $this->parts['select'] .= $this->parts['distinct'];
        }

        if($fields){
            $count = count($fields) - 1;

            foreach ($fields as $k => $field) {
                $comma = ($count !== $k) ? ', ' : ' ';
                $this->parts['select'] .=  $field .  $comma . ' ';
            }
        }else{
            $this->parts['select'] .= ' ' . $this->table . '.* ';
        }

        $this->parts['from'] .= 'FROM ' . $this->table . ' ';

        $this->mode = 'select';
        return $this;
    }

    /**
     * @param string $table
     * @return $this
     */
    public function from($table)
    {
        $this->parts['from'] = 'FROM ' . $table . ' ';

        return $this;
    }

    /**
     * @param null $conditions
     * @param array $fields
     * @return $this
     */
    public function where($conditions = null, array $fields = [])
    {
        if($conditions){
            $this->parts['where'] = 'WHERE ';

            $this->parts['where'] .= $conditions;

            foreach ($fields as $k => $v) {
                $this->bind += [$k => $v];
            }
        }
        return $this;
    }

    /**
     * @param $table
     * @param string $condition
     * @return $this
     */
    public function leftJoin($table, $condition = '')
    {
        $this->joins[] = ' LEFT JOIN ' . $table . ($condition ? ' ON ' . $condition . ' ' : ' ');

        return $this;
    }

    /**
     * @param $table
     * @param string $condition
     * @return $this
     */
    public function rightJoin($table, $condition = '')
    {
        $this->joins[] = ' RIGHT JOIN ' . $table . ($condition ? ' ON ' . $condition . ' ' : ' ');

        return $this;
    }

    /**
     * @param $table
     * @param string $condition
     * @return $this
     */
    public function innerJoin($table, $condition = '')
    {
        $this->joins[] = ' INNER JOIN ' . $table . ($condition ? ' ON ' . $condition . ' ' : ' ');

        return $this;
    }

    /**
     * @return $this
     */
    public function delete()
    {
        $this->parts['delete'] = 'DELETE FROM ' . $this->table . ' ';

        $this->mode = 'delete';
        return $this;
    }

    /**
     * @param $data
     * @param bool|false $ignore
     * @return $this
     */
    public function insert($data, $ignore = false)
    {
        $column_names = $this->getColumnNames();

        $values = [];
        if (is_object($data)) {
            $values = get_object_vars($data);
        }else if(is_array($data)){
            $values = $data;
        }

        //проверка есть ли такое поле в таблице
        foreach ($values as $k => $v) {
            if (in_array($k, $column_names) && !in_array($k, $this->guarded) && $k !== 'id') {
                $this->fields[$k] = $v;
            }
        }

        $this->parts['insert'] = 'INSERT ' . ($ignore ? 'IGNORE':'') .' INTO ' . $this->table . ' ';

        $query = [];

        if (is_array($this->fields)) {
            $count = count($this->fields);

            end($this->fields);
            $last_key = key($this->fields);
            $count_for_values = 1;
            if ($count === 1) {
                $i = 1;
                foreach ($this->fields as $k => $v) {
                    $query[$last_key + $i] = '(`' . $k . '`) ';
                    $query[$last_key + $i + $count + $count_for_values] = '(:' . $k . ');';
                    $this->bind += [':' . $k => $v];
                }
            } else {
                $i = 1;
                foreach ($this->fields as $k => $v) {
                    if ($i === 1) {
                        $query[$last_key + $i] = '(`' . $k . '`, ';
                        $query[$last_key + $i + $count + $count_for_values] = '(:' . $k . ', ';
                    } else if ($i === $count) {
                        $query[$last_key + $i] = '`' . $k . '`) ';
                        $query[$last_key + $i + $count + $count_for_values] = ':' . $k . ');';
                    } else {
                        $query[$last_key + $i] = '`' . $k . '`, ';
                        $query[$last_key + $i + $count + $count_for_values] = ':' . $k . ', ';
                    }
                    $this->bind += [':' . $k => $v];
                    $i++;
                }
            }
        }

        $query[$last_key + $count + $count_for_values] = 'VALUES ';

        ksort($query);
        foreach($query as $part){
            $this->parts['insert'] .= $part;
        }

        $this->mode = 'insert';
        return $this;
    }

    /**
     * @param $data
     * @return $this
     */
    public function update($data)
    {
        $column_names = $this->getColumnNames();

        $values = [];
        if (is_object($data)) {
            $values = get_object_vars($data);
        }else if(is_array($data)){
            $values = $data;
        }

        //проверка есть ли такое поле в таблице
        foreach ($values as $k => $v) {
            if (in_array($k, $column_names) && !in_array($k, $this->guarded) && $k !== 'id') {
                $this->fields[$k] = $v;
            }
        }

        $this->parts['update'] = 'UPDATE ' . $this->table . ' ';
        $this->parts['update'] .= 'SET ';

        if (is_array($this->fields)) {

            $count = count($this->fields);

            if ($count === 1) {
                foreach ($this->fields as $k => $v) {
                    $this->parts['update'] .= '`' . $k . '` = :' . $k . ' ';
                    $this->bind += [':' . $k => $v];
                }
            } else {
                $i = 1;
                foreach ($this->fields as $k => $v) {
                    if ($i === 1) {
                        $this->parts['update'] .= '`' . $k . '` = :' . $k . ', ';
                    } else if ($i === $count) {
                        $this->parts['update'] .= '`' . $k . '` = :' . $k . ' ';
                    } else {
                        $this->parts['update'] .= '`' . $k . '` = :' . $k . ', ';
                    }
                    $this->bind += [':' . $k => $v];
                    $i++;
                }
            }
        }

        $this->mode = 'update';
        return $this;
    }

    /**
     * @return $this
     */
    public function execute()
    {
        $query = '';
        switch($this->mode){
            case 'select':
                $query = $this->parts['select'] . $this->parts['from'];

                foreach ($this->joins as $join) {
                    $query .= $join;
                }

                $query .= $this->parts['where'] . $this->parts['order'] . $this->parts['group'] . $this->parts['limit'];
                break;
            case 'insert':
                $query = $this->parts['insert'];
                break;
            case 'update':
                $query .= $this->parts['update'] . $this->parts['where'];
                break;
            case 'delete':
                $query .= $this->parts['delete'] . $this->parts['where'];
                break;
            case 'paginate':
                $query = $this->parts['select'];

                foreach ($this->joins as $join) {
                    $query .= $join;
                }

                $query .= $this->parts['where'] . $this->parts['order'] . $this->parts['group'];
                break;
            default:
                die('Ошибка');
                break;
        }

//        echo $query;
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

    /**
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    //TODO
    public function paginate($rules = null, $key = false, $per_page = 30)
    {
//        $page = $this->getCore()->request->request('page') ? $this->getCore()->request->request('page') : 1;

//        $limit = (($page - 1) * $per_page);
//        $offset = $per_page;
//
//        $this->parts['limit'] = ' LIMIT ' . $limit . ', ' . $offset;
//
//        $this->mode = 'select';
//
//        $collection = $this
//            ->with($this->relations)
//            ->execute()
//            ->all($rules, $key)
//            ->getResult();
//
//        $this->parts['select'] = 'SELECT COUNT(*) AS count FROM ' . $this->table . ' ';
//
//        $this->mode = 'paginate';
//
//        $count = $this
//            ->execute()
//            ->one()
//            ->getResult()
//            ->count;
//
//        $pages = ceil($count / $per_page);
//
//        return ['items' => $collection, 'count' => $count, 'pages' => $pages];
    }

    /**
     * @param $array
     * @return $this
     */
    public function with($array)
    {
        $this->relations = $array;
        return $this;
    }

    /**
     * @param null $rules
     * @return Collection
     */
    public function one($rules = null)
    {
        $class = $this->class_name;

        $objects = $this->stmt->fetchAll(PDO::FETCH_CLASS, $class);
        $collection = new Collection();

        foreach ($objects as $key => $object) {
            /** @var Model $object */
//            $object->query_seconds = $this->i_query_seconds;
            $collection->appendOne($object->afterSelect($rules));
            break;
        }

        foreach($this->relations as $relation){
            $class::$relation($collection, ['type' => 'one', 'rules' => $rules]);
        }

        $this->stmt->closeCursor();

        return $collection;
    }

    /**
     * @param bool|false $key
     * @param null $rules
     * @return Collection
     *
     * либо по порядку, либо по указанному свойству объекта
     */
    public function all($key = false, $rules = null)
    {
        $class = $this->class_name;
        $objects = $this->stmt->fetchAll(PDO::FETCH_CLASS, $class);
        $collection = new Collection();

        $i = 0;
        foreach ($objects as $object) {
            /** @var Model $object */
//            $object->query_seconds = $this->i_query_seconds;
            if($key){
                $collection->appendToArray($object->afterSelect($rules), $object->$key);
            }else{
                $collection->appendToArray($object->afterSelect($rules), $i);
                $i++;
            }
        }

        foreach($this->relations as $relation){
            $class::$relation($collection, ['type' => 'all', 'rules' => $rules]);
        }

        $this->stmt->closeCursor();

        return $collection;
    }

    /**
     * @param $field
     * @param string $sort
     * @return $this
     */
    public function order($field, $sort = 'ASC')
    {
        if(empty($this->parts['order'])){
            $this->parts['order'] = ' ORDER BY ' . $field . ' ' . $sort . ' ';
        }else{
            $this->parts['order'] .= ', ' . $field . ' ' . $sort . ' ';
        }
        return $this;
    }

    /**
     * @param int $start
     * @param int $offest
     * @return $this
     */
    public function limit($start = 0, $offest = 1)
    {
        $this->parts['limit'] = ' LIMIT ' . $start . ', ' . $offest;
        return $this;
    }

    /**
     * @param string $field
     * @return $this
     */
    public function group($field)
    {
        $this->parts['group'] = ' GROUP BY ' . $field;
        return $this;
    }

    /**
     * @return string
     */
    public function getlastInsertId()
    {
        $last_inserted_id = self::getPDO()->lastInsertId();
        return $last_inserted_id;
    }

    //rowCount() количество задетых строк

    /**
     *
     */

    public function dump()
    {
//        $dump_dir = ABS . '/tmp/schema/'; // директория, куда будем сохранять резервную копию БД
//
//        foreach($this->getTables() as $table){
//            $fp = fopen( $dump_dir."/".$table[0].".sql", "a" );
//
//
//
//        }


        /*while( $table = mysql_fetch_row($res) )
        {
            $fp = fopen( $dump_dir."/".$table[0].".sql", "a" );
            if ( $fp )
            {
                $query = "TRUNCATE TABLE `".$table[0]."`;\n";
                fwrite ($fp, $query);
                $rows = 'SELECT * FROM `'.$table[0].'`';
                $r = mysql_query($rows) or die("Ошибка при выполнении запроса: ".mysql_error());
                while( $row = mysql_fetch_row($r) )
                {
                    $query = "";
                    foreach ( $row as $field )
                    {
                        if ( is_null($field) )
                            $field = "NULL";
                        else
                            $field = "'".mysql_escape_string( $field )."'";
                        if ( $query == "" )
                            $query = $field;
                        else
                            $query = $query.', '.$field;
                    }
                    $query = "INSERT INTO `".$table[0]."` VALUES (".$query.");\n";
                    fwrite ($fp, $query);
                }
                fclose ($fp);
            }
        }*/
    }

}