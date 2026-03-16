@extends('layouts.app')

@section('title', 'Admin House Records')

@push('styles')
    @vite([
        'resources/css/admin-shared.css',
        'resources/css/admin-record-house.css'
    ])
@endpush

@push('scripts')
    @vite('resources/js/admin-record-house.js')
@endpush

@section('content')
    @php
        $houses = [
            [
                'name' => 'House 1',
                'records' => [
                    [
                        'batch_id' => 'Batch-2025-07',
                        'start_date' => '07-5-25',
                        'end_date' => '08-11-25',
                        'reporting_date' => '07-8-25',
                        'pen_no' => '2',
                        'initial_population' => '10000',
                        'running_population' => '9980',
                        'mortalities' => '5',
                        'eggs_hatched' => '325',
                    ],
                    [
                        'batch_id' => 'Batch-2025-07',
                        'start_date' => '07-5-25',
                        'end_date' => '08-11-25',
                        'reporting_date' => '07-7-25',
                        'pen_no' => '1',
                        'initial_population' => '10000',
                        'running_population' => '9985',
                        'mortalities' => '15',
                        'eggs_hatched' => '325',
                    ],
                    [
                        'batch_id' => 'Batch-2025-07',
                        'start_date' => '07-5-25',
                        'end_date' => '08-11-25',
                        'reporting_date' => '07-6-25',
                        'pen_no' => '4',
                        'initial_population' => '10000',
                        'running_population' => '10000',
                        'mortalities' => '250',
                        'eggs_hatched' => '325',
                    ],
                ],
            ],
            [
                'name' => 'House 2',
                'records' => [
                    [
                        'batch_id' => 'Batch-2026-08',
                        'start_date' => '08-1-25',
                        'end_date' => '09-5-25',
                        'reporting_date' => '08-4-25',
                        'pen_no' => '2',
                        'initial_population' => '9500',
                        'running_population' => '9440',
                        'mortalities' => '18',
                        'eggs_hatched' => '310',
                    ],
                    [
                        'batch_id' => 'Batch-2026-08',
                        'start_date' => '08-1-25',
                        'end_date' => '09-5-25',
                        'reporting_date' => '08-6-25',
                        'pen_no' => '3',
                        'initial_population' => '9500',
                        'running_population' => '9412',
                        'mortalities' => '27',
                        'eggs_hatched' => '318',
                    ],
                ],
            ],
            [
                'name' => 'House 3',
                'records' => [
                    [
                        'batch_id' => 'Batch-2026-09',
                        'start_date' => '09-2-25',
                        'end_date' => '10-7-25',
                        'reporting_date' => '09-4-25',
                        'pen_no' => '1',
                        'initial_population' => '10200',
                        'running_population' => '10160',
                        'mortalities' => '12',
                        'eggs_hatched' => '340',
                    ],
                    [
                        'batch_id' => 'Batch-2026-09',
                        'start_date' => '09-2-25',
                        'end_date' => '10-7-25',
                        'reporting_date' => '09-6-25',
                        'pen_no' => '2',
                        'initial_population' => '10200',
                        'running_population' => '10105',
                        'mortalities' => '31',
                        'eggs_hatched' => '336',
                    ],
                ],
            ],
        ];
    @endphp

    <div class="admin-shell">
        @include('includes.admin-sidebar')

        <main class="admin-main">
            <section class="record-house-page">
                <div class="record-page-header">
                    <h1>House Data</h1>
                </div>

                <div class="record-house-tabs">
                    @foreach ($houses as $index => $house)
                        <button
                            type="button"
                            class="record-house-tab {{ $index === 0 ? 'active' : '' }}"
                            data-house-index="{{ $index }}"
                        >
                            {{ $house['name'] }}
                        </button>
                    @endforeach
                </div>

                <div class="record-page-divider"></div>

                <div class="record-topbar">
                    <a href="{{ route('admin.houses') }}" class="record-back-btn" title="Back">
                        &#10094;
                    </a>

                    <div class="record-pill">Records</div>
                </div>

                <div class="record-table-card">
                    <div class="record-table-wrap">
                        <table class="record-table">
                            <thead>
                                <tr>
                                    <th>Batch ID</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Reporting Date</th>
                                    <th>Pen No.</th>
                                    <th>Initial Population</th>
                                    <th>Running Population</th>
                                    <th>Mortalities</th>
                                    <th>Eggs Hatched</th>
                                </tr>
                            </thead>
                            <tbody id="recordTableBody">
                                @foreach ($houses[0]['records'] as $record)
                                    <tr>
                                        <td>{{ $record['batch_id'] }}</td>
                                        <td>{{ $record['start_date'] }}</td>
                                        <td>{{ $record['end_date'] }}</td>
                                        <td>{{ $record['reporting_date'] }}</td>
                                        <td>{{ $record['pen_no'] }}</td>
                                        <td>{{ $record['initial_population'] }}</td>
                                        <td>{{ $record['running_population'] }}</td>
                                        <td>{{ $record['mortalities'] }}</td>
                                        <td>{{ $record['eggs_hatched'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script>
        window.houseRecordData = @json($houses);
    </script>
@endsection