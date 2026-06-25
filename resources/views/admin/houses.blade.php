@extends('layouts.app')

@section('title', 'Admin Farm Management')

@push('styles')
    @vite([
        'resources/css/admin-shared.css',
        'resources/css/admin-houses.css'
    ])
@endpush

@push('scripts')
    @vite('resources/js/admin-houses.js')
@endpush

@section('content')
    <div class="admin-shell">
        @include('includes.admin-sidebar')

        <main class="admin-main">
            @include('includes.admin-profile-modal')
            @include('includes.admin-add-house-modal')
            @include('includes.admin-edit-house-modal')
            @include('includes.admin-end-batch-modal')
            @include('includes.admin-archive-house-modal')

            <section class="houses-page">
                <div class="houses-header">
                    <div>
                        <h1>Farm Management</h1>
                    </div>
                </div>

                <div class="houses-tabs">
                    <button type="button" class="add-house-btn" id="openAddHouseModal" aria-label="Add house">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
                            <path d="M12 5v14"></path>
                            <path d="M5 12h14"></path>
                        </svg>
                    </button>
                </div>

                <div class="houses-divider"></div>

                <div class="houses-workspace">
                    <div class="houses-main-column">
                        <article class="farm-panel info-panel">
                            <div class="section-heading">
                                <h2>Selected Pen Details</h2>
                            </div>

                            <div class="info-grid" id="infoGrid"></div>
                        </article>

                        <article class="farm-panel monitor-card env-card">
                            <div class="section-heading">
                                <h2>Environment Readings</h2>
                            </div>

                            <div class="sensor-chart-grid">
                                <div class="sensor-chart-wrap temperature-chart">
                                    <div class="sensor-stat temperature-stat">
                                        <span class="sensor-kicker">Temperature</span>
                                        <span class="sensor-reading-value" id="houseTemperature">--</span>
                                        <span class="sensor-helper">Current pen reading</span>
                                    </div>
                                </div>

                                <div class="sensor-chart-wrap ammonia-chart">
                                    <div class="sensor-stat ammonia-stat">
                                        <span class="sensor-kicker">Ammonia</span>
                                        <span class="sensor-reading-value" id="houseAmmonia">--</span>
                                        <span class="sensor-helper">Current pen reading</span>
                                    </div>
                                </div>
                            </div>
                        </article>

                        <div class="resource-grid">
                            <section class="resource-section">
                                <h2>Feeder Levels</h2>

                                <div class="resource-row" id="feedRow"></div>
                            </section>

                            <section class="resource-section">
                                <h2>Drinker Levels</h2>

                                <div class="resource-row" id="waterRow"></div>
                            </section>
                        </div>
                    </div>

                    <aside class="houses-side-column" id="flockBatchPanel">
                        <article class="farm-panel cycle-panel">
                            <div class="section-heading">
                                <h2>Flock Batch</h2>
                            </div>

                            <div class="cycle-stack">
                                <div class="status-card">
                                    <span class="status-label">Pen Status</span>
                                    <span class="chip chip-gray" id="houseStatus">Loading...</span>
                                </div>

                                <div class="status-card">
                                    <span class="status-label">Batch ID</span>
                                    <span class="chip chip-yellow" id="houseBatch">Loading...</span>
                                </div>

                                <div class="status-card">
                                    <span class="status-label">Selected Pen</span>
                                    <select class="pen-select" id="housePen">
                                        <option value="">Loading pens...</option>
                                    </select>
                                </div>
                            </div>

                            <div class="houses-actions">
                                <button type="button" class="toolbar-btn btn-edit" id="openEditHouseModal">Edit</button>
                                <a href="{{ route('admin.houses.record-house') }}" class="toolbar-btn btn-file" title="Flock Batch Records">
                                    Records
                                </a>
                                <button type="button" class="toolbar-btn btn-archive" id="openArchiveHouseModal">Archive</button>
                                <button type="button" class="toolbar-btn btn-end" id="openEndBatchModal">End Batch</button>
                            </div>
                        </article>
                    </aside>
                </div>

                <button type="button" class="flock-batch-float-btn" data-flock-batch-toggle aria-controls="flockBatchPanel" aria-expanded="false">
                    <svg class="flock-batch-float-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path d="M8 5.5h8" />
                        <path d="M8 12h8" />
                        <path d="M8 18.5h8" />
                        <path d="M4.5 5.5h.01" />
                        <path d="M4.5 12h.01" />
                        <path d="M4.5 18.5h.01" />
                    </svg>
                    <strong>Flock Batch</strong>
                </button>
            </section>
        </main>
    </div>
@endsection

@push('scripts')
    {{-- House data is now fetched from /api/houses endpoint --}}
@endpush
