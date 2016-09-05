<?php
namespace Framework\Auth;

use Framework\Pattern\PatternTraits\NonStaticTrait;
use Framework\Pattern\PatternTraits\SingletonTrait;

/**
 * Class Flash
 * @package Framework\Auth
 * TODO
 */
class Flash
{
    use SingletonTrait, NonStaticTrait;

    public $key = 'messages';



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

}