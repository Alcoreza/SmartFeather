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
                        <colgroup>
                            <col class="workers-name-col">
                            <col class="workers-role-col">
                            <col class="workers-actions-col">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Role</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="workersTableBody">
                        </tbody>
                    </table>
                </div>

                <div class="workers-pagination" data-workers-pagination>
                    <button type="button" class="workers-page-btn" data-workers-prev>
                        Previous
                    </button>
                    <div class="workers-page-dots" data-workers-dots></div>
                    <button type="button" class="workers-page-btn" data-workers-next>
                        Next
                    </button>
                </div>
            </section>
        </main>
    </div>
@endsection
