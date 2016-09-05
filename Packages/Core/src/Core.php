<?php
namespace Framework\Core;

use Framework\Config;
use Framework\Pattern\PatternTraits\SingletonTrait;

class Core
{
    use SingletonTrait;


//    public $classes = [
//        'auth' => ''
//    ];

    public $pool = [];


    public function __construct()
    {

    }



    public function __handle()
    {
//        $this->load();

//        $this->auth = new Auth();
//
//        $this->flash = new Flash();
//
//        $this->asset = new AssetBundle();
//
//        $this->request = new Request();
//
//        $this->redirect = new Redirect();
//
//        $this->settings = (new Settings());

//        //$this->cart = new Cart();
//
//
//
//
//        //$this->ip =
//
//
//
//        return $this;
    }

    public function load()
    {
        foreach (Config::get('core', 'boot') as $class) {
            $this->pool[] = $class::getInstance();
        }
    }

}