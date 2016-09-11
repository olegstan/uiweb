<?php
namespace Uiweb\Model\Types;

use Uiweb\Config;
use Uiweb\Db\ModelQueryBuilder;
use Uiweb\Model\Model;
use Uiweb\Model\Collection;
/**
 * Class DatabaseModel
 * @package Uiweb\Model\Types
 */
class DatabaseModel extends Model
{
    /**
     * @var string
     */
    protected $table;
    /**
     * @var string
     */
    protected $connection;

    /**
     * @param $name
     *
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
        //TODO
        if($this->table){
            return $this->table;
        }else{
            echo 'Not set table ' . static::class;
            die();
            throw new \Exception;
        }
    }

    public function setConnection($name)
    {
        $this->connection = $name;
    }

    public function getConnection()
    {
        return $this->connection ? $this->connection : Config::get('app', 'connection');
    }

    /**
     * @param
     * @return ModelQueryBuilder
     */
    public function getQuery()
    {
        return (new ModelQueryBuilder($this->getConnection(), $this->getTable(), get_class($this), $this->getGuarded(), $this->getFillable()));
    }

    /**
     * @param $id
     * @return Collection|$this
     */

    public function findById($id)
    {
        if (is_array($id)) {
            return $this->getQuery()->select()->where('`' . $this->table . '`.`id` IN (' . implode(',', $id) . ')')->execute()->all();
        } else {
            return $this->getQuery()->select()->where('`' . $this->table . '`.`id` = :id', [':id' => $id])->limit()->execute()->one();
        }
    }

    public function findByField($field, $value)
    {
//        $this->getQuery()->select()
        return $this->getQuery()->select()->where('`' . $this->table . '`.`' . $field . '` = :' . $field, [':' . $field => $value])->execute();
    }

    public function findAll()
    {
        return $this->getQuery()->select()->execute()->all();
    }

    public function insert($ignore = false)
    {
        $this->validate();

        $this->beforeInsert();

        $this->fields = $this->getQuery()->insert($this, $ignore)->execute()->getFields();

        $this->fields['id'] = $this->id = $this->getQuery()->getlastInsertId();

        $this->afterInsert();

        return $this->id;
//        return ['result' => 'success', 'fields' => $this->fields];
    }

    /**
     * @return array
     */

    public function update()
    {
        $this->validate();

        $this->beforeUpdate();

        $this->fields = $this->getQuery()->update($this)->where('id = :id', [':id' => $this->id])->execute()->getFields();

        $this->afterUpdate();

//        $this->fields['id'] = $this->id;

//        return ['result' => 'success', 'fields' => $this->fields];
        return $this->id;
    }

    /**
     * @param $id
     */

    public function delete()
    {
        $this->beforeDelete();

        $this->query()->delete()->where('id = :id', [':id' => $this->id])->execute();

        $this->fields['id'] = $this->id;

        $this->afterDelete();

        return ['result' => 'success', 'fields' => $this->fields];
    }
}