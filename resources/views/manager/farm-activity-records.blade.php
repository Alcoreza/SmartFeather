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

            <section class="reports-toolbar farm-records-toolbar">
                <div class="reports-filter-wrap">
                    <select id="farmRecordTypeFilter" class="reports-filter-select">
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
            </section>

            <section class="reports-content-shell">
                <div id="farmRecordContent"></div>
            </section>
        </main>
    </div>
@endsection
