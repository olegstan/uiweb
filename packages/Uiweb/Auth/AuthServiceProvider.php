<?php
namespace Uiweb\Auth;

use Uiweb\Http\HttpServiceProvider;
use Uiweb\Route\Route;
use Uiweb\ServiceProvider;
use Uiweb\Auth\Http\Controllers\UserController;

class AuthServiceProvider extends ServiceProvider
{
    public function getDepencies()
    {
        return [
            HttpServiceProvider::class
        ];
    }

    public function boot()
    {

    }

    public function register()
    {
        Route::group('user/', [], function(){
            Route::get('login', 'get.login', UserController::class, 'login');
            Route::get('register', 'get.register', UserController::class, 'register');
            Route::get('new-password', 'get.new.password', UserController::class, 'newPassword');
            Route::get('forgot-password', 'get.forgot.password', UserController::class, 'forgotPassword');
            Route::get('logout', 'get.logout', UserController::class, 'logout');
        });
    }
}