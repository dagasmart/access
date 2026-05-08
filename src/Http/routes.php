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
    $router->get('access/enterprise/{enterprise_id}/facility/{facility_id}/device/all', [Controllers\AccessDeviceController::class, 'deviceAll']);
    $router->get('access/enterprise/{enterprise_id}/department/{department_id}/user/{user_type}/all', [Controllers\AccessUserController::class, 'userAll']);
    $router->get('access/enterprise/{enterprise_id}/grade/{grade_id}/classes/{classes_id}/user/{user_type}/is_boarder/{is_boarder}/all', [Controllers\AccessUserController::class, 'userAll']);

    $router->get('access/enterprise/{enterprise_id}/permission/all', [Controllers\AccessPermissionController::class, 'permissionAll']);
    $router->get('access/enterprise/{enterprise_id}/permission/{id}/code', [Controllers\AccessPermissionController::class, 'permissionCode']);
    //下发用户人脸
    $router->put('access/dispatch/user/{id}/face/publish', [Controllers\AccessDispatchController::class, 'userFacePublish']);

    $router->resource('access/permission', Controllers\AccessPermissionController::class);
    $router->resource('access/dispatch', Controllers\AccessDispatchController::class);
    $router->resource('access/device', Controllers\AccessDeviceController::class);
    $router->resource('access/user', Controllers\AccessUserController::class);
    $router->resource('access/log', Controllers\AccessLogController::class);
});
