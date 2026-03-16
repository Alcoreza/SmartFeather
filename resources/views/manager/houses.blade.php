@extends('layouts.app')

@section('title', 'House Data')

@push('styles')
    @vite([
        'resources/css/manager-shared.css',
        'resources/css/manager-houses.css'
    ])
@endpush

@push('scripts')
    @vite('resources/js/manager-houses.js')
@endpush

@section('content')
    @php
        $houses = [
            [
                'name' => 'House 1',
                'status' => 'Active',
                'batch' => 'Batch-2026-02',
                'records' => [
                    [
                        'batch' => 'Batch-2026-01',
                        'date' => '2026-01-10',
                        'population' => '1500',
                        'condition' => 'Completed',
                    ],
                    [
                        'batch' => 'Batch-2026-02',
                        'date' => '2026-02-05',
                        'population' => '1429',
                        'condition' => 'Active',
                    ],
                ],
                'pens' => [
                    [
                        'name' => 'Pen 1',
                        'temperature' => '24 deg',
                        'ammonia' => '15 ppm',
                        'cards' => [
                            ['icon' => '🏠', 'title' => 'Capacity: 3000', 'subtitle' => 'Population: 1429', 'accent' => 'red'],
                            ['icon' => '📅', 'title' => 'Start Date', 'subtitle' => '1-21-2026', 'accent' => 'blue'],
                            ['icon' => '💚', 'title' => 'Current Condition', 'subtitle' => 'Normal', 'accent' => 'green'],
                            ['icon' => '📊', 'title' => 'Eggs Hatched: 105', 'subtitle' => 'Mortality: 20', 'accent' => 'orange'],
                        ],
                        'feeders' => [
                            ['label' => 'Feeder 1', 'value' => 40],
                            ['label' => 'Feeder 2', 'value' => 77],
                            ['label' => 'Feeder 3', 'value' => 20],
                        ],
                        'drinkers' => [
                            ['label' => 'Drinker 1', 'value' => 80],
                            ['label' => 'Drinker 2', 'value' => 22],
                            ['label' => 'Drinker 3', 'value' => 70],
                        ],
                    ],
                    [
                        'name' => 'Pen 2',
                        'temperature' => '25 deg',
                        'ammonia' => '12 ppm',
                        'cards' => [
                            ['icon' => '🏠', 'title' => 'Capacity: 3000', 'subtitle' => 'Population: 1510', 'accent' => 'red'],
                            ['icon' => '📅', 'title' => 'Start Date', 'subtitle' => '1-24-2026', 'accent' => 'blue'],
                            ['icon' => '💚', 'title' => 'Current Condition', 'subtitle' => 'Stable', 'accent' => 'green'],
                            ['icon' => '📊', 'title' => 'Eggs Hatched: 112', 'subtitle' => 'Mortality: 15', 'accent' => 'orange'],
                        ],
                        'feeders' => [
                            ['label' => 'Feeder 1', 'value' => 55],
                            ['label' => 'Feeder 2', 'value' => 60],
                            ['label' => 'Feeder 3', 'value' => 35],
                        ],
                        'drinkers' => [
                            ['label' => 'Drinker 1', 'value' => 76],
                            ['label' => 'Drinker 2', 'value' => 58],
                            ['label' => 'Drinker 3', 'value' => 64],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'House 2',
                'status' => 'Active',
                'batch' => 'Batch-2026-03',
                'records' => [
                    [
                        'batch' => 'Batch-2026-02',
                        'date' => '2026-02-15',
                        'population' => '1750',
                        'condition' => 'Completed',
                    ],
                    [
                        'batch' => 'Batch-2026-03',
                        'date' => '2026-03-01',
                        'population' => '1900',
                        'condition' => 'Active',
                    ],
                ],
                'pens' => [
                    [
                        'name' => 'Pen 1',
                        'temperature' => '26 deg',
                        'ammonia' => '11 ppm',
                        'cards' => [
                            ['icon' => '🏠', 'title' => 'Capacity: 2800', 'subtitle' => 'Population: 1900', 'accent' => 'red'],
                            ['icon' => '📅', 'title' => 'Start Date', 'subtitle' => '2-10-2026', 'accent' => 'blue'],
                            ['icon' => '💚', 'title' => 'Current Condition', 'subtitle' => 'Stable', 'accent' => 'green'],
                            ['icon' => '📊', 'title' => 'Eggs Hatched: 120', 'subtitle' => 'Mortality: 12', 'accent' => 'orange'],
                        ],
                        'feeders' => [
                            ['label' => 'Feeder 1', 'value' => 62],
                            ['label' => 'Feeder 2', 'value' => 55],
                            ['label' => 'Feeder 3', 'value' => 48],
                        ],
                        'drinkers' => [
                            ['label' => 'Drinker 1', 'value' => 74],
                            ['label' => 'Drinker 2', 'value' => 68],
                            ['label' => 'Drinker 3', 'value' => 59],
                        ],
                    ],
                    [
                        'name' => 'Pen 2',
                        'temperature' => '27 deg',
                        'ammonia' => '13 ppm',
                        'cards' => [
                            ['icon' => '🏠', 'title' => 'Capacity: 2800', 'subtitle' => 'Population: 1820', 'accent' => 'red'],
                            ['icon' => '📅', 'title' => 'Start Date', 'subtitle' => '2-12-2026', 'accent' => 'blue'],
                            ['icon' => '💚', 'title' => 'Current Condition', 'subtitle' => 'Normal', 'accent' => 'green'],
                            ['icon' => '📊', 'title' => 'Eggs Hatched: 116', 'subtitle' => 'Mortality: 10', 'accent' => 'orange'],
                        ],
                        'feeders' => [
                            ['label' => 'Feeder 1', 'value' => 50],
                            ['label' => 'Feeder 2', 'value' => 47],
                            ['label' => 'Feeder 3', 'value' => 39],
                        ],
                        'drinkers' => [
                            ['label' => 'Drinker 1', 'value' => 66],
                            ['label' => 'Drinker 2', 'value' => 62],
                            ['label' => 'Drinker 3', 'value' => 60],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'House 3',
                'status' => 'Inactive',
                'batch' => 'Batch-2026-04',
                'records' => [
                    [
                        'batch' => 'Batch-2026-03',
                        'date' => '2026-03-10',
                        'population' => '2050',
                        'condition' => 'Completed',
                    ],
                    [
                        'batch' => 'Batch-2026-04',
                        'date' => '2026-03-15',
                        'population' => '2100',
                        'condition' => 'Inactive',
                    ],
                ],
                'pens' => [
                    [
                        'name' => 'Pen 1',
                        'temperature' => '22 deg',
                        'ammonia' => '9 ppm',
                        'cards' => [
                            ['icon' => '🏠', 'title' => 'Capacity: 3200', 'subtitle' => 'Population: 2100', 'accent' => 'red'],
                            ['icon' => '📅', 'title' => 'Start Date', 'subtitle' => '3-05-2026', 'accent' => 'blue'],
                            ['icon' => '💚', 'title' => 'Current Condition', 'subtitle' => 'Good', 'accent' => 'green'],
                            ['icon' => '📊', 'title' => 'Eggs Hatched: 98', 'subtitle' => 'Mortality: 8', 'accent' => 'orange'],
                        ],
                        'feeders' => [
                            ['label' => 'Feeder 1', 'value' => 35],
                            ['label' => 'Feeder 2', 'value' => 44],
                            ['label' => 'Feeder 3', 'value' => 29],
                        ],
                        'drinkers' => [
                            ['label' => 'Drinker 1', 'value' => 65],
                            ['label' => 'Drinker 2', 'value' => 38],
                            ['label' => 'Drinker 3', 'value' => 51],
                        ],
                    ],
                    [
                        'name' => 'Pen 2',
                        'temperature' => '23 deg',
                        'ammonia' => '10 ppm',
                        'cards' => [
                            ['icon' => '🏠', 'title' => 'Capacity: 3200', 'subtitle' => 'Population: 1980', 'accent' => 'red'],
                            ['icon' => '📅', 'title' => 'Start Date', 'subtitle' => '3-08-2026', 'accent' => 'blue'],
                            ['icon' => '💚', 'title' => 'Current Condition', 'subtitle' => 'Good', 'accent' => 'green'],
                            ['icon' => '📊', 'title' => 'Eggs Hatched: 101', 'subtitle' => 'Mortality: 9', 'accent' => 'orange'],
                        ],
                        'feeders' => [
                            ['label' => 'Feeder 1', 'value' => 38],
                            ['label' => 'Feeder 2', 'value' => 40],
                            ['label' => 'Feeder 3', 'value' => 31],
                        ],
                        'drinkers' => [
                            ['label' => 'Drinker 1', 'value' => 69],
                            ['label' => 'Drinker 2', 'value' => 45],
                            ['label' => 'Drinker 3', 'value' => 56],
                        ],
                    ],
                ],
            ],
        ];
    @endphp

    <div class="manager-shell">
        @include('includes.manager-sidebar')

        <main class="manager-main">
            @include('includes.manager-add-house-modal')
            @include('includes.manager-edit-house-modal')

            <section class="houses-page">
                <div class="houses-header">
                    <h1>House Data</h1>
                </div>

                <div class="houses-tabs">
                    @foreach ($houses as $index => $house)
                        <button
                            type="button"
                            class="house-tab {{ $index === 0 ? 'active' : '' }}"
                            data-house-index="{{ $index }}"
                        >
                            {{ $house['name'] }}
                        </button>
                    @endforeach

                    <button type="button" class="add-house-btn" id="openAddHouseModal">+</button>
                </div>

                <div class="houses-divider"></div>

                <div class="houses-toolbar">
                    <div class="toolbar-left">
                        <span class="chip chip-green" id="houseStatus">{{ $houses[0]['status'] }}</span>
                        <span class="chip chip-yellow" id="houseBatch">{{ $houses[0]['batch'] }}</span>

                        <select class="pen-select" id="housePen">
                            @foreach ($houses[0]['pens'] as $penIndex => $pen)
                                <option value="{{ $penIndex }}">{{ $pen['name'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="toolbar-right">
                        <button type="button" class="toolbar-btn btn-edit" id="openEditHouseModal">✎ Edit</button>
                        <a href="{{ route('manager.houses.record-house') }}" class="toolbar-btn btn-file" title="Records">
                            Rec
                        </a>
                    </div>
                </div>

                <div class="houses-top-grid">
                    <article class="monitor-card env-card">
                        <h2>Environmental Monitoring</h2>

                        <div class="gauge-group">
                            <div class="gauge-item">
                                <div class="semi-gauge">
                                    <div class="semi-gauge-inner" id="houseTemperature">{{ $houses[0]['pens'][0]['temperature'] }}</div>
                                </div>
                            </div>

                            <div class="inner-divider"></div>

                            <div class="gauge-item">
                                <div class="semi-gauge">
                                    <div class="semi-gauge-inner" id="houseAmmonia">{{ $houses[0]['pens'][0]['ammonia'] }}</div>
                                </div>
                            </div>
                        </div>
                    </article>

                    <div class="info-grid" id="infoGrid">
                        @foreach ($houses[0]['pens'][0]['cards'] as $card)
                            <article class="info-card">
                                <div class="info-icon {{ $card['accent'] }}">
                                    <span>{{ $card['icon'] }}</span>
                                </div>

                                <div class="info-text">
                                    <div class="info-title">{{ $card['title'] }}</div>
                                    <div class="info-subtitle">{{ $card['subtitle'] }}</div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>

                <section class="resource-section">
                    <h2>Feed Monitoring</h2>

                    <div class="resource-row" id="feedRow">
                        @foreach ($houses[0]['pens'][0]['feeders'] as $item)
                            <div class="resource-item">
                                <div class="resource-bar-box">
                                    <div class="resource-bar feed-bar" style="width: {{ $item['value'] }}%;"></div>
                                </div>
                                <div class="resource-value feed-text">{{ $item['value'] }}%</div>
                                <div class="resource-label">{{ $item['label'] }}</div>
                            </div>

                            @if (!$loop->last)
                                <div class="resource-line"></div>
                            @endif
                        @endforeach
                    </div>
                </section>

                <section class="resource-section">
                    <h2>Water Monitoring</h2>

                    <div class="resource-row" id="waterRow">
                        @foreach ($houses[0]['pens'][0]['drinkers'] as $item)
                            <div class="resource-item">
                                <div class="resource-bar-box">
                                    <div class="resource-bar water-bar" style="width: {{ $item['value'] }}%;"></div>
                                </div>
                                <div class="resource-value water-text">{{ $item['value'] }}%</div>
                                <div class="resource-label">{{ $item['label'] }}</div>
                            </div>

                            @if (!$loop->last)
                                <div class="resource-line"></div>
                            @endif
                        @endforeach
                    </div>
                </section>
            </section>
        </main>
    </div>

    <script>
        window.houseData = @json($houses);
    </script>
@endsection