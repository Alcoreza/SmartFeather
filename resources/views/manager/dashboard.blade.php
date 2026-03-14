@extends('layouts.app')

@section('title', 'Manager Dashboard')

@push('styles')
    @vite([
        'resources/css/manager-shared.css',
        'resources/css/manager-dashboard.css'
    ])
@endpush


@push('scripts')
    @vite('resources/js/manager-dashboard.js')
@endpush

@section('content')
    @php
        $overviewCards = [
            ['icon' => '🐔', 'value' => '5462', 'label' => 'Total Birds', 'accent' => 'red'],
            ['icon' => '🥚', 'value' => '367', 'label' => 'Total Eggs', 'accent' => 'orange'],
            ['icon' => '📉', 'value' => '25', 'label' => 'Mortalities', 'accent' => 'gray'],
        ];
    @endphp

    <div class="manager-shell">
        @include('includes.manager-sidebar')

        <main class="manager-main">
            @include('includes.manager-topbar')
            @include('includes.manager-profile-modal')


            <section class="dashboard-card dashboard-overview">
                <div class="section-heading">
                    <h2>Daily Farm Overview</h2>
                </div>

                <div class="overview-grid">
                    @foreach ($overviewCards as $card)
                        <article class="overview-item">
                            <div class="overview-icon {{ $card['accent'] }}">
                                <span>{{ $card['icon'] }}</span>
                            </div>
                            <div>
                                <div class="overview-value">{{ $card['value'] }}</div>
                                <div class="overview-label">{{ $card['label'] }}</div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="dashboard-panels">
                <article class="dashboard-card panel-card">
                    <div class="section-heading">
                        <h2>Environmental Monitoring</h2>
                    </div>

                    <div class="env-realtime-grid" id="environmentGrid">
                        <div class="sensor-card">
                            <div class="radial-gauge safe" style="--gauge-value: 172.8deg;">
                                <div class="radial-gauge-inner">
                                    <span class="sensor-value">24deg</span>
                                </div>
                            </div>
                            <div class="sensor-label">Temperature</div>
                        </div>

                        <div class="panel-divider"></div>

                        <div class="sensor-card">
                            <div class="radial-gauge warning" style="--gauge-value: 108deg;">
                                <div class="radial-gauge-inner">
                                    <span class="sensor-value">15ppm</span>
                                </div>
                            </div>
                            <div class="sensor-label">Ammonia</div>
                        </div>
                    </div>
                </article>

                <article class="dashboard-card panel-card">
                    <div class="section-heading">
                        <h2>Feed and Water Monitoring</h2>
                    </div>

                    <div class="resource-grid" id="resourceGrid">
                        <div class="resource-card">
                            <div class="resource-bar-shell">
                                <div class="resource-bar feed-bar" style="height: 60%;"></div>
                            </div>
                            <div class="resource-percent">60%</div>
                            <div class="resource-label">Feed</div>
                        </div>

                        <div class="panel-divider"></div>

                        <div class="resource-card">
                            <div class="resource-bar-shell">
                                <div class="resource-bar water-bar" style="height: 30%;"></div>
                            </div>
                            <div class="resource-percent water-text">30%</div>
                            <div class="resource-label">Water</div>
                        </div>
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

                    <div class="graph-dots">
                        <button type="button" class="graph-dot active" data-slide="0"></button>
                        <button type="button" class="graph-dot" data-slide="1"></button>
                    </div>
                </article>




                <article class="dashboard-card decision-card">
                    <div class="decision-header">
                        <h2>Decision Support</h2>

                        <select class="decision-select">
                            <option>Mortality</option>
                            <option>Temperature</option>
                            <option>Water</option>
                        </select>
                    </div>

                    <div class="decision-content">
                        <div class="decision-stars">
                            <span class="star star-lg"></span>
                            <span class="star star-md"></span>
                            <span class="star star-sm"></span>
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