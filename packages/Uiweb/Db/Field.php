<?php
namespace Uiweb\Db;
/**
 * Class Field
 * @package Uiweb\Schema
 */
class Field
{
    /**
     * @var string
     */
    public $name;
    /**
     * @var string
     */
    public $type;
    /**
     * @var int
     */
    public $length = 0;
    /**
     * @var bool
     */
    public $nullable = false;
    /**
     * @var string
     */
    public $default = '';
    /**
     * @var string
     */
    public $collate = 'utf8_general_ci';
    /**
     * @var bool
     */
    public $increment = false;
    /**
     * @var string
     */
    public $attribute = '';
    /**
     * @var string
     */
    public $index = '';
    /**
     * @var string
     */
    public $comment = '';
    /**
     * @var string
     */
    public $after = '';
    /**
     * @var string
     */
    public $action = '';

    /**
     * @param $name
     * @param $type
     * @param int $length
     *
     * 0 if field not need
     *
     */
    public function __construct($name, $type, $length = 0, $action = 'add')
    {
        $this->name = $name;
        $this->type = $type;
        $this->length = $length;
        $this->action = $action;
    }
    /**
     * @return string
     */
    public function getQuery()
    {
        $query = $this->name . ' ' . $this->type . ($this->length ? '(' . $this->length . ') ' : ' ');

        if($this->attribute){
            $query .= $this->attribute . ' ';
        }

        if($this->increment){
            $query .= 'AUTO_INCREMENT ';
        }

        if($this->nullable){
            $query .= 'NULL ';
        }else{
            $query .= 'NOT NULL ';
        }
        if(isset($this->default) && (!empty($this->default) || $this->default === 0)){
            $query .= 'DEFAULT \'' . $this->default . '\' ';
        }

        if($this->index){
            $query .= $this->index . ' ';
        }

        if($this->comment){
            $query .= 'COMMENT \'' . $this->comment . '\' ';
        }

        return $query;
    }

    public function getQueryAdd()
    {
        return 'ADD ' . $this->getQuery() . ' ' . ($this->after ? 'AFTER `' . $this->after . '`' : '');
    }

    public function getQueryDrop()
    {
        return 'DROP `' . $this->name . '`';
    }
}