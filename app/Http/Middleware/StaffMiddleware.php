<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class StaffMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user() || !$request->user()->isStaff()) {
            abort(403, 'Access denied. Staff only.');
        }
        return $next($request);
    }
}
