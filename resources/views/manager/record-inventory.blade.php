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