@extends('layouts.app')

@section('title', 'Manager Profile')

@push('styles')
    @vite([
        'resources/css/manager-shared.css',
        'resources/css/manager-profile.css'
    ])
@endpush

@section('content')
    <div class="manager-shell">
        @include('includes.manager-sidebar')

        <main class="manager-main profile-page-main">
            <div class="profile-page-shell">
                <div class="profile-page-hero">
                    <div class="profile-page-hero-avatar">
                        <span>{{ strtoupper(substr($user->FirstName ?? '', 0, 1)) }}{{ strtoupper(substr($user->LastName ?? '', 0, 1)) }}</span>
                    </div>

                    <div class="profile-page-hero-copy">
                        <p class="profile-page-eyebrow">Manager account</p>
                        <h1 class="profile-page-title">Profile</h1>
                        <p class="profile-page-subtitle">
                            Review your account details in a cleaner, easier-to-read view.
                        </p>
                    </div>

                    <div class="profile-page-hero-actions">
                        <span class="profile-page-pill">Active account</span>
                        <a href="{{ route('logout') }}" class="profile-page-btn logout-btn">Logout</a>
                    </div>
                </div>

                <div class="profile-page-grid">
                    <section class="profile-page-panel">
                        <div class="profile-page-panel-header">
                            <div>
                                <p class="profile-page-panel-label">Personal details</p>
                                <h2>Identity</h2>
                            </div>
                        </div>

                        <div class="profile-page-form-grid">
                            <div class="profile-page-field">
                                <label for="profileFirstName">First name</label>
                                <input type="text" id="profileFirstName" value="{{ $user->FirstName ?? '' }}" readonly aria-readonly="true">
                            </div>

                            <div class="profile-page-field">
                                <label for="profileMiddleName">Middle name</label>
                                <input type="text" id="profileMiddleName" value="{{ $user->MiddleName ?? '' }}" readonly aria-readonly="true">
                            </div>

                            <div class="profile-page-field">
                                <label for="profileLastName">Last name</label>
                                <input type="text" id="profileLastName" value="{{ $user->LastName ?? '' }}" readonly aria-readonly="true">
                            </div>

                            <div class="profile-page-field">
                                <label for="profileSuffix">Suffix</label>
                                <input type="text" id="profileSuffix" value="{{ $user->Suffix ?? '' }}" readonly aria-readonly="true">
                            </div>

                            <div class="profile-page-field profile-page-field-wide">
                                <label for="profileBirthday">Birthday</label>
                                <input type="text" id="profileBirthday" value="{{ $birthdayDisplay }}" readonly aria-readonly="true">
                            </div>

                            <div class="profile-page-field">
                                <label for="profileGender">Gender</label>
                                <input type="text" id="profileGender" value="{{ $user->Gender ?? '' }}" readonly aria-readonly="true">
                            </div>
                        </div>
                    </section>

                    <section class="profile-page-panel">
                        <div class="profile-page-panel-header">
                            <div>
                                <p class="profile-page-panel-label">Employment info</p>
                                <h2>Work details</h2>
                            </div>
                        </div>

                        <div class="profile-page-form-grid">
                            <div class="profile-page-field">
                                <label for="profileRole">Role</label>
                                <input type="text" id="profileRole" value="{{ $user->Role ?? '' }}" readonly aria-readonly="true">
                            </div>

                            <div class="profile-page-field">
                                <label for="profileId">Username</label>
                                <input type="text" id="profileId" value="{{ $user->Username ?? '' }}" readonly aria-readonly="true">
                            </div>

                            <div class="profile-page-field profile-page-field-wide">
                                <label for="profilePhone">Phone number</label>
                                <input type="text" id="profilePhone" value="{{ $user->PhoneNumber ?? '' }}" readonly aria-readonly="true">
                            </div>

                            <div class="profile-page-field profile-page-field-wide">
                                <label for="profileAddress">Address</label>
                                <input type="text" id="profileAddress" value="{{ $user->Address ?? '' }}" readonly aria-readonly="true">
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </main>
    </div>
@endsection
