@extends('layouts.app')

@section('title', 'Admin Profile')

@push('styles')
    @vite([
        'resources/css/admin-shared.css',
        'resources/css/admin-profile.css'
    ])
@endpush

@push('scripts')
    @vite('resources/js/profile-edit.js')
@endpush

@section('content')
    <div class="admin-shell">
        @include('includes.admin-sidebar')

        <main class="admin-main profile-page-main">
            <div class="admin-profile-page-shell">
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

                <div class="admin-profile-hero">
                    <div class="admin-profile-hero-avatar">
                        <span>{{ strtoupper(substr($user->FirstName ?? '', 0, 1)) }}{{ strtoupper(substr($user->LastName ?? '', 0, 1)) }}</span>
                    </div>

                    <div class="admin-profile-hero-copy">
                        <p class="admin-profile-page-user-name">
                            {{ trim(($user->FirstName ?? '') . ' ' . ($user->LastName ?? '')) ?: 'User profile' }}
                        </p>
                        <p class="admin-profile-page-user-meta">{{ $user->Role ?? 'Admin' }}</p>
                    </div>

                    <div class="admin-profile-hero-actions">
                        <button type="button" class="admin-profile-page-btn edit-btn" data-profile-edit-open>
                            Edit
                        </button>
                    </div>
                </div>

                <div class="admin-profile-page-layout">
                    <section class="admin-profile-page-panel admin-profile-page-panel-primary">
                        <div class="admin-profile-page-panel-header">
                            <div>
                                <p class="admin-profile-page-panel-label">Personal details</p>
                            </div>
                        </div>

                        <div class="admin-profile-page-form-grid">
                            <div class="admin-profile-page-field">
                                <label for="adminProfileFirstName">First name</label>
                                <input type="text" id="adminProfileFirstName" value="{{ $user->FirstName ?? '' }}" readonly aria-readonly="true">
                            </div>

                            <div class="admin-profile-page-field">
                                <label for="adminProfileMiddleName">Middle name</label>
                                <input type="text" id="adminProfileMiddleName" value="{{ $user->MiddleName ?? '' }}" readonly aria-readonly="true">
                            </div>

                            <div class="admin-profile-page-field">
                                <label for="adminProfileLastName">Last name</label>
                                <input type="text" id="adminProfileLastName" value="{{ $user->LastName ?? '' }}" readonly aria-readonly="true">
                            </div>

                            <div class="admin-profile-page-field">
                                <label for="adminProfileSuffix">Suffix</label>
                                <input type="text" id="adminProfileSuffix" value="{{ $user->Suffix ?? '' }}" readonly aria-readonly="true">
                            </div>

                            <div class="admin-profile-page-field">
                                <label for="adminProfileBirthday">Birthday</label>
                                <input type="text" id="adminProfileBirthday" value="{{ $birthdayDisplay }}" readonly aria-readonly="true">
                            </div>

                            <div class="admin-profile-page-field">
                                <label for="adminProfileGender">Gender</label>
                                <input type="text" id="adminProfileGender" value="{{ $user->Gender ?? '' }}" readonly aria-readonly="true">
                            </div>
                        </div>
                    </section>

                    <div class="admin-profile-page-side-stack">
                        <section class="admin-profile-page-panel">
                            <div class="admin-profile-page-panel-header">
                                <div>
                                    <p class="admin-profile-page-panel-label">Contact information</p>
                                </div>
                            </div>

                            <div class="admin-profile-page-form-grid admin-profile-page-form-grid-single-column">
                                <div class="admin-profile-page-field admin-profile-page-field-wide">
                                    <label for="adminProfilePhone">Phone number</label>
                                    <input type="text" id="adminProfilePhone" value="{{ $user->PhoneNumber ?? '' }}" readonly aria-readonly="true">
                                </div>

                                <div class="admin-profile-page-field admin-profile-page-field-wide">
                                    <label for="adminProfileAddress">Address</label>
                                    <input type="text" id="adminProfileAddress" value="{{ $user->Address ?? '' }}" readonly aria-readonly="true">
                                </div>
                            </div>
                        </section>

                        <section class="admin-profile-page-panel">
                            <div class="admin-profile-page-panel-header">
                                <div>
                                    <p class="admin-profile-page-panel-label">Employment information</p>
                                </div>
                            </div>

                            <div class="admin-profile-page-form-grid">
                                <div class="admin-profile-page-field">
                                    <label for="adminProfileRole">Role</label>
                                    <input type="text" id="adminProfileRole" value="{{ $user->Role ?? '' }}" readonly aria-readonly="true">
                                </div>

                                <div class="admin-profile-page-field">
                                    <label for="adminProfileUsername">Username</label>
                                    <input type="text" id="adminProfileUsername" value="{{ $user->Username ?? '' }}" readonly aria-readonly="true">
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
                                Save changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
@endsection
