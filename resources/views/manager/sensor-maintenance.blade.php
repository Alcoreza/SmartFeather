@extends('layouts.app')

@section('title', 'Manager Sensor Maintenance Records')

@push('styles')
    @vite([
        'resources/css/manager-shared.css',
        'resources/css/manager-sensor-maintenance.css'
    ])
@endpush

@push('scripts')
    @vite('resources/js/manager-sensor-maintenance.js')
@endpush

@section('content')
    <div class="manager-shell">
        @include('includes.manager-sidebar')
        @include('includes.manager-profile-modal')

        <main class="manager-main">
            <div class="manager-maintenance-topbar">
                <div class="manager-maintenance-topbar-spacer"></div>

                <h1 class="manager-maintenance-page-title">Sensor Configuration</h1>

                <div class="manager-maintenance-topbar-actions">
                </div>
            </div>

            <div class="manager-maintenance-divider"></div>

            <div class="manager-maintenance-toolbar">
                <a href="{{ route('manager.sensors') }}" class="manager-maintenance-back-btn" aria-label="Go back">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
                        <path d="M15 18l-6-6 6-6"></path>
                    </svg>
                </a>

                <div class="manager-maintenance-pill">Maintenance Records</div>
            </div>

            <section class="manager-maintenance-card">
                <div class="manager-maintenance-table-wrap">
                    <table class="manager-maintenance-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Sensor<br>Type</th>
                                <th>House<br>Number</th>
                                <th>Pen<br>Number</th>
                                <th>Maintenance<br>Date</th>
                            </tr>
                        </thead>
                        <tbody id="managerMaintenanceTableBody"></tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
@endsection
