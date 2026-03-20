@extends('layouts.app')

@section('title', 'House Records')

@push('styles')
    @vite([
        'resources/css/manager-shared.css',
        'resources/css/manager-record-house.css'
    ])
@endpush

@push('scripts')
    @vite('resources/js/manager-record-house.js')
@endpush

@section('content')
    @php
        $houses = [
            [
                'name' => 'House 1',
                'records' => [
                    [
                        'batch_id' => 'Batch-2026-01',
                        'start_date' => '2026-01-10',
                        'end_date' => '2026-02-01',
                        'reporting_date' => '2026-01-15',
                        'pen_no' => '1',
                        'initial_population' => '1500',
                        'running_population' => '1488',
                        'mortalities' => '12',
                        'eggs_hatched' => '98',
                    ],
                    [
                        'batch_id' => 'Batch-2026-02',
                        'start_date' => '2026-02-05',
                        'end_date' => '2026-03-01',
                        'reporting_date' => '2026-02-10',
                        'pen_no' => '2',
                        'initial_population' => '1429',
                        'running_population' => '1410',
                        'mortalities' => '19',
                        'eggs_hatched' => '105',
                    ],
                ],
            ],
            [
                'name' => 'House 2',
                'records' => [
                    [
                        'batch_id' => 'Batch-2026-02',
                        'start_date' => '2026-02-15',
                        'end_date' => '2026-03-10',
                        'reporting_date' => '2026-02-20',
                        'pen_no' => '1',
                        'initial_population' => '1750',
                        'running_population' => '1736',
                        'mortalities' => '14',
                        'eggs_hatched' => '112',
                    ],
                    [
                        'batch_id' => 'Batch-2026-03',
                        'start_date' => '2026-03-01',
                        'end_date' => '2026-03-25',
                        'reporting_date' => '2026-03-07',
                        'pen_no' => '2',
                        'initial_population' => '1900',
                        'running_population' => '1885',
                        'mortalities' => '15',
                        'eggs_hatched' => '120',
                    ],
                ],
            ],
            [
                'name' => 'House 3',
                'records' => [
                    [
                        'batch_id' => 'Batch-2026-03',
                        'start_date' => '2026-03-10',
                        'end_date' => '2026-04-02',
                        'reporting_date' => '2026-03-15',
                        'pen_no' => '1',
                        'initial_population' => '2050',
                        'running_population' => '2038',
                        'mortalities' => '12',
                        'eggs_hatched' => '98',
                    ],
                    [
                        'batch_id' => 'Batch-2026-04',
                        'start_date' => '2026-03-15',
                        'end_date' => '2026-04-10',
                        'reporting_date' => '2026-03-22',
                        'pen_no' => '2',
                        'initial_population' => '2100',
                        'running_population' => '2084',
                        'mortalities' => '16',
                        'eggs_hatched' => '101',
                    ],
                ],
            ],
        ];
    @endphp

    <div class="manager-shell">
        @include('includes.manager-sidebar')

        <main class="manager-main">
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
                    <a href="{{ route('manager.houses') }}" class="record-back-btn" title="Back">
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