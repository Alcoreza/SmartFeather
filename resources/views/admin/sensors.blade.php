@extends('layouts.app')

@section('title', 'Admin Sensors')

@push('styles')
    @vite([
        'resources/css/admin-shared.css',
        'resources/css/admin-sensors.css'
    ])
@endpush

@push('scripts')
    @vite('resources/js/admin-sensors.js')
@endpush

@section('content')
    <div class="admin-shell">
        @include('includes.admin-sidebar')
        @include('includes.admin-profile-modal')
        @include('includes.admin-sensors-modals')

        <main class="admin-main">
            <div class="admin-sensors-topbar">
                <h1 class="admin-sensors-title">Sensor Configuration</h1>

                <div class="admin-sensors-topbar-actions">
                    <button class="admin-profile" type="button" id="openProfileModal" aria-label="Open profile">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <circle cx="12" cy="8" r="4"></circle>
                            <path d="M4 20c1.8-3.8 5-5.5 8-5.5S18.2 16.2 20 20"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="admin-sensors-divider"></div>

            <div class="admin-sensors-toolbar">
                <button type="button" class="admin-sensor-add-btn" id="openAddSensorModal" aria-label="Add sensor">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6">
                        <path d="M12 5v14"></path>
                        <path d="M5 12h14"></path>
                    </svg>
                </button>

                <a href="{{ route('admin.sensor-maintenance') }}" class="admin-sensor-maintenance-btn"
                    aria-label="Maintenance logs">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18v3h3l6.3-6.3a4 4 0 0 0 5.4-5.4l-2.2 2.2-2.8-2.8 2-2.4z">
                        </path>
                    </svg>
                </a>
            </div>

            <div id="adminSensorSections" class="admin-sensors-sections"></div>
        </main>
    </div>
@endsection