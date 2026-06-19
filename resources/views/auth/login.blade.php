@extends('layouts.app')

@section('title', 'Login')

@push('styles')
    @vite('resources/css/login.css')
@endpush

@push('scripts')
    @vite('resources/js/login.js')
@endpush

@section('content')
    <section class="login-page-bg">
        <div class="login-wrapper">
            <div class="login-shell">
                <div class="login-card">
                    <div class="login-card-header text-center">
                        <img class="login-logo" src="{{ asset('images/AppLogoSmartFeather-favicon.png') }}" alt="" aria-hidden="true">
                        <p class="login-eyebrow text-uppercase">MJBJ Corporation</p>
                        <h1 class="login-title fw-bold">Welcome to SmartFeather</h1>
                        <p class="login-subtitle">Sign in to monitor farm records, tasks, and daily operations.</p>
                    </div>

                    <form method="POST" action="#" id="loginForm" class="login-form">
                        @csrf

                        <div class="login-field">
                            <label for="username" class="form-label login-label">Username</label>
                            <div class="login-input-wrap">
                                <span class="login-input-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" focusable="false">
                                        <path d="M20 21a8 8 0 0 0-16 0"/>
                                        <circle cx="12" cy="7" r="4"/>
                                    </svg>
                                </span>
                                <input type="text" id="username" name="username" class="form-control custom-input"
                                    placeholder="Enter your username" autocomplete="username">
                            </div>
                        </div>

                        <div class="login-field">
                            <label for="password" class="form-label login-label">Password</label>
                            <div class="login-input-wrap">
                                <span class="login-input-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" focusable="false">
                                        <rect x="5" y="11" width="14" height="10" rx="2"/>
                                        <path d="M8 11V8a4 4 0 0 1 8 0v3"/>
                                    </svg>
                                </span>
                                <input type="password" id="password" name="password" class="form-control custom-input"
                                    placeholder="Enter your password" autocomplete="current-password">
                            </div>
                        </div>

                        <div class="login-actions">
                            <button type="submit" class="go-btn" id="loginSubmitBtn">
                                <span class="go-btn-label">Login</span>
                            </button>
                        </div>
                    </form>
                </div>

                <aside class="login-showcase" aria-label="SmartFeather operations preview">
                    <div class="login-showcase-image" aria-hidden="true"></div>
                    <div class="login-status-card login-status-card-primary">
                        <span>Today</span>
                        <strong>Farm activity ready for review</strong>
                    </div>
                    <div class="login-status-card login-status-card-secondary">
                        <span>Inventory</span>
                        <strong>Feed and vitamin stock visible</strong>
                    </div>
                    <div class="login-showcase-copy">
                        <h2>Farm records, ready when needed.</h2>
                        <p>SmartFeather keeps daily work visible for MJBJ Corporation.</p>
                    </div>
                </aside>
            </div>
        </div>

        <div class="login-error-modal-backdrop" id="loginErrorModal" aria-hidden="true">
            <div class="login-error-modal" role="alertdialog" aria-modal="true" aria-labelledby="loginErrorTitle">
                <div class="login-error-icon" aria-hidden="true">!</div>
                <h2 id="loginErrorTitle">Login Failed</h2>
                <p id="loginErrorMessage">Wrong username or password.</p>
                <button type="button" class="login-error-btn" id="closeLoginErrorModal">OK</button>
            </div>
        </div>
    </section>
@endsection
