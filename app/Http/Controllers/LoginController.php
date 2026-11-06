<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;

class LoginController extends Controller
{
    /**
     * Show the login page
     * If user is already logged in, redirect to their dashboard
     */
    public function show(): Response|RedirectResponse
    {
        // If user is already logged in, redirect them to their dashboard
        if (session()->has('user_id')) {
            $role = session()->get('role');
            
            if ($role === 'Manager') {
                return $this->withNoCacheHeaders(redirect()->route('manager.dashboard'));
            } elseif ($role === 'Admin') {
                return $this->withNoCacheHeaders(redirect()->route('admin.dashboard'));
            }
        }

        return $this->withNoCacheHeaders(response()->view('auth.login'));
    }

    private function withNoCacheHeaders(Response|RedirectResponse $response): Response|RedirectResponse
    {
        return $response
            ->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Thu, 01 Jan 1970 00:00:00 GMT');
    }
}
