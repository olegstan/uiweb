<?php
namespace App\Controllers\Console;

use App\Layers\LayerConsoleController;
use Framework\Response\ConsoleResponse;

class ConfigController extends LayerConsoleController
{
    public function sheet()
    {
        $res = true;

        $extensions = [
            'curl', 'json', 'gd', 'sockets',
            'openssl', 'fileinfo', 'pdo', 'mbstring',
            'iconv', 'mcrypt', 'redis'
        ];

        foreach ($extensions as $extension) {
            if(!$this->checkExtension($extension)){
                $res = false;
            }
        }

        if(!$this->checkVersion()){
            $res = false;
        }

        if(!$this->checkLocale()){
            $res = false;
        }

        return new ConsoleResponse($res ? 'All works' : 'require extensions', $res ? 'green' : 'red', $res ? 'black' : 'black');
    }

    public function checkVersion()
    {
        if(version_compare(PHP_VERSION, '5.5.0') >= 0){
            $this->info('version ok');
            return true;
        }else{
            $this->error('required version > 5.5.0');
            return false;
        }
    }

    public function checkExtension($name)
    {
        if(extension_loaded($name)){
            $this->info($name . ' installed');
            return true;
        }else{
            $this->error($name . ' not installed');
            return false;
        }
    }

    public function checkLocale()
    {
        $set_locale_res = setlocale(LC_ALL, 'ru_RU' . '.UTF8');
        if($set_locale_res !== false){
            $this->info('locale ' . 'ru_RU' . '.UTF8');
            return true;
        }else{
            $this->error('not found locale ' . 'ru_RU' . '.UTF8');
            return false;
        }
    }
}