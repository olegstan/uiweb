<?php
namespace core\helper;

use core\Core;

class Redirect
{
    public function redirect($url = '', $status = 200)
    {
        header('HTTP/1.1 ' . $status . ' ' . $this->statuses[$status]);
        header('Location:' . $this->getCore()->root . $url);
        die();
    }

    public function auth()
    {
        header('Location:' . $this->getCore()->root . '/user/login/');
        die();
    }
}