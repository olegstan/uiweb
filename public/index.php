<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
//error_reporting( E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_ERROR | E_WARNING | E_PARSE | E_USER_ERROR | E_USER_WARNING | E_RECOVERABLE_ERROR );

//ABS /корень
define('ABS', dirname(__FILE__).'/..');

////автолоадер composer
//require_once(ABS . '//libraries/autoload.php');

//запускаем автолоадер для подгрузки классов на лету
require_once(ABS . '/Framework/Autoload/Autoloader.php');

require_once(ABS . '/Framework/Debug/Shutdown.php');

//composer autoload
require_once(ABS . '/vendor/autoload.php');

require_once(ABS . '/config/defines.php');

use Framework\Controller\FrontController;
use Framework\Request\Request;
use Framework\Route\RouteCollection;

return (new FrontController(Request::getInstance(), RouteCollection::getInstance()))->init();
