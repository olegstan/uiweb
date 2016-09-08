<?php
use Framework\Route\Route;

//main
Route::get('', 'main', \App\Controllers\Http\IndexController::class, 'index');
Route::post('', 'main2', \App\Controllers\Http\IndexController::class, 'index');

Route::get('assets/img/resized/{folder}/{file_name}', 'default_images', \App\Controllers\Http\FileController::class, 'images')->with(['file_name' => '[A-Za-z0-9\_\-\.]+']);
Route::get('assets/img/resized/{folder}/{product}/{file_name}', 'images', \App\Controllers\Http\FileController::class, 'images')->with(['file_name' => '[A-Za-z0-9\_\-\.]+']);

//auth user

Route::group('user/', [], function(){
    Route::get('login', 'login', \App\Controllers\Http\UserController::class, 'login');
    Route::get('register', 'register', \App\Controllers\Http\UserController::class, 'register');
    Route::get('new-password', 'new_password', \App\Controllers\Http\UserController::class, 'newPassword');
    Route::get('forgot-password', 'forgot_password', \App\Controllers\Http\UserController::class, 'forgotPassword');
    Route::get('logout', 'logout', \App\Controllers\Http\UserController::class, 'logout');
});


Route::get('about', 'about', \App\Controllers\Http\IndexController::class, 'about');

Route::group('catalog/', [], function(){
    Route::get('', 'catalog', \App\Controllers\Http\CatalogController::class, 'catalog');
    Route::get('search', 'catalog_seaech', \App\Controllers\Http\CatalogController::class, 'search');
    Route::get('{category_url}/', 'catalog_category', \App\Controllers\Http\CatalogController::class, 'category');//->with(['category_url' => '']);
    Route::get('{category_url}/{product_url}', 'catalog_product', \App\Controllers\Http\CatalogController::class, 'product');
});

Route::group('ajax/', [], function(){
    Route::get('bar/open', 'bar_open', \App\Controllers\Http\Ajax\BarController::class, 'open');
    Route::get('bar/close', 'bar_close', \App\Controllers\Http\Ajax\BarController::class, 'close');

    Route::group('cart/', [], function(){
        //cart
        Route::get('get/{id}', 'ajax_cart_get', \App\Controllers\Http\Ajax\CartController::class, 'get');//->with(['id' => '']);
        Route::get('add/{id}', 'ajax_cart_add', \App\Controllers\Http\Ajax\CartController::class, 'add');
        Route::get('delete/{id}', 'ajax_cart_delete', \App\Controllers\Http\Ajax\CartController::class, 'delete');
        Route::get('change/{id}/{count}', 'ajax_cart_change', \App\Controllers\Http\Ajax\CartController::class, 'change');
        Route::get('clear/', 'ajax_cart_clear', \App\Controllers\Http\Ajax\CartController::class, 'clear');
    });

    Route::get('product/active/{id}', 'product_active', \App\Controllers\Http\Ajax\ProductController::class, 'setIsActive');
});

//admin
Route::group('admin/', [], function(){

    //products
    Route::get('product', 'product_admin_index', \App\Controllers\Http\Admin\ProductControlloer::class, 'index');

    //import
    Route::get('import', 'import_get', \App\Controllers\Http\Admin\ImportController::class, 'getImport');
    Route::post('import', 'import_post', \App\Controllers\Http\Admin\ImportController::class, 'postImport');

    //delete
    Route::get('parse/insat/prices', 'parse_insat_prices', \App\Controllers\Http\Admin\ParseController::class, 'getParseInsatPrices');
    Route::get('parse/ipc2u/prices', 'parse_ipc2u_prices', \App\Controllers\Http\Admin\ParseController::class, 'getParseIpc2uPrices');
    Route::get('parse/ipc2u/images', 'parse_ipc2u_images', \App\Controllers\Http\Admin\ParseController::class, 'getParseIpc2uImages');
    Route::get('parse/ipc2u/products', 'parse_ipc2u_products', \App\Controllers\Http\Admin\ParseController::class, 'getParseIpc2uProducts');
});

Route::group('account/', [], function(){
    Route::get('cart/', 'cart', \App\Controllers\Http\AccountController::class, 'cart');
    Route::get('info/', 'info', \App\Controllers\Http\AccountController::class, 'info');
});



//Route::group('help/', [], function(){
//    Route::group('help', [], function(){
//        Route::get('', 'asd8', 'App\Controllers\IndexController', 'index');
//    });
//});
//
//Route::get('', 'asd1', App\Controllers\Http\IndexController::class, 'index', [App\Middlewares\Authenticate::class]);
//Route::get('asdf/sdfg', 'asd2', 'App\Controllers\IndexController', 'index')->with(['text' => '[0-9]+']);
//Route::get('asd', 'asd3', 'App\Controllers\Index1Controller', 'index')->with(['text' => '[0-9]+']);
//Route::get('asd/asd', 'asd4', 'App\Controllers\Index3Controller', 'index')->with(['text' => '[0-9]+']);
//Route::get('asd123', 'asd5', 'App\Controllers\Index2Controller', 'index')->with(['text' => '[0-9]+']);
//Route::get('asd123/{text}/{id}/{rara}', 'asd6', 'App\Controllers\IndexController', 'index')->with(['text' => '[0-9]+']);
//Route::get('asd/{asd}', 'asd7', App\Controllers\Http\IndexController::class, 'index')->with(['asd' => '[0-9]+']);
//Route::rest('user', 'model', 'App\Controllers\Index4Controller', 'model');
