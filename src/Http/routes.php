<?php

use DagaSmart\Access\Http\Controllers;
use DagaSmart\Access\Http\Middleware;
use DagaSmart\BizAdmin\Middleware\Authenticate;
use DagaSmart\BizAdmin\Middleware\Permission;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

// Route::get('access', [Controllers\AccessController::class, 'index']);

// 免登录无限制
// Route::get('access', [Controllers\AccessController::class, 'index'])->withoutMiddleware([Authenticate::class, Permission::class]);

Route::group([
    'prefix' => 'biz',
    'middleware' => [Middleware\Middleware::class],
], function (Router $router) {
    $router->get('access/enterprise/{enterprise_id}/facility/options', [Controllers\AccessDeviceController::class, 'options']);
    $router->get('access/enterprise/{enterprise_id}/facility/{facility_id}/device/all', [Controllers\AccessDeviceController::class, 'deviceAll']);
    $router->get('access/enterprise/{enterprise_id}/department/{department_id}/user/{user_type}/all', [Controllers\AccessUserController::class, 'userAll']);
    $router->get('access/enterprise/{enterprise_id}/grade/{grade_id}/classes/{classes_id}/user/{user_type}/is_boarder/{is_boarder}/all', [Controllers\AccessUserController::class, 'userAll']);

    // 获取用户/一键导入
    $router->get('access/enterprise/{enterprise_id}/{grade_id}/{classes_id}/{department_id}/{user_type}/user', [Controllers\AccessUserController::class, 'getAccessUser']);
    $router->put('access/user/import', [Controllers\AccessUserController::class, 'userImport']);


    $router->get('access/enterprise/{enterprise_id}/permission/all', [Controllers\AccessPermissionController::class, 'permissionAll']);
    $router->get('access/enterprise/{enterprise_id}/permission/{id}/code', [Controllers\AccessPermissionController::class, 'permissionCode']);
    // 下发用户人脸
    $router->put('access/dispatch/user/{id}/face/publish', [Controllers\AccessDispatchController::class, 'userFacePublish']);

    $router->resource('access/permission', Controllers\AccessPermissionController::class);
    $router->resource('access/dispatch', Controllers\AccessDispatchController::class);
    $router->resource('access/device', Controllers\AccessDeviceController::class);
    $router->resource('access/user', Controllers\AccessUserController::class);
    $router->resource('access/log', Controllers\AccessLogController::class);
});
