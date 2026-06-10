<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AuthenticateSession
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if user has an active session
        if (!session()->has('user_id')) {
            // If it's an API request, return JSON
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }
            
            // Otherwise redirect to login
            return redirect('/')->with('error', 'Please login first');
        }

        return $next($request);
    }
}
