@extends('layouts.app')

@section('title', 'Manager Inventory')

@push('styles')
    @vite([
        'resources/css/manager-shared.css',
        'resources/css/manager-inventory.css'
    ])
@endpush

@push('scripts')
    @vite('resources/js/manager-inventory.js')
@endpush

@section('content')
<div class="manager-shell">
    @include('includes.manager-sidebar')
    @include('includes.manager-profile-modal')

    <main class="manager-main inventory-main">

        <div class="inventory-topbar">
            <h1 class="inventory-title">Inventory</h1>
            <div class="inventory-topbar-actions"></div>
        </div>

        <div class="inventory-divider"></div>

        <!-- Feed Stock Section -->
        <section class="inventory-section">

            <div class="inventory-section-head">

                <h2>Feed Stock</h2>

                <div class="inventory-actions">

                    <a
                        href="{{ route('manager.inventory.records') }}"
                        class="inventory-rec-btn"
                        aria-label="Stock Transaction History"
                    >
                        Stock Transaction History
                    </a>

                    <button
                        type="button"
                        class="inventory-rec-btn"
                        id="openArchiveInventoryModal"
                    >
                        Archive
                    </button>

                    <button
                        type="button"
                        class="inventory-edit-btn"
                        id="openEditStockModal"
                        aria-label="Edit Stock"
                    >
                        ✎ Edit Stock
                    </button>

                    <button
                        type="button"
                        class="inventory-icon-btn add-btn"
                        id="openCreateInventoryTypeModal"
                        aria-label="Add Inventory"
                        title="Add Inventory"
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
                            <path d="M12 5v14"></path>
                            <path d="M5 12h14"></path>
                        </svg>
                    </button>

                </div>

            </div>

            <div class="inventory-list" id="feedInventoryList">

                @foreach ($feedItems as $item)

                    <div
                        class="inventory-entry inventory-feed-entry"
                        data-id="{{ $item['id'] }}"
                        data-item-name="{{ $item['item_name'] }}"
                        data-initial-stock="{{ $item['initial_stock'] }}"
                        data-remaining-stock="{{ $item['remaining_stock'] }}"
                        data-critical="{{ $item['critical'] }}"
                        data-unit="{{ $item['unit'] }}"
                    >

                        <div class="inventory-card inventory-stock-card">

                            <div class="inventory-stock-card-header">
                                <h3 class="inventory-item-name">
                                    {{ $item['item_name'] }}
                                </h3>
                            </div>

                            <div class="inventory-stock-card-body">

                                <div class="inventory-stock-summary">
                                    <div class="inventory-stock-amount">
                                        {{ $item['remaining_stock'] }} <span class="inventory-stock-unit">{{ $item['unit'] === 'bottles' ? 'btls' : $item['unit'] }}</span>
                                    </div>
                                    <div class="inventory-stock-subtitle">Remaining</div>
                                </div>

                                <div class="inventory-stock-details">

                                    <p>
                                        <strong>Critical Level:</strong>
                                        <span class="inventory-stock-detail-value">{{ number_format($item['critical'], 0, '.', '') }} <span class="inventory-stock-detail-unit">{{ $item['unit'] === 'bottles' ? 'btls' : $item['unit'] }}</span></span>
                                    </p>

                                </div>

                                <div class="inventory-stock-progress">
                                    <div class="inventory-progress-track {{ $item['status_class'] }}" style="--percent: {{ $item['percentage'] }};">
                                        <div class="inventory-progress-fill"></div>
                                        <div class="inventory-progress-label">
                                            {{ $item['status'] }}
                                        </div>
                                    </div>
                                </div>

                            </div>

                        </div>

                    </div>

                @endforeach

            </div>

        </section>

        <!-- Vitamins Stock Section -->
        <section class="inventory-section vitamins-section">

            <div class="inventory-section-head">

                <h2>Vitamins Stock</h2>


            </div>

            <div class="inventory-list vitamins-list" id="vitaminInventoryList">

                @foreach ($vitaminItems as $item)

                    <div
                        class="inventory-entry inventory-vitamin-entry"
                        data-id="{{ $item['id'] }}"
                        data-item-name="{{ $item['item_name'] }}"
                        data-initial-stock="{{ $item['initial_stock'] }}"
                        data-remaining-stock="{{ $item['remaining_stock'] }}"
                        data-critical="{{ $item['critical'] }}"
                        data-unit="{{ $item['unit'] }}"
                    >

                        <div class="inventory-card inventory-stock-card">

                            <div class="inventory-stock-card-header">
                                <h3 class="inventory-item-name">
                                    {{ $item['item_name'] }}
                                </h3>
                            </div>

                            <div class="inventory-stock-card-body">

                                <div class="inventory-stock-summary">
                                    <div class="inventory-stock-amount">
                                        {{ $item['remaining_stock'] }} <span class="inventory-stock-unit">btls</span>
                                    </div>
                                    <div class="inventory-stock-subtitle">Remaining</div>
                                </div>

                                <div class="inventory-stock-details">

                                    <p>
                                        <strong>Critical Level:</strong>
                                        <span class="inventory-stock-detail-value">{{ number_format($item['critical'], 0, '.', '') }} <span class="inventory-stock-detail-unit">btls</span></span>
                                    </p>

                                </div>

                                <div class="inventory-stock-progress">
                                    <div class="inventory-progress-track {{ $item['status_class'] }}" style="--percent: {{ $item['percentage'] }};">
                                        <div class="inventory-progress-fill"></div>
                                        <div class="inventory-progress-label">
                                            {{ $item['status'] }}
                                        </div>
                                    </div>
                                </div>

                            </div>

                        </div>

                    </div>

                @endforeach

            </div>

        </section>

    </main>

</div>

@include('includes.manager-edit-stock-modal')
@include('includes.manager-archive-inventory-modal')
@include('includes.manager-create-inventory-modal')
@include('includes.manager-profile-modal')

@endsection
