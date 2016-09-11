<?php
namespace Uiweb\Model;

use Iterator;

class Collection implements Iterator
{
    /**
     * @var int
     */
    private $position = 0;
    /**
     * @var array
     */
    private $array = [];
    /**
     * @var string
     */
    private $json = '';
    /**
     * @var array
     */
    private $items = [];
    /**
     * @var array
     */
    private $map = [];
    /**
     * @var array
     */
    private $tree = [];

    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * @return mixed
     */
    public function current()
    {
        return $this->items[$this->position];
    }

    /**
     * @return int
     */
    public function key()
    {
        return $this->position;
    }

    public function next()
    {
        ++$this->position;
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return isset($this->items[$this->position]);
    }

    public function appendToArray($element, $key = null)
    {
        if(isset($key)){
            $this->items[$key] = $element;
        }else{
            $this->items[] = $element;
        }
    }

    public function appendOne($element)
    {
        $this->items = $element;
    }

    /**
     * @param $field
     * @return array
     */
    public function getField($field)
    {
        if (is_array($this->items)) {
            $fields = [];
            foreach ($this->items as $k => $v) {
                $fields[] = $v->$field;
            }
            $this->result_fields = $fields;
            return $this->result_fields;
        } else {
            return [];
        }
    }

    /**
     * @return array|Model
     */
    public function getResult()
    {
        if(!empty($this->items)){
            return $this->items;
        }else{
            return [];
        }
    }

    /**
     * @return array|Model
     */
    public function get()
    {
        return $this->getResult();
    }

    /**
     * @return $this
     */
    public function toMap()
    {
        foreach($this->items as $item){
            $this->map[$item->id] = $item;
        }
        return $this;
    }

    /**
     * @param $int
     * @return array
     */
    public function toGroups($int)
    {
        $result = [];
        if($this->items){
            $group_count = round(count($this->items) / $int);

            $itter = 0;
            for($i1 = 0; $i1 <= $group_count; $i1++){
                $result[$i1] = [];
                for($i2 = 0; $i2 < $int; $i2++){
                    if(isset($array[$itter])){
                        $result[$i1][] = $array[$itter];
                    }else{
                        break;
                    }
                    $itter++;
                }
            }
        }
        return $result;
    }

    /**
     * @param $field
     * @return $this
     */

    public function toTree($field)
    {
        $this->toMap();

        foreach($this->items as $item){
            if($item->$field == 0){
                $this->tree[] = $item;
            }else{
                $this->map[$item->$field]->nodes[] = $item;
            }
        }
        return $this;
    }

    /**
     * @return array
     */

    public function toArray()
    {
        $array = [];
        foreach($this->items as $k => $item){
            $array[$k] = (array) $item;
        }
        return $array;
    }

    /**
     * @return string
     */
    public function toJson()
    {

    }

    /**
     * @return array
     */
    public function getTree()
    {
        return $this->tree;
    }

    /**
     * @return array
     */
    public function getMap()
    {
        return $this->map;
    }

    public function getJson()
    {
        return $this->json;
    }

    public function getArray()
    {
        return $this->array;
    }
}