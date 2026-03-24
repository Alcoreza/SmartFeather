@extends('layouts.app')

@section('title', 'Admin House Records')

@push('styles')
    @vite([
        'resources/css/admin-shared.css',
        'resources/css/admin-record-house.css'
    ])
@endpush

@push('scripts')
    @vite('resources/js/admin-record-house.js')
@endpush

@section('content')
    <div class="admin-shell">
        @include('includes.admin-sidebar')

        <main class="admin-main">
            <section class="record-house-page">
                <div class="record-page-header">
                    <h1>House Data</h1>
                </div>

                <div class="record-house-tabs" id="recordHouseTabs">
                    <!-- Tabs are populated by JavaScript from API -->
                </div>

                <div class="record-page-divider"></div>

                <div class="record-topbar">
                    <a href="{{ route('admin.houses') }}" class="record-back-btn" title="Back">
                        &#10094;
                    </a>

                    <div class="record-pill">Records</div>
                </div>

                <div class="record-table-card">
                    <div class="record-table-wrap">
                        <table class="record-table">
                            <thead>
                                <tr>
                                    <th>House</th>
                                    <th>Pen Name</th>
                                    <th>Capacity</th>
                                    <th>Population</th>
                                    <th>Eggs Hatched</th>
                                    <th>Mortality</th>
                                    <th>Last Recorded</th>
                                </tr>
                            </thead>
                            <tbody id="recordTableBody">
                                <tr>
                                    <td colspan="7" class="record-empty">Loading records...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </main>
    </div>
@endsection