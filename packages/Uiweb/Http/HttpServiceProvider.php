<?php
namespace Uiweb\Http;

use Uiweb\Http\Route\RouteServiceProvider;
use Uiweb\ServiceProvider;

class HttpServiceProvider extends ServiceProvider
{
    public function getDepencies()
    {
        return [
            RouteServiceProvider::class
        ];
    }

    public function boot()
    {

    }

    public function register()
    {
        
    }
}