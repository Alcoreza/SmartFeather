@extends('layouts.app')

@section('title', 'Employee Data')

@push('styles')
    @vite([
        'resources/css/manager-shared.css',
        'resources/css/manager-workers.css'
    ])
@endpush


@push('scripts')
    @vite('resources/js/manager-workers.js')
@endpush

@section('content')
    <div class="manager-shell">
        @include('includes.manager-sidebar')

        <main class="manager-main workers-main">
            <div class="workers-topbar">
                <h1 class="workers-title">Employee Data</h1>

                <button class="manager-profile" type="button" id="openProfileModal" aria-label="Open profile">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <circle cx="12" cy="8" r="4"></circle>
                        <path d="M4 20c1.8-3.8 5-5.5 8-5.5S18.2 16.2 20 20"></path>
                    </svg>
                </button>
            </div>

            @include('includes.manager-profile-modal')
            @include('includes.manager-workers-modal')

            <div class="workers-divider"></div>

            <section class="workers-toolbar">
                <div></div>

                <select id="roleFilter" class="workers-filter">
                    <option value="All">All</option>
                    <option value="Manager">Manager</option>
                    <option value="Admin">Admin</option>
                    <option value="Flockman">Flockman</option>
                </select>
            </section>

            <section class="workers-card">
                <div class="workers-table-wrap">
                    <table class="workers-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>ID</th>
                                <th>Role</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="workersTableBody">
                            <tr>
                                <td>Juan Dela Cruz</td>
                                <td>1</td>
                                <td>Manager</td>
                                <td class="text-center">
                                    <button class="view-worker-btn icon-btn" type="button" aria-label="View employee">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                    </button>
                                </td>

                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
@endsection