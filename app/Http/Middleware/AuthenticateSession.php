<?php

namespace App\Http\Middleware;

use App\Models\User;
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
        if (!$request->session()->has('user_id')) {
            return $this->unauthenticated($request);
        }

        if ($this->sessionWasRecentlyVerified($request)) {
            return $next($request);
        }

        $user = User::find($request->session()->get('user_id'));

        if (
            !$user ||
            !$user->is_active ||
            $request->session()->get('role') !== $user->Role
        ) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $this->unauthenticated($request);
        }

        $request->session()->put('auth_verified_at', now()->timestamp);

        return $next($request);
    }

    private function sessionWasRecentlyVerified(Request $request): bool
    {
        $seconds = (int) env('AUTH_SESSION_RECHECK_SECONDS', 30);

        if ($seconds <= 0) {
            return false;
        }

        $verifiedAt = (int) $request->session()->get('auth_verified_at', 0);

        return $verifiedAt > 0 && now()->timestamp - $verifiedAt < $seconds;
    }

    private function unauthenticated(Request $request)
    {
        // If it's an API request, return JSON
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Otherwise redirect to login
        return redirect('/login')
            ->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Thu, 01 Jan 1970 00:00:00 GMT')
            ->with('error', 'Please login first');
    }
}
