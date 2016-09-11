<?php
namespace Uiweb\Generator;

use Uiweb\Text\Inflector;

class Generator
{
    public function __construct($type, array $data = [])
    {
        $this->type = $type;
        $this->data = $data;
    }

    public function generate()
    {
        if (!empty($this->getData())) {
            extract($this->getData());
        }

        if(file_exists($this->getTemplateFilePath())){
            $template = require_once($this->getTemplateFilePath());
        }else{
            echo 'no template';
        }

        if(!file_exists($this->getFilePath())){
            file_put_contents($this->getFilePath(), $template);
        }else{
            echo 'file exists ' . $this->getFilePath();
        }
    }

    public function getTemplateFilePath()
    {
        $path = ABS . '/Uiweb/Generator/Templates/';

        $file = '';
        switch($this->getType()){
            case 'migration':
                $file = 'Migration.php';
                break;
            case 'seed':
                $file = 'Seed.php';
                break;
            case 'model':
                $file = 'Model.php';
                break;
            case 'controller':
                $file = 'Controller.php';
                break;
        }

        return $path . $file;
    }

    public function getFilePath()
    {
        switch($this->getType()){
            case 'migration':
                return ABS . '/database/migrations/' . Inflector::camelize($this->getData()['name']) . '.php';
            case 'seed':
                return ABS . '/database/seeds/' . Inflector::camelize($this->getData()['name']) . '.php';
            case 'model':

            case 'controller':
        }
    }

    public function getType()
    {
        return $this->type;
    }

    public function getData()
    {
        return $this->data;
    }
}