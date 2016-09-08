<?php
namespace Framework\Db;

use Framework\Config;

class Table
{
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
    public $fields = [];
    /**
     * @var string
     */
    public $engine = 'InnoDB';
    /**
     * @var int
     */
    public $increment = 0;
    /**
     * @var string
     */
    public $charset = 'utf8';

    public function __construct($name)
    {
        $this->table = $name;
    }

    public function getQuery()
    {
        return new SchemaQueryBuilder($this->getConnection(), $this->getTable(), $this->getEngine(), $this->getCharset(), $this->getIncrement());
    }

    /**
     * @param $name
     * @return void
     */
    public function setTable($name)
    {
        $this->table = $name;
    }

    /**
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    public function setConnection($name)
    {
        $this->connection = $name;
    }

    public function getConnection()
    {
        return $this->connection ? $this->connection : Config::get('app', 'connection');
    }

    public function getEngine()
    {
        return $this->engine;
    }

    public function setEngine($engine)
    {
        $this->engine = $engine;
    }

    public function getCharset()
    {
        return $this->charset;
    }

    public function setCharset($charset)
    {
        $this->charset = $charset;
    }

    public function getIncrement()
    {
        return $this->increment;
    }

    public function setIncrement($int)
    {
        $this->increment = $int;
    }

    public function create()
    {
        return $this->getQuery()->create($this->fields)->execute();
    }

    public function update()
    {
        return $this->getQuery()->update($this->fields)->execute();
    }

    public function drop()
    {
        return $this->getQuery()->drop()->execute();
    }

    public function truncate()
    {
        return $this->getQuery()->truncate()->execute();
    }

    public function exists()
    {
        return $this->getQuery()->exists();
    }

    public function addField($name, $type, $length = 0)
    {
        $this->fields[] = new Field($name, $type, $length, $action = 'add');
    }


    public function dropField($name)
    {
        $this->fields[] = new Field($name, '', '', $action = 'drop');
    }

    /**
     * @return Field
     */
    public function getLast()
    {
        return end($this->fields);
    }

//    public function setAttribute($key, $value)
//    {
//        $last = $this->getLast();
//        if(isset($last)){
//            $last['attributes'][$key] = $value;
//        }
//    }

    /**
     * @return $this
     */
    public function setAutoIncrement()
    {
        $last = $this->getLast();
        if(isset($last)){
            $last->increment = true;
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function setAfter($name)
    {
        $last = $this->getLast();
        if(isset($last)){
            $last->after = $name;
        }
        return $this;
    }
    /**
     * @return $this
     */
    public function setUnsigned()
    {
        $last = $this->getLast();
        if(isset($last)){
            $last->attribute = 'UNSIGNED';
        }
        return $this;
    }

    public function setComment($text)
    {
        $last = $this->getLast();
        if(isset($last)){
            $last->comment = $text;
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function setNullable($value = true)
    {
        $last = $this->getLast();
        if(isset($last)){
            $last->nullable = $value;
        }
        return $this;
    }
    /**
     * @return $this
     */
    public function setDefault($value = '')
    {
        $last = $this->getLast();
        if(isset($last)){
            $last->default = $value;
        }
        return $this;
    }

    public function setPrimaryKey()
    {
        $last = $this->getLast();
        if(isset($last)){
            $last->index = 'PRIMARY KEY';
        }
        return $this;
    }

    public function setUnique()
    {
        $last = $this->getLast();
        if(isset($last)){
            $last->index = 'UNIQUE';
        }
        return $this;
    }


    /**
     * @param $name
     * @param int $length
     * @return $this
     */
    public function integer($name, $length = 11)
    {
        $this->addField($name, 'INT', $length);
        return $this;
    }

    /**
     * @param $name
     * @param int $length
     * @return $this
     *
     * synonym integer
     */
    public function int($name, $length = 11)
    {
        $this->addField($name, 'INT', $length);
        return $this;
    }

    /**
     * @param $name
     * @param int $length
     * @return $this
     *
     * TODO
     */
    public function numeric($name, $integer = 10, $after_comma = 0)
    {
        $this->addField($name, 'NUMERIC', $integer . ',' . $after_comma);
        return $this;
    }

    /**
     * @param $name
     * @param int $length
     * @return $this
     */
    public function tinyint($name, $length = 4)
    {
        $this->addField($name, 'TINYINT', $length);
        return $this;
    }

    /**
     * @param $name
     * @param int $length
     * @return $this
     */
    public function smallint($name, $length = 6)
    {
        $this->addField($name, 'SMALLINT', $length);
        return $this;
    }

    /**
     * @param $name
     * @param int $length
     * @return $this
     */
    public function mediumint($name, $length = 9)
    {
        $this->addField($name, 'MEDIUMINT', $length);
        return $this;
    }

    /**
     * @param $name
     * @param int $length
     * @return $this
     */
    public function bigint($name, $length = 20)
    {
        $this->addField($name, 'BIGINT', $length);
        return $this;
    }

    /**
     * @param $name
     * @return $this
     *
     * synonym bigint
     */
    public function serial($name)
    {
        $this->addField($name, 'SERIAL');
        return $this;
    }


    /**
     * @param $name
     * @return $this
     */
    public function float($name)
    {
        $this->addField($name, 'FLOAT');
        return $this;
    }

    //TODO
    public function double($name)
    {
        $this->addField($name, 'DOUBLE');
        return $this;
    }

    //TODO synonym double
    public function real($name)
    {
        $this->addField($name, 'DOUBLE');
        return $this;
    }

    /**
     * @param $name
     * @return $this
     */
    public function bit($name)
    {
        $this->addField($name, 'BIT');
        return $this;
    }

    /**
     * @param $name
     * @param float $length
     * @return $this
     */
    public function decimal($name, $integer = 10, $after_comma = 0)
    {
        $this->addField($name, 'DECIMAL', $integer . ',' . $after_comma);
        return $this;
    }

    /**
     * @param $name
     * @return $this
     *
     * synonym decimal
     */
    public function dec($name)
    {
        $this->addField($name, 'DECIMAL');
        return $this;
    }

    /**
     * @param $name
     * @return $this
     */
    public function date($name)
    {
        $this->addField($name, 'DATE');
        return $this;
    }

    /**
     * @param $name
     * @return $this
     */
    public function datetime($name)
    {
        $this->addField($name, 'DATETIME');
        return $this;
    }

    /**
     * @param $name
     * @return $this
     */
    public function timestamp($name)
    {
        $this->addField($name, 'TIMESTAMP');
        return $this;
    }

    /**
     * @param $name
     * @return $this
     */
    public function time($name)
    {
        $this->addField($name, 'TIME');
        return $this;
    }

    /**
     * @param $name
     * @return $this
     */
    public function year($name)
    {
        $this->addField($name, 'YEAR');
        return $this;
    }

    /**
     * @param $name
     * @param int $length
     * @return $this
     */
    public function char($name, $length = 20)
    {
        $this->addField($name, 'CHAR', $length);
        return $this;
    }

    /**
     * @param $name
     * @return $this
     */
    public function binary($name)
    {
        $this->addField($name, 'BINARY');
        return $this;
    }

    /**
     * @param $name
     * @param int $length
     * @return $this
     */
    public function varchar($name, $length = 255)
    {
        $this->addField($name, 'VARCHAR', $length);
        return $this;
    }

    /**
     * @param $name
     * @return $this
     */
    public function varbinary($name)
    {
        $this->addField($name, 'VARBINARY');
        return $this;
    }

    /**
     * @param $name
     * @return $this
     */
    public function blob($name)
    {
        $this->addField($name, 'BLOB');
        return $this;
    }

    /**
     * @param $name
     * @return $this
     */
    public function text($name)
    {
        $this->addField($name, 'TEXT');
        return $this;
    }

    /**
     * @param $name
     * @return $this
     */
    public function tinyblob($name)
    {
        $this->addField($name, 'TINYBLOB');
        return $this;
    }

    /**
     * @param $name
     * @return $this
     */
    public function tinytext($name)
    {
        $this->addField($name, 'TINYTEXT');
        return $this;
    }

    /**
     * @param $name
     * @return $this
     */
    public function mediumblob($name)
    {
        $this->addField($name, 'MEDIUMBLOB');
        return $this;
    }

    /**
     * @param $name
     * @return $this
     */
    public function mediumtext($name)
    {
        $this->addField($name, 'MEDIUMTEXT');
        return $this;
    }

    /**
     * @param $name
     * @return $this
     */
    public function longblob($name)
    {
        $this->addField($name, 'LONGBLOB');
        return $this;
    }

    /**
     * @param $name
     * @return $this
     */
    public function longtext($name)
    {
        $this->addField($name, 'LONGTEXT');
        return $this;
    }

    //TODO ENUM('0','1','2','')
    public function enum($name)
    {
        $this->addField($name, 'ENUM');
        return $this;
    }

    //TODO SET('0','1','2','')
    public function set($name)
    {
        $this->addField($name, 'SET');
        return $this;
    }

}