<?php

use DagaSmart\Access\Http\Controllers;
use DagaSmart\Access\Http\Middleware\CheckPackageMiddleware;
use Illuminate\Support\Facades\Route;
use Illuminate\Routing\Router;
use DagaSmart\BizAdmin\Middleware\Permission;
use DagaSmart\BizAdmin\Middleware\Authenticate;


//Route::get('access', [Controllers\AccessController::class, 'index']);

//免登录无限制
//Route::get('access', [Controllers\AccessController::class, 'index'])->withoutMiddleware([Authenticate::class, Permission::class]);


Route::group([
    'prefix' => 'biz',
    'middleware' => [CheckPackageMiddleware::class],
], function (Router $router) {
    $router->resource('access/device', Controllers\AccessDeviceController::class);
    $router->resource('access/log', Controllers\AccessLogController::class);
});
