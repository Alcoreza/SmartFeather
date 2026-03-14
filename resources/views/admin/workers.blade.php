@extends('layouts.app')

@section('title', 'Admin Employee Data')

@push('styles')
    @vite([
        'resources/css/admin-shared.css',
        'resources/css/admin-workers.css'
    ])
@endpush

@push('scripts')
    @vite('resources/js/admin-workers.js')
@endpush

@section('content')
    <div class="admin-shell">
        @include('includes.admin-sidebar')

        <main class="admin-main">
            <div class="admin-workers-topbar">
                <h1 class="admin-workers-title">Employee Data</h1>

                <button class="admin-profile-btn" type="button" id="openAdminProfileModal" aria-label="Open profile">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <circle cx="12" cy="8" r="4"></circle>
                        <path d="M4 20c1.8-3.8 5-5.5 8-5.5S18.2 16.2 20 20"></path>
                    </svg>
                </button>
            </div>

            @include('includes.admin-profile-modal')
            @include('includes.admin-workers-modals')

            <div class="admin-workers-divider"></div>

            <section class="admin-workers-toolbar">
                <div class="admin-workers-toolbar-actions">
                    <button type="button" class="admin-add-worker-btn" id="openAddWorkerModal" aria-label="Add employee">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
                            <path d="M12 5v14"></path>
                            <path d="M5 12h14"></path>
                        </svg>
                    </button>
                </div>

                <select id="roleFilter" class="admin-workers-filter">
                    <option value="All">All</option>
                    <option value="Manager">Manager</option>
                    <option value="Admin">Admin</option>
                    <option value="Flockman">Flockman</option>
                </select>
            </section>

            <section class="admin-workers-card">
                <div class="admin-workers-table-wrap">
                    <table class="admin-workers-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>ID</th>
                                <th>Role</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="workersTableBody"></tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
@endsection