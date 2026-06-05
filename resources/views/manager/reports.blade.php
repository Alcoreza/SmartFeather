@extends('layouts.app')

@section('title', 'Reports')

@push('styles')
    @vite([
        'resources/css/manager-shared.css',
        'resources/css/manager-reports.css'
    ])
@endpush

@push('scripts')
    @vite('resources/js/manager-reports.js')
@endpush

@section('content')
    <div class="manager-shell">
        @include('includes.manager-sidebar')
        @include('includes.manager-profile-modal')

        <main class="manager-main reports-page">
            <div class="reports-topbar">
                <div class="reports-topbar-spacer"></div>

                <h1 class="reports-page-title">Reports</h1>
            </div>

            <div class="reports-divider"></div>

            <form class="reports-filter-card" id="reportsFilterForm">
                <div class="reports-filter-field">
                    <label for="reportsFromDate">From Date</label>
                    <input type="date" id="reportsFromDate" name="from_date">
                </div>

                <div class="reports-filter-field">
                    <label for="reportsToDate">To Date</label>
                    <input type="date" id="reportsToDate" name="to_date">
                </div>

                <div class="reports-filter-field">
                    <label for="reportsHouse">House</label>
                    <select id="reportsHouse" name="house">
                        <option value="">All Houses</option>
                    </select>
                </div>

                <button type="submit" class="reports-filter-submit">Generate Report</button>
            </form>

            <div class="reports-summary-card">
                <div class="reports-summary-stat">
                    <div class="reports-summary-label">Total Feed Consumed</div>
                    <div class="reports-summary-value" id="summaryFeedConsumed">-- kg</div>
                </div>
                <div class="reports-summary-stat">
                    <div class="reports-summary-label">Total Mortalities</div>
                    <div class="reports-summary-value" id="summaryMortalities">--</div>
                </div>
                <div class="reports-summary-stat">
                    <div class="reports-summary-label">Overall Farm Weight Status</div>
                    <div class="reports-summary-breakdown" id="summaryWeightStatus">--</div>
                </div>
            </div>

            <section class="reports-content-shell">
                <div id="reportContent"></div>
            </section>
        </main>
    </div>
@endsection
