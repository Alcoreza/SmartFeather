@extends('layouts.app')

@section('title', 'Admin Dashboard')

@push('styles')
    @vite([
        'resources/css/admin-shared.css',
        'resources/css/admin-dashboard.css',
        'resources/css/manager-decision-support.css'
    ])
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @vite('resources/js/admin-dashboard.js')
    @vite('resources/js/manager-decision-support.js')
@endpush

@section('content')
    <div class="admin-shell">
        @include('includes.admin-sidebar')

        <main class="admin-main">
            <div class="admin-topbar">
                <div>
                    <h1 class="admin-page-title">Dashboard</h1>
                </div>
            </div>

            @include('includes.admin-profile-modal')

            <section class="admin-dashboard-command-grid">
                <section class="admin-dashboard-card admin-overview-card">
                    <div class="admin-section-heading">
                        <h2>Daily Farm Overview</h2>
                    </div>

                    <div class="admin-overview-grid">
                        @foreach ($overviewCards as $card)
                            <article class="admin-overview-item">
                                <div class="admin-overview-icon {{ $card['accent'] }}">
                                    @if ($card['label'] === 'Total Chickens')
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M6 14c0-4.4 3.2-8 7.2-8 2.4 0 4.5 1.3 5.8 3.3"></path>
                                            <path d="M5 14.5c-1.2.3-2 .9-2 1.7 0 1.2 1.9 2.2 4.3 2.2h7.2c3.1 0 5.5-2.1 5.5-4.8 0-1.9-1.2-3.6-3-4.4"></path>
                                            <path d="M10 9.5h.01"></path>
                                            <path d="M8 18.5 6.5 21"></path>
                                            <path d="M14 18.5 15.5 21"></path>
                                        </svg>
                                    @elseif ($card['label'] === 'Total Eggs')
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M12 3c4 0 7 5 7 10.2 0 4.5-2.8 7.8-7 7.8s-7-3.3-7-7.8C5 8 8 3 12 3Z"></path>
                                            <path d="M9.5 12.5c.5-1.8 1.6-3.2 2.5-4"></path>
                                        </svg>
                                    @else
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="m4 7 6 6 4-4 6 6"></path>
                                            <path d="M20 10v5h-5"></path>
                                            <path d="M6 20h12"></path>
                                        </svg>
                                    @endif
                                </div>

                                <div>
                                    <div class="admin-overview-value">{{ $card['value'] }}</div>
                                    <div class="admin-overview-label">{{ $card['label'] }}</div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>

                <article class="admin-dashboard-card admin-decision-card decision-card">
                    <div class="admin-decision-header decision-header">
                        <h2>Decision Support</h2>

                        <button type="button" class="decision-support-refresh" id="refreshDecisionSupport" title="Refresh recommendations">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M20 12a8 8 0 0 1-13.7 5.7"></path>
                                <path d="M4 12A8 8 0 0 1 17.7 6.3"></path>
                                <path d="M7 18H4v3"></path>
                                <path d="M17 6h3V3"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="admin-decision-content decision-content" id="decisionSupportContent" data-decision-support-api="/api/admin/dashboard/decision-support">
                        <div class="decision-support-loading">
                            <div class="spinner"></div>
                            <p>Loading recommendations...</p>
                        </div>
                    </div>
                </article>
            </section>

            <section class="admin-dashboard-panels">
                <article class="admin-dashboard-card admin-panel-card">
                    <div class="admin-section-heading">
                        <h2>Environmental Monitoring</h2>
                    </div>

                    <div class="admin-graph-area">
                        <canvas id="adminEnvironmentChart"></canvas>
                    </div>

                    <div class="admin-graph-caption" id="adminEnvGraphCaption">Temperature</div>

                    <div class="admin-graph-dots">
                        <button type="button" class="admin-graph-dot admin-env-dot active" data-slide="0"></button>
                        <button type="button" class="admin-graph-dot admin-env-dot" data-slide="1"></button>
                    </div>
                </article>

                <article class="admin-dashboard-card admin-panel-card">
                    <div class="admin-section-heading">
                        <h2>Feed and Water Monitoring</h2>
                    </div>

                    <div class="admin-graph-area">
                        <canvas id="adminResourceChart"></canvas>
                    </div>

                    <div class="admin-graph-caption" id="adminResourceGraphCaption">Feed</div>

                    <div class="admin-graph-dots">
                        <button type="button" class="admin-graph-dot admin-resource-dot active" data-slide="0"></button>
                        <button type="button" class="admin-graph-dot admin-resource-dot" data-slide="1"></button>
                    </div>
                </article>
            </section>

            <div class="admin-dashboard-separator"></div>

            <section class="admin-dashboard-bottom">
                <article class="admin-dashboard-card admin-graph-card">
                    <div class="admin-section-heading centered">
                        <h2>Monitoring Graphs</h2>
                    </div>

                    <div class="admin-graph-area">
                        <canvas id="adminMonitoringChart"></canvas>
                    </div>

                    <div class="admin-graph-caption" id="adminGraphCaption">Temperature</div>

                    <div class="admin-house-graph-pager" id="adminHouseGraphPager" hidden>
                        <button type="button" class="admin-house-graph-page-btn" id="adminHouseGraphPrev">Prev</button>
                        <div class="admin-house-graph-page-dots" id="adminHouseGraphPageDots"></div>
                        <button type="button" class="admin-house-graph-page-btn" id="adminHouseGraphNext">Next</button>
                    </div>

                    <div class="admin-graph-dots">
                        <button type="button" class="admin-graph-dot admin-monitoring-dot active" data-slide="0"></button>
                        <button type="button" class="admin-graph-dot admin-monitoring-dot" data-slide="1"></button>
                    </div>
                </article>
            </section>
        </main>
    </div>
@endsection
