@extends('layouts.app')

@section('title', 'Admin Dashboard')

@push('styles')
    @vite([
        'resources/css/admin-shared.css',
        'resources/css/admin-dashboard.css'
    ])
@endpush

@push('scripts')
    @vite('resources/js/admin-dashboard.js')
@endpush

@section('content')
    @php
        $overviewCards = [
            ['icon' => '🐔', 'value' => '5462', 'label' => 'Total Birds', 'accent' => 'red'],
            ['icon' => '🥚', 'value' => '367', 'label' => 'Total Eggs', 'accent' => 'orange'],
            ['icon' => '📉', 'value' => '25', 'label' => 'Mortalities', 'accent' => 'gray'],
        ];
    @endphp

    <div class="admin-shell">
        @include('includes.admin-sidebar')

        <main class="admin-main">
            <div class="admin-topbar">
                <div>
                    <h1 class="admin-page-title">Dashboard</h1>
                    <p class="admin-page-subtitle">Monitor operations, live farm conditions, and critical alerts.</p>
                </div>
            </div>

            @include('includes.admin-profile-modal')

            <section class="admin-dashboard-card admin-overview-card">
                <div class="admin-section-heading">
                    <h2>Daily Farm Overview</h2>
                </div>

                <div class="admin-overview-grid">
                    @foreach ($overviewCards as $card)
                        <article class="admin-overview-item">
                            <div class="admin-overview-icon {{ $card['accent'] }}">
                                <span>{{ $card['icon'] }}</span>
                            </div>

                            <div>
                                <div class="admin-overview-value">{{ $card['value'] }}</div>
                                <div class="admin-overview-label">{{ $card['label'] }}</div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="admin-dashboard-panels">
                <article class="admin-dashboard-card admin-panel-card">
                    <div class="admin-section-heading">
                        <h2>Environmental Monitoring</h2>
                    </div>

                    <div class="admin-environment-grid" id="adminEnvironmentGrid">
                        <div class="admin-sensor-card">
                            <div class="admin-radial-gauge safe" style="--gauge-value: 172deg;">
                                <div class="admin-radial-gauge-inner">
                                    <span class="admin-sensor-value">24deg</span>
                                </div>
                            </div>
                            <div class="admin-sensor-label">Temperature</div>
                        </div>

                        <div class="admin-panel-divider"></div>

                        <div class="admin-sensor-card">
                            <div class="admin-radial-gauge warning" style="--gauge-value: 108deg;">
                                <div class="admin-radial-gauge-inner">
                                    <span class="admin-sensor-value">15ppm</span>
                                </div>
                            </div>
                            <div class="admin-sensor-label">Ammonia</div>
                        </div>
                    </div>
                </article>

                <article class="admin-dashboard-card admin-panel-card">
                    <div class="admin-section-heading">
                        <h2>Feed and Water Monitoring</h2>
                    </div>

                    <div class="admin-resource-grid" id="adminResourceGrid">
                        <div class="admin-resource-card">
                            <div class="admin-resource-bar-shell">
                                <div class="admin-resource-bar admin-feed-bar" style="height: 60%;"></div>
                            </div>
                            <div class="admin-resource-percent">60%</div>
                            <div class="admin-resource-label">Feed</div>
                        </div>

                        <div class="admin-panel-divider"></div>

                        <div class="admin-resource-card">
                            <div class="admin-resource-bar-shell">
                                <div class="admin-resource-bar admin-water-bar" style="height: 30%;"></div>
                            </div>
                            <div class="admin-resource-percent admin-water-text">30%</div>
                            <div class="admin-resource-label">Water</div>
                        </div>
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

                    <div class="admin-graph-dots">
                        <button type="button" class="admin-graph-dot active" data-slide="0"></button>
                        <button type="button" class="admin-graph-dot" data-slide="1"></button>
                    </div>
                </article>

                <article class="admin-dashboard-card admin-decision-card">
                    <div class="admin-decision-header">
                        <h2>Decision Support</h2>

                        <select class="admin-decision-select">
                            <option>Mortality</option>
                            <option>Temperature</option>
                            <option>Water</option>
                        </select>
                    </div>

                    <div class="admin-decision-content">
                        <div class="admin-decision-stars">
                            <span class="admin-star admin-star-lg"></span>
                            <span class="admin-star admin-star-md"></span>
                            <span class="admin-star admin-star-sm"></span>
                        </div>

                        <p>
                            Mortality count has increased beyond the normal daily range.
                            Conduct flock inspection, review environmental conditions,
                            and verify feed and water availability to identify possible causes.
                        </p>
                    </div>
                </article>
            </section>
        </main>
    </div>
@endsection