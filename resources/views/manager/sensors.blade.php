@extends('layouts.app')

@section('title', 'Manager Sensors')

@push('styles')
    @vite([
        'resources/css/manager-shared.css',
        'resources/css/manager-sensors.css'
    ])
@endpush

@push('scripts')
    @vite('resources/js/manager-sensors.js')
@endpush

@section('content')
    <div class="manager-shell">
        @include('includes.manager-sidebar')
        @include('includes.manager-profile-modal')
        @include('includes.manager-sensors-modals')

        <main class="manager-main">
            <div class="manager-sensors-topbar">
                <h1 class="manager-sensors-title">Sensor Configuration</h1>

                <div class="manager-sensors-topbar-actions">
                </div>
            </div>

            <div class="manager-sensors-divider"></div>

            <div class="manager-sensors-toolbar">
                <a href="{{ route('manager.sensor-maintenance') }}" class="manager-sensor-maintenance-btn"
                    aria-label="Maintenance logs">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18v3h3l6.3-6.3a4 4 0 0 0 5.4-5.4l-2.2 2.2-2.8-2.8 2-2.4z">
                        </path>
                    </svg>
                </a>
            </div>


            <div id="managerSensorSections" class="manager-sensors-sections"></div>
        </main>
    </div>
@endsection