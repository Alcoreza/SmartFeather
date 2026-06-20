@extends('layouts.app')

@section('title', 'Admin Farm Management Records')

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

        <main class="admin-main record-house-main">
            <section class="record-house-page">
                <div class="record-house-topbar">
                    <h1 class="record-house-title">Farm Management Records</h1>
                </div>

                <div class="record-house-divider"></div>

                <div class="record-house-controls">
                    <a href="{{ route('admin.houses') }}" class="record-back-btn" aria-label="Back to Farm Management">
                        &#10094;
                    </a>

                    <div class="record-tabs" id="recordHouseTabs">
                        <!-- Tabs are populated by JavaScript from API -->
                    </div>

                    <div class="record-date-filters" aria-label="Farm management record date filters">
                        <label class="record-date-filter record-pen-filter">
                            <span>Pen</span>
                            <select id="penFilter">
                                <option value="">All Pens</option>
                            </select>
                        </label>

                        <label class="record-date-filter">
                            <span>Last Recorded</span>
                            <input type="date" id="recordedDateFilter">
                        </label>

                        <button type="button" class="record-clear-filters-btn" id="clearRecordDateFilters">
                            Clear
                        </button>
                    </div>
                </div>

                <div class="record-table-card">
                    <table class="record-table">
                        <thead id="recordTableHead">
                            <tr>
                                <th>House</th>
                                <th>Pen</th>
                                <th>Capacity</th>
                                <th>Population</th>
                                <th>Eggs Hatched</th>
                                <th>Mortality</th>
                                <th>Last Recorded</th>
                            </tr>
                        </thead>
                        <tbody id="recordTableBody">
                            <tr>
                                <td colspan="7">Loading records...</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="record-pagination" data-record-pagination>
                        <button type="button" class="record-page-btn" data-record-prev>
                            Previous
                        </button>
                        <div class="record-page-dots" data-record-dots></div>
                        <button type="button" class="record-page-btn" data-record-next>
                            Next
                        </button>
                    </div>
                </div>
            </section>
        </main>
    </div>
@endsection
