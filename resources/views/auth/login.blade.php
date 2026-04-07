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
        <div class="login-wrapper d-flex align-items-center justify-content-center px-3">
            <div class="bg-orb orb-one"></div>
            <div class="bg-orb orb-two"></div>

            <div class="container position-relative z-1">
                <div class="row justify-content-center">
                    <div class="col-12 col-md-8 col-lg-5 col-xl-4">
                        <div class="login-card p-4 p-md-5">
                            <div class="text-center mb-5">
                                <p class="login-eyebrow text-uppercase mb-2">Welcome Back</p>
                                <h1 class="login-title fw-bold mb-2">Log In</h1>
                                <p class="login-subtitle mb-0">Enter your credentials to continue.</p>
                            </div>

                            <form method="POST" action="#" id="loginForm">
                                @csrf

                                <div class="mb-4">
                                    <label for="user_id" class="form-label login-label">ID</label>
                                    <input type="text" id="user_id" name="user_id" class="form-control custom-input"
                                        placeholder="Enter your ID">
                                </div>

                                <div class="mb-4">
                                    <label for="password" class="form-label login-label">Password</label>
                                    <input type="password" id="password" name="password" class="form-control custom-input"
                                        placeholder="Enter your password">
                                </div>

                                <div class="text-center mt-5">
                                    <button type="submit" class="go-btn px-5">Go</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection