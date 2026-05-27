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
                        aria-label="Records"
                    >
                        Records
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
                        id="openFeedEditModal"
                        aria-label="Edit Feed"
                    >
                        ✎ Edit
                    </button>

                    <button
                        type="button"
                        class="inventory-icon-btn add-btn"
                        id="openFeedAddModal"
                        aria-label="Add Feed"
                    >
                        +
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
                        data-purchase-date="{{ $item['purchase_date'] }}"
                        data-unit="{{ $item['unit'] }}"
                    >

                        <div class="inventory-entry-header">

                            <h3 class="inventory-item-name">
                                {{ $item['item_name'] }}
                            </h3>

                        </div>

                        <div class="inventory-item-block">

                            <div class="inventory-card inventory-stock-card">

                                <div
                                    class="inventory-ring {{ $item['status_class'] }}"
                                    style="--percent: {{ $item['percentage'] }};"
                                >
                                    <div class="inventory-ring-inner"></div>
                                </div>

                                <div class="inventory-stock-details">

                                    <span class="inventory-status-badge {{ $item['status_class'] }}">
                                        {{ $item['status'] }}
                                    </span>

                                    <p>
                                        <strong>Initial Stock:</strong>
                                        {{ $item['initial_stock'] }} {{ $item['unit'] }}
                                    </p>

                                    <p>
                                        <strong>Remaining:</strong>
                                        {{ $item['remaining_stock'] }} {{ $item['unit'] }}
                                    </p>

                                    <p>
                                        <strong>Critical Level:</strong>
                                        {{ $item['critical'] }} {{ $item['unit'] }}
                                    </p>

                                </div>

                            </div>

                            <div class="inventory-card inventory-date-card">

                                <div class="inventory-date-icon">
                                    🗓
                                </div>

                                <p>
                                    <strong>Recent Purchase Date:</strong>
                                    {{ $item['purchase_date'] }}
                                </p>

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

                <div class="inventory-actions">

                    <button
                        type="button"
                        class="inventory-edit-btn"
                        id="openVitaminEditModal"
                        aria-label="Edit Vitamin"
                    >
                        ✎ Edit
                    </button>

                    <button
                        type="button"
                        class="inventory-icon-btn add-btn"
                        id="openVitaminAddModal"
                        aria-label="Add Vitamin"
                    >
                        +
                    </button>

                </div>

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
                        data-purchase-date="{{ $item['purchase_date'] }}"
                        data-unit="{{ $item['unit'] }}"
                    >

                        <div class="inventory-entry-header">

                            <h3 class="inventory-item-name">
                                {{ $item['item_name'] }}
                            </h3>

                        </div>

                        <div class="inventory-item-block">

                            <div class="inventory-card inventory-stock-card">

                                <div
                                    class="inventory-ring {{ $item['status_class'] }}"
                                    style="--percent: {{ $item['percentage'] }};"
                                >
                                    <div class="inventory-ring-inner"></div>
                                </div>

                                <div class="inventory-stock-details">

                                    <span class="inventory-status-badge {{ $item['status_class'] }}">
                                        {{ $item['status'] }}
                                    </span>

                                    <p>
                                        <strong>Initial Stock:</strong>
                                        {{ $item['initial_stock'] }} bottles
                                    </p>

                                    <p>
                                        <strong>Remaining:</strong>
                                        {{ $item['remaining_stock'] }} bottles
                                    </p>

                                    <p>
                                        <strong>Critical Level:</strong>
                                        {{ $item['critical'] }} bottles
                                    </p>

                                </div>

                            </div>

                            <div class="inventory-card inventory-date-card">

                                <div class="inventory-date-icon">
                                    🗓
                                </div>

                                <p>
                                    <strong>Recent Purchase Date:</strong>
                                    {{ $item['purchase_date'] }}
                                </p>

                            </div>

                        </div>

                    </div>

                @endforeach

            </div>

        </section>

    </main>

</div>

@include('includes.manager-edit-stocks-modal')
@include('includes.manager-add-stocks-modal')
@include('includes.manager-archive-inventory-modal')
@include('includes.manager-profile-modal')

@endsection