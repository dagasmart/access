<?php

namespace DagaSmart\Access\Http\Middleware;

use Closure;
use DagaSmart\BizAdmin\Admin;
use Illuminate\Http\Request;

class CheckPackageMiddleware
{

    public function handle(Request $request, Closure $next)
    {
        if (!class_exists(\DagaSmart\Organization\OrganizationServiceProvider::class)) {
            return Admin::response()->fail('尚未安装软件《基数安装包》');
        }
        return $next($request);
    }
}
