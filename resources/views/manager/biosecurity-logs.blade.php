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
                        <div class="bio-stat-icon personnel" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9.5" cy="7" r="4"></circle>
                                <path d="m16 11 2 2 4-4"></path>
                            </svg>
                        </div>

                        <div class="bio-stat-content">
                            <div class="bio-stat-label">Personnel Entered</div>
                            <div class="bio-stat-value" id="bioPersonnelEntered">0</div>
                        </div>
                    </article>

                    <article class="bio-stat-card">
                        <div class="bio-stat-icon visitor" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="m17 11 2 2 4-4"></path>
                            </svg>
                        </div>

                        <div class="bio-stat-content">
                            <div class="bio-stat-label">Visitors Entered</div>
                            <div class="bio-stat-value" id="bioVisitorsEntered">0</div>
                        </div>
                    </article>
                </div>
            </section>

            <div class="bio-divider"></div>

            <section class="bio-toolbar">
                <div class="bio-toolbar-left">
                    <div class="bio-category-buttons">
                        <button 
                            type="button" 
                            class="bio-category-btn active" 
                            data-category="Personnel Biosecurity Logs"
                            title="View personnel logs"
                        >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            Personnel
                        </button>
                        <button 
                            type="button" 
                            class="bio-category-btn" 
                            data-category="Visitors"
                            title="View visitor logs"
                        >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                            Visitors
                        </button>
                    </div>

                    <div class="bio-filters-container">
                        <div class="bio-search-wrap">
                            <svg class="bio-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.35-4.35"></path>
                            </svg>
                            <input 
                                type="text" 
                                id="bioNameSearch" 
                                class="bio-search-input" 
                                placeholder="Search by name..."
                            >
                        </div>

                        <div class="bio-date-filter-wrap">
                            <div class="bio-date-group">
                                <label class="bio-date-label">From:</label>
                                <input 
                                    type="date" 
                                    id="bioDateFrom" 
                                    class="bio-date-input" 
                                    title="Start date"
                                >
                            </div>
                            <div class="bio-date-group">
                                <label class="bio-date-label">To:</label>
                                <input 
                                    type="date" 
                                    id="bioDateTo" 
                                    class="bio-date-input" 
                                    title="End date"
                                >
                            </div>
                        </div>
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

                <div class="bio-pagination" data-bio-pagination>
                    <button type="button" class="bio-page-btn" data-bio-prev>
                        Previous
                    </button>
                    <div class="bio-page-dots" data-bio-dots></div>
                    <button type="button" class="bio-page-btn" data-bio-next>
                        Next
                    </button>
                </div>
            </section>

            @include('includes.manager-edit-biosecurity-logs')
            @include('includes.manager-add-biosecurity-logs')
        </main>
    </div>
@endsection
