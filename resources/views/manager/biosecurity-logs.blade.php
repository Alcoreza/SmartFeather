@extends('layouts.app')

@section('title', 'Biosecurity Logs')

@push('styles')
    @vite([
        'resources/css/manager-shared.css',
        'resources/css/manager-biosecurity-logs.css'
    ])
@endpush

@push('scripts')
    @vite('resources/js/manager-biosecurity-logs.js')
@endpush

@section('content')
    <div class="manager-shell">
        @include('includes.manager-sidebar')
        @include('includes.manager-profile-modal')

        <main class="manager-main bio-page">
            <div class="bio-topbar">
                <div class="bio-topbar-spacer"></div>

                <h1 class="bio-page-title">Biosecurity</h1>
            </div>

            <section class="bio-overview-panel">
                <h2 class="bio-overview-title">Daily Overview</h2>

                <div class="bio-overview-grid">
                    <article class="bio-stat-card">
                        <div class="bio-stat-icon">
                            <img src="{{ asset('images/biosecurity/violation.png') }}" alt="Violation Icon">
                        </div>

                        <div class="bio-stat-content">
                            <div class="bio-stat-label">Violations:</div>
                            <div class="bio-stat-value" id="bioViolations">2</div>
                        </div>
                    </article>

                    <article class="bio-stat-card">
                        <div class="bio-stat-icon">
                            <img src="{{ asset('images/biosecurity/disinfection.png') }}" alt="Disinfection Icon">
                        </div>

                        <div class="bio-stat-content stacked">
                            <div class="bio-stat-label">Last Disinfection</div>
                            <div class="bio-mini-text" id="bioDisinfectionDate">1-21-26</div>
                            <div class="bio-mini-text" id="bioDisinfectionTime">11:58 AM</div>
                        </div>
                    </article>

                    <article class="bio-stat-card">
                        <div class="bio-stat-icon">
                            <img src="{{ asset('images/biosecurity/visitor.png') }}" alt="Visitor Icon">
                        </div>

                        <div class="bio-stat-content">
                            <div class="bio-stat-label">Visitors:</div>
                            <div class="bio-stat-value" id="bioVisitors">1</div>
                        </div>
                    </article>

                    <article class="bio-stat-card">
                        <div class="bio-stat-icon">
                            <img src="{{ asset('images/biosecurity/mortality.png') }}" alt="Mortality Icon">
                        </div>

                        <div class="bio-stat-content">
                            <div class="bio-stat-label">Mortalities:</div>
                            <div class="bio-stat-value" id="bioMortalities">1</div>
                        </div>
                    </article>
                </div>
            </section>

            <div class="bio-divider"></div>

            <section class="bio-toolbar">
                <div class="bio-toolbar-left">
                    <div class="bio-filter-wrap">
                        <select id="bioCategoryFilter" class="bio-filter-select">
                            <option value="Personnel Biosecurity Logs" selected>Personnel Biosecurity Logs</option>
                            <option value="Visitors">Visitors</option>
                        </select>
                    </div>
                </div>

                <button type="button" class="bio-circle-btn add" id="openAddBioModal" aria-label="Add log">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6">
                        <path d="M12 5v14"></path>
                        <path d="M5 12h14"></path>
                    </svg>
                </button>
            </section>

            <section class="bio-table-card">
                <div class="bio-table-wrap">
                    <table class="bio-table">
                        <thead id="bioTableHead"></thead>
                        <tbody id="bioLogsTableBody"></tbody>
                    </table>
                </div>
            </section>

            @include('includes.manager-edit-biosecurity-logs')
            @include('includes.manager-add-biosecurity-logs')
        </main>
    </div>
@endsection