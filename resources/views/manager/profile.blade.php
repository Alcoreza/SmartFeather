@extends('layouts.app')

@section('title', 'Manager Profile')

@push('styles')
    @vite([
        'resources/css/manager-shared.css',
        'resources/css/manager-profile.css'
    ])
@endpush

@push('scripts')
    @vite('resources/js/profile-edit.js')
@endpush

@section('content')
    <div class="manager-shell">
        @include('includes.manager-sidebar')

        <main class="manager-main profile-page-main">
            <div class="profile-page-shell">
                @if(session('profile_success'))
                    <div class="profile-status profile-status-success">
                        {{ session('profile_success') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="profile-status profile-status-error">
                        <p>Please fix the following:</p>
                        <ul>
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="profile-page-hero">
                    <div class="profile-page-hero-avatar">
                        <span>{{ strtoupper(substr($user->FirstName ?? '', 0, 1)) }}{{ strtoupper(substr($user->LastName ?? '', 0, 1)) }}</span>
                        <span class="profile-page-hero-avatar-badge" aria-hidden="true"></span>
                    </div>

                    <div class="profile-page-hero-copy">
                        <p class="profile-page-user-name">
                            {{ trim(($user->FirstName ?? '') . ' ' . ($user->LastName ?? '')) ?: 'User profile' }}
                        </p>
                        <p class="profile-page-user-meta">{{ $user->Role ?? 'Manager' }}</p>
                    </div>

                    <div class="profile-page-hero-actions">
                        <button type="button" class="profile-page-btn edit-btn" data-profile-edit-open>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                <path d="M12 20h9"></path>
                                <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"></path>
                            </svg>
                            Edit
                        </button>
                    </div>
                </div>

                <div class="profile-page-layout">
                    <section class="profile-page-panel profile-page-panel-primary">
                        <div class="profile-page-panel-header">
                            <div>
                                <p class="profile-page-panel-label">Personal details</p>
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

                            <div class="profile-page-field">
                                <label for="profileBirthday">Birthday</label>
                                <input type="text" id="profileBirthday" value="{{ $birthdayDisplay }}" readonly aria-readonly="true">
                            </div>

                            <div class="profile-page-field">
                                <label for="profileGender">Gender</label>
                                <input type="text" id="profileGender" value="{{ $user->Gender ?? '' }}" readonly aria-readonly="true">
                            </div>
                        </div>
                    </section>

                    <div class="profile-page-side-stack">
                        <section class="profile-page-panel">
                            <div class="profile-page-panel-header">
                                <div>
                                    <p class="profile-page-panel-label">Contact information</p>
                                </div>
                            </div>

                            <div class="profile-page-form-grid profile-page-form-grid-single-column">
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

                        <section class="profile-page-panel">
                            <div class="profile-page-panel-header">
                                <div>
                                    <p class="profile-page-panel-label">Employment information</p>
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
                            </div>
                        </section>
                    </div>
                </div>
            </div>

            <div class="profile-edit-modal-backdrop" data-profile-edit-modal>
                <div class="profile-edit-modal-card">
                    <div class="profile-edit-modal-header">
                        <h2>Edit profile</h2>
                        <p>Update your phone number and address.</p>
                    </div>

                    <form id="profileEditForm" method="POST" action="{{ route('profile.update') }}">
                        @csrf
                        @method('PATCH')

                        <div class="profile-edit-form-grid">
                            <div class="profile-edit-field">
                                <label for="profileEditFirstName">First name</label>
                                <input type="text" id="profileEditFirstName" value="{{ $user->FirstName ?? '' }}" readonly>
                            </div>

                            <div class="profile-edit-field">
                                <label for="profileEditMiddleName">Middle name</label>
                                <input type="text" id="profileEditMiddleName" value="{{ $user->MiddleName ?? '' }}" readonly>
                            </div>

                            <div class="profile-edit-field">
                                <label for="profileEditLastName">Last name</label>
                                <input type="text" id="profileEditLastName" value="{{ $user->LastName ?? '' }}" readonly>
                            </div>

                            <div class="profile-edit-field">
                                <label for="profileEditSuffix">Suffix</label>
                                <input type="text" id="profileEditSuffix" value="{{ $user->Suffix ?? '' }}" readonly>
                            </div>

                            <div class="profile-edit-field">
                                <label for="profileEditBirthday">Birthday</label>
                                <input type="text" id="profileEditBirthday" value="{{ $birthdayDisplay }}" readonly>
                            </div>

                            <div class="profile-edit-field">
                                <label for="profileEditGender">Gender</label>
                                <input type="text" id="profileEditGender" value="{{ $user->Gender ?? '' }}" readonly>
                            </div>
                        </div>

                        <div class="profile-edit-field">
                            <label for="profileEditRole">Role</label>
                            <input type="text" id="profileEditRole" value="{{ $user->Role ?? '' }}" readonly>
                        </div>

                        <div class="profile-edit-field">
                            <label for="profileEditUsername">Username</label>
                            <input type="text" id="profileEditUsername" value="{{ $user->Username ?? '' }}" readonly>
                        </div>

                        <div class="profile-edit-field">
                            <label for="profileEditPhoneNumber">Phone number</label>
                            <input type="text" id="profileEditPhoneNumber" name="PhoneNumber" value="{{ old('PhoneNumber', $user->PhoneNumber ?? '') }}">
                        </div>

                        <div class="profile-edit-field">
                            <label for="profileEditAddress">Address</label>
                            <input type="text" id="profileEditAddress" name="Address" value="{{ old('Address', $user->Address ?? '') }}">
                        </div>

                        <div class="profile-edit-actions">
                            <button type="button" class="profile-edit-btn cancel-btn" data-profile-edit-cancel>
                                Cancel
                            </button>
                            <button type="submit" class="profile-edit-btn save-btn">
                                Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
@endsection
