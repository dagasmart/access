<?php

namespace DagaSmart\Access\Http\Middleware;

use Closure;
use DagaSmart\BizAdmin\Admin;
use Illuminate\Http\Request;

class CheckPackageMiddleware
{

    public function handle(Request $request, Closure $next)
    {
        if (!admin_extension_enabled('dagasmart.organization')) {
            return Admin::response()->fail('没有找到「<font color="#f40">基础安装包</font>」，请进行安装并启用');
        }
        return $next($request);
    }
}
