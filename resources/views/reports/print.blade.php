<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $reportType }} Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 24px;
            color: #111827;
        }

        h1, h2, h3 {
            margin: 0 0 12px;
        }

        .meta {
            margin-bottom: 20px;
            font-size: 14px;
            color: #4b5563;
        }

        .section {
            margin-bottom: 28px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 12px;
        }

        th, td {
            border: 1px solid #d1d5db;
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f3f4f6;
        }

        .empty {
            padding: 12px;
            border: 1px solid #d1d5db;
            background: #fafafa;
        }

        @media print {
            body {
                margin: 12px;
            }
        }
    </style>
</head>
<body>
    <h1>{{ $reportType }} Report</h1>

    <div class="meta">
        <div><strong>Generated At:</strong> {{ $generatedAt }}</div>
        <div><strong>Reporting Period:</strong> {{ $periodLabel }}</div>
    </div>

    @if ($reportType === 'Population' || $reportType === 'Weight Sampling' || $reportType === 'Tasks')
        @php
            $rows = is_array($reportData) ? $reportData : [];
            $headers = !empty($rows) ? array_keys($rows[0]) : [];
        @endphp

        @if (count($rows))
            <table>
                <thead>
                    <tr>
                        @foreach ($headers as $header)
                            <th>{{ ucwords(str_replace('_', ' ', $header)) }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            @foreach ($headers as $header)
                                <td>{{ $row[$header] ?? '' }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="empty">No data available.</div>
        @endif
    @else
        @foreach ($reportData as $sectionTitle => $rows)
            <div class="section">
                <h2>{{ ucwords(str_replace('_', ' ', $sectionTitle)) }}</h2>

                @php
                    $rows = is_array($rows) ? $rows : [];
                    $headers = !empty($rows) ? array_keys($rows[0]) : [];
                @endphp

                @if (count($rows))
                    <table>
                        <thead>
                            <tr>
                                @foreach ($headers as $header)
                                    <th>{{ ucwords(str_replace('_', ' ', $header)) }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr>
                                    @foreach ($headers as $header)
                                        <td>{{ $row[$header] ?? '' }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="empty">No data available.</div>
                @endif
            </div>
        @endforeach
    @endif

    <script>
        window.onload = function () {
            window.print();
        };
    </script>
</body>
</html>