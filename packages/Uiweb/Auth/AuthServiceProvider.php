<?php
namespace Uiweb\Auth;

use Uiweb\Route\Route;
use Uiweb\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function getDepencies()
    {
        return [
            
        ];
    }

    public function boot()
    {

    }

    public function register()
    {
        
        Route::group('user/', [], function(){
            Route::get('login', 'get.login', \App\Controllers\Http\UserController::class, 'login');
            Route::get('register', 'get.register', \App\Controllers\Http\UserController::class, 'register');
            Route::get('new-password', 'get.new.password', \App\Controllers\Http\UserController::class, 'newPassword');
            Route::get('forgot-password', 'get.forgot.password', \App\Controllers\Http\UserController::class, 'forgotPassword');
            Route::get('logout', 'get.logout', \App\Controllers\Http\UserController::class, 'logout');
        });
    }
}