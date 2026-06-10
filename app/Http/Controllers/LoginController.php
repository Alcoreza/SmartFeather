<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class LoginController extends Controller
{
    /**
     * Show the login page
     * If user is already logged in, redirect to their dashboard
     */
    public function show(): View|RedirectResponse
    {
        // If user is already logged in, redirect them to their dashboard
        if (session()->has('user_id')) {
            $role = session()->get('role');
            
            if ($role === 'Manager') {
                return redirect()->route('manager.dashboard');
            } elseif ($role === 'Admin') {
                return redirect()->route('admin.dashboard');
            }
        }

        return view('auth.login');
    }
}
