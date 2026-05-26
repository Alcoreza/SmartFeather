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
                    </div>

                    <div class="profile-page-hero-copy">
                        <h1 class="profile-page-title">Profile</h1>
                    </div>

                    <div class="profile-page-hero-actions">
                        <button type="button" class="profile-page-btn edit-btn" data-profile-edit-open>
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
