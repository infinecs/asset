<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RestrictManagerRole
{
    private const ALLOWED_ROUTE_PATTERNS = [
        'outcome.categories.*',
        'outcome.departments.*',
        'outcome.report',
        'outcome.summary*',
        'settings.*',
        'logout',
    ];

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->isManager() && !$request->routeIs(...self::ALLOWED_ROUTE_PATTERNS)) {
            return redirect()->route('outcome.summary');
        }

        return $next($request);
    }
}
