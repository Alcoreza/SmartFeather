@extends('layouts.app')

@section('title', 'Manager Dashboard')

@push('styles')
    @vite([
        'resources/css/manager-shared.css',
        'resources/css/manager-dashboard.css',
        'resources/css/manager-decision-support.css'
    ])
@endpush


@push('scripts')
    @vite('resources/js/manager-dashboard.js')
    @vite('resources/js/manager-decision-support.js')
@endpush

@section('content')
    <div class="manager-shell">
        @include('includes.manager-sidebar')

        <main class="manager-main">
            @include('includes.manager-topbar')
            @include('includes.manager-profile-modal')


            <section class="dashboard-command-grid">
                <section class="dashboard-card dashboard-overview">
                    <div class="section-heading">
                        <h2>Daily Farm Overview</h2>
                    </div>

                    <div class="overview-grid">
                        @foreach ($overviewCards as $card)
                            <article class="overview-item">
                                <div class="overview-icon {{ $card['accent'] }}">
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
                                    <div class="overview-value">{{ $card['value'] }}</div>
                                    <div class="overview-label">{{ $card['label'] }}</div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>

                <article class="dashboard-card decision-card">
                    <div class="decision-header">
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

                    <div class="decision-content" id="decisionSupportContent">
                        <div class="decision-support-loading">
                            <div class="spinner"></div>
                            <p>Loading recommendations...</p>
                        </div>
                    </div>
                </article>
            </section>

            <section class="dashboard-panels">
                <article class="dashboard-card panel-card">
                    <div class="section-heading">
                        <h2>Environmental Monitoring</h2>
                    </div>

                    <div class="env-chart-area">
                        <canvas id="environmentChart"></canvas>
                    </div>

                    <div class="graph-caption" id="envGraphCaption">Temperature</div>

                    <div class="graph-dots">
                        <button type="button" class="graph-dot env-dot active" data-slide="0"></button>
                        <button type="button" class="graph-dot env-dot" data-slide="1"></button>
                    </div>
                </article>

                <article class="dashboard-card panel-card">
                    <div class="section-heading">
                        <h2>Feed and Water Monitoring</h2>
                    </div>

                    <div class="resource-chart-area">
                        <canvas id="resourceChart"></canvas>
                    </div>

                    <div class="graph-caption" id="resourceGraphCaption">Feed</div>

                    <div class="graph-dots">
                        <button type="button" class="graph-dot resource-dot active" data-slide="0"></button>
                        <button type="button" class="graph-dot resource-dot" data-slide="1"></button>
                    </div>
                </article>
            </section>



            <div class="dashboard-separator"></div>

            <section class="dashboard-bottom">
                <article class="dashboard-card graph-card">
                    <div class="section-heading centered">
                        <h2>Monitoring Graphs</h2>
                    </div>

                    <div class="graph-area">
                        <canvas id="monitoringChart"></canvas>
                    </div>

                    <div class="graph-caption" id="graphCaption">Temperature</div>

                    <div class="house-graph-pager" id="houseGraphPager" hidden>
                        <button type="button" class="house-graph-page-btn" id="houseGraphPrev">Prev</button>
                        <div class="house-graph-page-dots" id="houseGraphPageDots"></div>
                        <button type="button" class="house-graph-page-btn" id="houseGraphNext">Next</button>
                    </div>

                    <div class="graph-dots">
                        <button type="button" class="graph-dot active" data-slide="0"></button>
                        <button type="button" class="graph-dot" data-slide="1"></button>
                    </div>
                </article>




            </section>
        </main>
    </div>
@endsection
