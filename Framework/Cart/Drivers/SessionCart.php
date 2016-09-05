<?php
namespace Framework\Cart\Drivers;

use Framework\Auth\Session;

/**
 * Class SessionCart
 * @package Framework\Cart\Drivers
 */
class SessionCart
{
    /**
     * @var string
     */
    public $key = 'cart';

    /**
     * @param integer $id
     */
    public function get($id)
    {
        return Session::multiGet($this->key, $id);
    }

    /**
     *
     */
    public function getAll()
    {
        return Session::get($this->key);
    }

    /**
     * @param integer $id
     */
    public function add($id)
    {
        if($this->has($id)){
            Session::multiSet($this->key, $id, $this->get($id) + 1);
        }else{
            Session::multiSet($this->key, $id, 1);
        }
    }

    /**
     * @param integer $id
     */
    public function has($id)
    {
        return Session::multiHas($this->key, $id);
    }

    /**
     * @param integer $id
     */
    public function delete($id)
    {
        Session::multiDelete($this->key, $id);
    }
    /**
     * @param integer $id
     * @param integer $count
     */
    public function change($id, $count)
    {
        Session::multiSet($this->key, $id, $count);
    }

    public function clear()
    {
        Session::delete($this->key);
    }
}