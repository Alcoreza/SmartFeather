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

    <div class="manager-shell">
        @include('includes.manager-sidebar')

        <main class="manager-main">
            @include('includes.manager-add-house-modal')
            @include('includes.manager-edit-house-modal')
            @include('includes.manager-end-batch-modal')

            <section class="houses-page">
                <div class="houses-header">
                    <h1>House Data</h1>
                </div>

                <div class="houses-tabs">
                    <button type="button" class="add-house-btn" id="openAddHouseModal">+</button>
                </div>

                <div class="houses-divider"></div>

                <div class="houses-toolbar">
                    <div class="toolbar-left">
                        <span class="chip chip-gray" id="houseStatus">Loading...</span>
                        <span class="chip chip-yellow" id="houseBatch">Loading...</span>

                        <select class="pen-select" id="housePen">
                            <option value="">Loading pens...</option>
                        </select>
                    </div>

                    <div class="toolbar-right">
                        <button type="button" class="toolbar-btn btn-edit" id="openEditHouseModal">✎ Edit</button>
                        <button type="button" class="toolbar-btn btn-end" id="openEndBatchModal">End Batch</button>
                        <a href="{{ route('manager.houses.record-house') }}" class="toolbar-btn btn-file" title="Records">
                            Records
                        </a>
                    </div>
                </div>

                <div class="houses-top-grid">
                        <article class="monitor-card env-card">
                            <h2>Environmental Monitoring</h2>

                            <div class="gauge-group">
                                <div class="gauge-item">
                                    <div class="semi-gauge">
                                        <div class="semi-gauge-inner" id="houseTemperature">--</div>
                                    </div>
                                    <span class="gauge-label temperature-label">Temperature</span>
                                </div>

                                <div class="inner-divider"></div>

                                <div class="gauge-item">
                                    <div class="semi-gauge">
                                        <div class="semi-gauge-inner" id="houseAmmonia">--</div>
                                    </div>
                                    <span class="gauge-label ammonia-label">Ammonia</span>
                                </div>
                            </div>
                        </article>

                        <div class="info-grid" id="infoGrid"></div>
                    </div>
                <section class="resource-section">
                    <h2>Feed Monitoring</h2>

                    <div class="resource-row" id="feedRow"></div>
                </section>

                <section class="resource-section">
                    <h2>Water Monitoring</h2>

                    <div class="resource-row" id="waterRow"></div>
                </section>
            </section>
        </main>
    </div>

    {{-- House data is now fetched from /api/houses endpoint --}}
@endsection
