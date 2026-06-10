<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PreventCaching
{
    /**
     * Prevent browser caching for sensitive pages
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Prevent caching of protected pages
        return $response
            ->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Thu, 01 Jan 1970 00:00:00 GMT')
            ->header('X-Frame-Options', 'DENY')
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('X-XSS-Protection', '1; mode=block');
    }
}
