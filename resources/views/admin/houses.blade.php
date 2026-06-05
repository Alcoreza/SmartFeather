@extends('layouts.app')

@section('title', 'Admin House Data')

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
                        <a href="{{ route('admin.houses.record-house') }}"
                        class="toolbar-btn btn-file"
                        title="Flock Batch Records">
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
@endsection

@push('scripts')
    {{-- House data is now fetched from /api/houses endpoint --}}
@endpush
