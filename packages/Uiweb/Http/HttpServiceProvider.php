<?php
namespace Uiweb\Http;

use Uiweb\Auth\AuthServiceProvider;
use Uiweb\FIleSystem\FileSystemServiceProvider;
use Uiweb\Route\RouteServiceProvider;
use Uiweb\ServiceProvider;

class HttpServiceProvider extends ServiceProvider
{
    public function getDepencies()
    {
        return [
            FileSystemServiceProvider::class,
            RouteServiceProvider::class,
            AuthServiceProvider::class
        ];
    }

    public function boot()
    {

    }

    public function register()
    {

    }
}