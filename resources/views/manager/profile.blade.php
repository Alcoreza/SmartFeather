@extends('layouts.app')

@section('title', 'Manager Profile')

@push('styles')
    @vite([
        'resources/css/manager-shared.css',
        'resources/css/manager-profile.css'
    ])
@endpush

@push('scripts')
    @vite('resources/js/manager-profile.js')
@endpush

@section('content')
    <div class="manager-shell">
        @include('includes.manager-sidebar')

        <main class="manager-main profile-page-main">
            <div class="profile-page-topbar">
                <div>
                    <h1 class="profile-page-title">Profile</h1>
                    <p class="profile-page-subtitle">View your account details and manage your session.</p>
                </div>
            </div>

            <div class="profile-page-divider"></div>

            <section class="profile-page-card">
                <form class="profile-page-form">
                    <div class="profile-page-grid">
                        <div class="profile-page-field">
                            <label for="profileFirstName">First Name</label>
                            <input type="text" id="profileFirstName" readonly>
                        </div>

                        <div class="profile-page-field">
                            <label for="profileMiddleName">Middle Name</label>
                            <input type="text" id="profileMiddleName" readonly>
                        </div>

                        <div class="profile-page-field">
                            <label for="profileLastName">Last Name</label>
                            <input type="text" id="profileLastName" readonly>
                        </div>

                        <div class="profile-page-field">
                            <label for="profileSuffix">Suffix</label>
                            <input type="text" id="profileSuffix" readonly>
                        </div>
                    </div>

                    <div class="profile-page-field">
                        <label for="profileRole">Role</label>
                        <input type="text" id="profileRole" readonly>
                    </div>

                    <div class="profile-page-grid">
                        <div class="profile-page-field">
                            <label for="profilePhone">Phone Number</label>
                            <input type="text" id="profilePhone" readonly>
                        </div>

                        <div class="profile-page-field">
                            <label for="profileId">Employee ID</label>
                            <input type="text" id="profileId" readonly>
                        </div>

                        <div class="profile-page-field">
                            <label for="profileBirthday">Birthday</label>
                            <input type="text" id="profileBirthday" readonly>
                        </div>

                        <div class="profile-page-field">
                            <label for="profileGender">Gender</label>
                            <input type="text" id="profileGender" readonly>
                        </div>
                    </div>

                    <div class="profile-page-field">
                        <label for="profileAddress">Address</label>
                        <input type="text" id="profileAddress" readonly>
                    </div>

                    <div class="profile-page-actions">
                        <a href="{{ route('logout') }}" class="profile-page-btn logout-btn">Logout</a>
                    </div>
                </form>
            </section>
        </main>
    </div>
@endsection
