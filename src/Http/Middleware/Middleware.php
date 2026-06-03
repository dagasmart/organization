<?php

declare(strict_types=1);

namespace DagaSmart\Organization\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Middleware
{
    public function handle(Request $request, Closure $next)
    {
        if (admin_extension_expiry('dagasmart.organization')) {
            return admin_response()->fail('软件已过期,请续费');
        }
        if (! admin_extension_enabled('dagasmart.organization')) {
            return admin_response()->fail('软件已禁用，请开启');
        }

        return $next($request);
    }
}
