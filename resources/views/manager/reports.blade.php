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
        @include('includes.manager-generate-report-modal')

        <main class="manager-main reports-page">
            <div class="reports-topbar">
                <div class="reports-topbar-spacer"></div>

                <h1 class="reports-page-title">Reports</h1>

                <button class="manager-profile" type="button" id="openProfileModal" aria-label="Open profile">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <circle cx="12" cy="8" r="4"></circle>
                        <path d="M4 20c1.8-3.8 5-5.5 8-5.5S18.2 16.2 20 20"></path>
                    </svg>
                </button>
            </div>

            <div class="reports-divider"></div>

            <section class="reports-toolbar">
                <div class="reports-filter-wrap">
                    <select id="reportTypeFilter" class="reports-filter-select">
                        <option value="Population">Population</option>
                        <option value="Environmental">Environmental</option>
                        <option value="Inventory">Inventory</option>
                        <option value="Biosecurity">Biosecurity</option>
                        <option value="Weight Sampling">Weight Sampling</option>
                        <option value="Tasks">Tasks</option>
                    </select>
                </div>

                <button type="button" class="reports-generate-btn" id="openGenerateReportModal">
                    <span>Generate Report</span>
                    <span class="reports-generate-plus">+</span>
                </button>
            </section>

            <section class="reports-content-shell">
                <div id="reportContent"></div>
            </section>
        </main>
    </div>
@endsection