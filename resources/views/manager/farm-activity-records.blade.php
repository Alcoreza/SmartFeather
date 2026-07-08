@extends('layouts.app')

@section('title', 'Farm Activity Records')

@push('styles')
    @vite([
        'resources/css/manager-shared.css',
        'resources/css/manager-reports.css',
        'resources/css/manager-farm-activity-records.css'
    ])
@endpush

@push('scripts')
    @vite('resources/js/manager-farm-activity-records.js')
@endpush

@section('content')
    <div class="manager-shell">
        @include('includes.manager-sidebar')

        <main class="manager-main reports-page farm-records-page">
            <div class="reports-topbar">
                <div class="reports-topbar-spacer"></div>

                <h1 class="reports-page-title">Farm Activity Records</h1>
            </div>

            <div class="reports-divider"></div>

            <form class="reports-filter-card farm-records-filter-card" id="farmRecordsFilterForm">
                <div class="reports-filter-field">
                    <label for="farmRecordTypeFilter">Activity Type</label>
                    <select id="farmRecordTypeFilter" name="activity_type" class="reports-filter-select" aria-label="Activity Type">
                        <option value="Hatch and Mortality Check">Hatch and Mortality Check</option>
                        <option value="Weight Monitoring">Weight Monitoring</option>
                        <option value="Feed Replenishment">Feed Replenishment</option>
                        <option value="Vitamin Supplementation">Vitamin Supplementation</option>
                        <option value="Pen Disinfection">Pen Disinfection</option>
                        <option value="Pen Cleaning">Pen Cleaning</option>
                        <option value="Sensor Inspection">Sensor Inspection</option>
                        <option value="Chick Placement">Chick Placement</option>
                    </select>
                </div>

                <div class="reports-filter-field">
                    <label for="farmRecordsFromDate">From Date</label>
                    <input type="date" id="farmRecordsFromDate" name="from_date">
                </div>

                <div class="reports-filter-field">
                    <label for="farmRecordsToDate">To Date</label>
                    <input type="date" id="farmRecordsToDate" name="to_date">
                </div>

                <div class="reports-filter-field">
                    <label for="farmRecordHouseFilter">House</label>
                    <select id="farmRecordHouseFilter" name="house_id" class="reports-filter-select" aria-label="House">
                        <option value="">All Houses</option>
                    </select>
                </div>

                <div class="reports-filter-field">
                    <label for="farmRecordFlockmanFilter">Flockman</label>
                    <select id="farmRecordFlockmanFilter" name="flockman_id" class="reports-filter-select" aria-label="Flockman">
                        <option value="">All Flockmen</option>
                    </select>
                </div>

                <button type="submit" class="reports-filter-submit">Generate</button>
            </form>

            <section class="reports-content-shell">
                <div id="farmRecordContent"></div>
            </section>
        </main>
    </div>
@endsection
