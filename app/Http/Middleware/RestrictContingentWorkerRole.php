<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RestrictContingentWorkerRole
{
    private const ALLOWED_ROUTE_PATTERNS = [
        'outcome.*',
        'settings.*',
        'logout',
    ];

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->isContingentWorker() && !$request->routeIs(...self::ALLOWED_ROUTE_PATTERNS)) {
            return redirect()->route('outcome.index');
        }

        return $next($request);
    }
}
