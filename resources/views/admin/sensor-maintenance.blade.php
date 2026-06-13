@extends('layouts.app')

@section('title', 'Admin Sensor Maintenance Records')

@push('styles')
    @vite([
        'resources/css/admin-shared.css',
        'resources/css/admin-sensor-maintenance.css'
    ])
@endpush

@push('scripts')
    @vite('resources/js/admin-sensor-maintenance.js')
@endpush

@section('content')
    <div class="admin-shell">
        @include('includes.admin-sidebar')
        @include('includes.admin-profile-modal')

        <main class="admin-main">
            <div class="admin-maintenance-topbar">
                <div class="admin-maintenance-topbar-spacer"></div>

                <h1 class="admin-maintenance-page-title">Sensor Configuration</h1>

                <div class="admin-maintenance-topbar-actions">
                </div>
            </div>

            <div class="admin-maintenance-divider"></div>

            <div class="admin-maintenance-toolbar">
                <a href="{{ route('admin.sensors') }}" class="admin-maintenance-back-btn" aria-label="Go back">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
                        <path d="M15 18l-6-6 6-6"></path>
                    </svg>
                </a>

                <div class="admin-maintenance-pill">Maintenance Records</div>
            </div>

            <section class="admin-maintenance-card">
                <div class="admin-maintenance-table-wrap">
                    <table class="admin-maintenance-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Sensor<br>Type</th>
                                <th>House<br>Number</th>
                                <th>Pen<br>Number</th>
                                <th>Maintenance<br>Date</th>
                            </tr>
                        </thead>
                        <tbody id="adminMaintenanceTableBody"></tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
@endsection
