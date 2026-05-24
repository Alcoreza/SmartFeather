@extends('layouts.app')

@section('title', 'Manager Management')

@push('styles')
    @vite([
        'resources/css/manager-shared.css',
        'resources/css/manager-management.css'
    ])
@endpush

@push('scripts')
    @vite('resources/js/manager-management.js')
@endpush

@section('content')
    <div class="manager-shell">
        @include('includes.manager-sidebar')
        @include('includes.manager-profile-modal')

        <main class="manager-main management-main">
            <div class="management-topbar">
                <h1 class="management-title">Management</h1>

                <div class="management-topbar-actions">
                    <button class="manager-profile" type="button" id="openProfileModal" aria-label="Open profile">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <circle cx="12" cy="8" r="4"></circle>
                            <path d="M4 20c1.8-3.8 5-5.5 8-5.5S18.2 16.2 20 20"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="management-divider"></div>

            <section class="management-section">
                <div class="management-list">
                    <button type="button" class="management-action-card" id="openCreateTaskTypeModal">
                        <span class="management-action-icon">✓</span>
                        <span class="management-action-label">Create Task</span>
                    </button>

                    <button type="button" class="management-action-card">
                        <span class="management-action-icon">▣</span>
                        <span class="management-action-label">Add Inventory</span>
                    </button>
                </div>
            </section>

            @include('includes.manager-create-task-modal')
        </main>
    </div>
@endsection
