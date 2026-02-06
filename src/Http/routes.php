<?php

use DagaSmart\Access\Http\Controllers;
use DagaSmart\Access\Http\Middleware;
use Illuminate\Support\Facades\Route;
use Illuminate\Routing\Router;
use DagaSmart\BizAdmin\Middleware\Permission;
use DagaSmart\BizAdmin\Middleware\Authenticate;


//Route::get('access', [Controllers\AccessController::class, 'index']);

//免登录无限制
//Route::get('access', [Controllers\AccessController::class, 'index'])->withoutMiddleware([Authenticate::class, Permission::class]);


Route::group([
    'prefix' => 'biz',
    'middleware' => [Middleware\CheckPackageMiddleware::class],
], function (Router $router) {
    $router->get('access/enterprise/{enterprise_id}/facility/options', [Controllers\AccessDeviceController::class, 'options']);
    $router->resource('access/permission', Controllers\AccessPermissionController::class);
    $router->resource('access/device', Controllers\AccessDeviceController::class);
    $router->resource('access/user', Controllers\AccessUserController::class);
    $router->resource('access/log', Controllers\AccessLogController::class);
});
