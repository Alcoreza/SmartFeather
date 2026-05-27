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
            </div>

            <div class="management-divider"></div>

            <section class="management-section">
                <div class="management-list">
                    <button type="button" class="management-action-card" id="openCreateTaskTypeModal">
                        <span class="management-action-icon">✓</span>
                        <span class="management-action-label">Create Task</span>
                    </button>

                    <button type="button" class="management-action-card" id="openCreateInventoryTypeModal">
                        <span class="management-action-icon">▣</span>
                        <span class="management-action-label">Add Inventory</span>
                    </button>
                </div>
            </section>

            @include('includes.manager-create-task-modal')
            @include('includes.manager-create-inventory-modal')
        </main>
    </div>
@endsection
