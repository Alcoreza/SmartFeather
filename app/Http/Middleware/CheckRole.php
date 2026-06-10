<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $role)
    {
        $userRole = session()->get('role');

        if (!$userRole || $userRole !== $role) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Forbidden'], 403);
            }
            
            return redirect('/')->with('error', 'You do not have permission to access this');
        }

        return $next($request);
    }
}
