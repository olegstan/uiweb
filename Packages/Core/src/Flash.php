<?php
namespace core\helper;

class Flash
{
    protected $core;

    public $session;
    public $flash_key = 'messages';

    public function __construct()
    {
        $this->session =& $_SESSION;

        if(!isset($this->session[$this->flash_key])){
            $this->session[$this->flash_key] = [];
        }
    }

    public function set($key, $value)
    {
        $this->session[$this->flash_key][$key] = $value;
    }

    /**
     * показывается только один раз, а потом удаляется
     */

    public function get($key)
    {
        if(isset($this->session[$this->flash_key][$key])){
            $message = $this->session[$this->flash_key][$key];
            unset($this->session[$this->flash_key][$key]);
            //session_unregister('variableName');
            return $message;
        }
    }

    public function exist($key)
    {
        return isset($this->session[$this->flash_key][$key]) ? true : false;
    }

    public function getLast()
    {
        if(isset($this->session[$this->flash_key]) && count($this->session[$this->flash_key] > 0)){
            $key = key($this->session[$this->flash_key]);
            $message = $this->session[$this->flash_key][$key];
            unset($this->session[$this->flash_key][$key]);
            return $message;
        }else{
            return;
        }
    }
}