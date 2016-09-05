<?php
namespace core;

class Settings
{
    private $vars = [];

    public function init()
    {
        $settings = (new \app\models\Settings())
            ->query()
            ->select()
            ->execute()
            ->all()
            ->getResult();

        if($settings){
            foreach ($settings as $setting) {
                $this->vars[$setting->name] = $setting->value;
            }
        }

        return $this;
    }

    public function __construct()
    {

    }

    public function __get($name)
    {
        if(isset($this->vars[$name])) {
            return $this->vars[$name];
        }else{
            return null;
        }
    }

    public function __set($name, $value)
    {
        $setting = (new \app\models\Settings())
            ->query()
            ->select()
            ->where('name = :name', [':name' => $name])
            ->limit()
            ->execute()
            ->one()
            ->getResult();

        if($setting){
            $setting->value = $value;
            $this->vars[$name] = $value;
            return true;
        }else{
            return false;
        }
    }

    private function __clone()
    {

    }

    private function __wakeup()
    {

    }
}