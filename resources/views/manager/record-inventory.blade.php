@extends('layouts.app')

@section('title', 'Inventory Records')

@push('styles')
    @vite([
        'resources/css/manager-shared.css',
        'resources/css/manager-record-inventory.css'
    ])
@endpush

@push('scripts')
    @vite('resources/js/manager-record-inventory.js')
@endpush

@section('content')
    <div class="manager-shell">
        @include('includes.manager-sidebar')
        @include('includes.manager-profile-modal')

        <main class="manager-main record-inventory-main">
            <div class="record-inventory-topbar">
                <h1 class="record-inventory-title">Inventory Records</h1>

                <div class="record-inventory-topbar-actions">
                    <button class="manager-profile" type="button" id="openProfileModal" aria-label="Open profile">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <circle cx="12" cy="8" r="4"></circle>
                            <path d="M4 20c1.8-3.8 5-5.5 8-5.5S18.2 16.2 20 20"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="record-inventory-divider"></div>

            <div class="record-inventory-controls">
                <a href="{{ route('manager.inventory') }}" class="record-back-btn" aria-label="Back to inventory">
                    &#10094;
                </a>

                <div class="record-tabs">
                    <button type="button" class="record-tab active" id="feedTab">Feed</button>
                    <button type="button" class="record-tab" id="vitaminsTab">Vitamins</button>
                </div>

                {{-- ✅ UPDATED: unified filter dropdown --}}
                <div class="record-filter-wrap hidden" id="filterWrap">
                    <select class="record-filter-select" id="recordFilter"></select>
                </div>
            </div>

            <div class="record-table-card">
                <table class="record-table">
                    <thead id="recordTableHead"></thead>
                    <tbody id="recordTableBody"></tbody>
                </table>
            </div>
        </main>
    </div>
@endsection