<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class MobileSessionLifetime
{
    public function handle(Request $request, Closure $next)
    {
        // Detect Vitalytics iOS app by User-Agent
        $userAgent = $request->userAgent() ?? '';

        if (str_contains($userAgent, 'Vitalytics-iOS')) {
            // 30 days in minutes
            config(['session.lifetime' => 43200]);
        }

        return $next($request);
    }
}
