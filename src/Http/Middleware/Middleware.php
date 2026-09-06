<?php

declare(strict_types=1);

namespace DagaSmart\Access\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Middleware
{
    public function handle(Request $request, Closure $next)
    {
        if (! admin_extension_enabled('dagasmart.basic')) {
            return admin_response()->fail('没有找到「<b class=text-danger>基础安装包</b>」，请进行安装并启用');
        }

        if (admin_extension_expiry('dagasmart.access')) {
            return admin_response()->fail('软件已过期,请续费');
        }

        if (! admin_extension_enabled('dagasmart.access')) {
            return admin_response()->fail('软件已禁用，请开启');
        }

        return $next($request);
    }
}
