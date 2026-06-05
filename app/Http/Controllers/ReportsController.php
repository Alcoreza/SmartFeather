<?php

namespace App\Http\Controllers;

use App\Models\FeedRefillRecord;
use App\Models\House;
use App\Models\Pen;
use App\Models\WeightSamplingLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'from_date' => $request->query('from_date', $request->query('start_date', '')),
            'to_date' => $request->query('to_date', $request->query('end_date', '')),
            'house' => $request->query('house', ''),
        ];

        return response()->json([
            'filters' => array_merge($filters, [
                'options' => $this->getReportFilterOptions(),
            ]),
            'summary' => $this->getSummaryStatistics($filters),
            'reports' => [
                'farm_status' => $this->getFarmStatusReport($filters),
                'feed_consumption' => $this->getFeedConsumptionReport($filters),
                'mortality' => $this->getMortalityReport($filters),
            ],
        ]);
    }

    private function getSummaryStatistics(array $filters): array
    {
        $totalFeedConsumed = 0;
        $totalMortalities = 0;
        $weightStatus = ['overweight' => 0, 'normal' => 0, 'underweight' => 0];

        // Total Feed Consumed
        if (Schema::hasTable('feed_refill_records')) {
            $query = FeedRefillRecord::query();
            $this->applyDateRange($query, 'recorded_at', $filters['from_date'] ?? null, $filters['to_date'] ?? null);

            if (!empty($filters['house'])) {
                $query->whereHas('house', function ($houseQuery) use ($filters) {
                    $houseQuery->whereIn('house_number', $this->getHouseFilterValues($filters['house']));
                });
            }

            $totalFeedConsumed = $query->sum('kilograms_used') ?? 0;
        }

        // Total Mortalities
        if (Schema::hasTable('pen')) {
            $query = Pen::query()->whereNull('archived_at');
            $this->applyDateRange($query, 'recorded_at', $filters['from_date'] ?? null, $filters['to_date'] ?? null);

            if (!empty($filters['house'])) {
                $query->whereHas('house', function ($houseQuery) use ($filters) {
                    $houseQuery->whereIn('house_number', $this->getHouseFilterValues($filters['house']));
                });
            }

            $totalMortalities = $query->sum('mortality') ?? 0;
        }

        // Weight Status Breakdown
        if (Schema::hasTable('weight_sampling_logs')) {
            $query = WeightSamplingLog::query();
            $this->applyDateRange($query, 'date', $filters['from_date'] ?? null, $filters['to_date'] ?? null);

            if (!empty($filters['house'])) {
                $query->whereIn('house', $this->getHouseFilterValues($filters['house']));
            }

            $statuses = $query->pluck('status')->toArray();
            foreach ($statuses as $status) {
                $status = strtolower(trim($status));
                if ($status === 'overweight') {
                    $weightStatus['overweight']++;
                } elseif ($status === 'underweight') {
                    $weightStatus['underweight']++;
                } else {
                    $weightStatus['normal']++;
                }
            }
        }

        return [
            'total_feed_consumed' => $totalFeedConsumed,
            'total_mortalities' => $totalMortalities,
            'weight_status' => $weightStatus,
        ];
    }

    private function getReportFilterOptions(): array
    {
        $houses = House::query()
            ->whereNull('archived_at')
            ->whereNotNull('house_number')
            ->orderBy('house_number')
            ->pluck('house_number')
            ->filter()
            ->unique()
            ->values()
            ->map(fn($houseNumber) => [
                'value' => $this->formatHouseNumber($houseNumber),
                'label' => $this->formatHouseNumber($houseNumber),
            ])
            ->all();

        return [
            'houses' => $houses,
        ];
    }

    private function getFarmStatusReport(array $filters): array
    {
        $query = WeightSamplingLog::query()
            ->orderByDesc('date')
            ->orderByDesc('id');

        $this->applyDateRange($query, 'date', $filters['from_date'] ?? null, $filters['to_date'] ?? null);

        if (!empty($filters['house'])) {
            $query->whereIn('house', $this->getHouseFilterValues($filters['house']));
        }

        return $query->get()->map(fn($record) => [
            'house' => $record->house ?? '--',
            'pen' => $record->pen ?? '--',
            'batch' => $record->batch ?? '--',
            'average_weight' => $record->average_weight ?? '--',
            'target' => $record->target ?? '--',
            'status' => $record->status ?? '--',
            'date' => $this->formatDate($record->date),
        ])->values()->all();
    }

    private function getFeedConsumptionReport(array $filters): array
    {
        $query = FeedRefillRecord::query()
            ->with(['inventory', 'house', 'pen.runningBatch'])
            ->orderByDesc('recorded_at');

        $this->applyDateRange($query, 'recorded_at', $filters['from_date'] ?? null, $filters['to_date'] ?? null);

        if (!empty($filters['house'])) {
            $query->whereHas('house', function ($houseQuery) use ($filters) {
                $houseQuery->whereIn('house_number', $this->getHouseFilterValues($filters['house']));
            });
        }

        return $query->get()->map(fn($record) => [
            'feed' => $record->inventory?->item_name ?? '--',
            'house_number' => $this->formatHouseNumber($record->house?->house_number),
            'pen_name' => $record->pen?->pen_name ?? '--',
            'feeder_number' => $record->feeder_number ?? '--',
            'kilograms_used' => $this->formatNumber($record->kilograms_used),
            'recorded_at' => $this->formatDate($record->recorded_at),
        ])->values()->all();
    }

    private function getMortalityReport(array $filters): array
    {
        $query = Pen::query()
            ->with(['house', 'currentBatch'])
            ->whereNull('archived_at')
            ->orderBy('house_id')
            ->orderBy('id');

        $this->applyDateRange($query, 'recorded_at', $filters['from_date'] ?? null, $filters['to_date'] ?? null);

        if (!empty($filters['house'])) {
            $query->whereHas('house', function ($houseQuery) use ($filters) {
                $houseQuery->whereIn('house_number', $this->getHouseFilterValues($filters['house']));
            });
        }

        return $query->get()->map(fn($record) => [
            'house_number' => $this->formatHouseNumber($record->house?->house_number),
            'pen_name' => $record->pen_name ?? '--',
            'mortality' => $record->mortality ?? 0,
            'recorded_at' => $this->formatDate($record->recorded_at),
        ])->values()->all();
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|in:Population,Environmental,Inventory,Biosecurity,Weight Sampling,Tasks',
            'format' => 'required|string|in:csv,pdf',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2100',
        ]);

        $type = $validated['type'];
        $format = $validated['format'];
        $month = (int) $validated['month'];
        $year = (int) $validated['year'];

        $period = Carbon::create($year, $month, 1);
        $startDate = $period->copy()->startOfMonth()->toDateString();
        $endDate = $period->copy()->endOfMonth()->toDateString();
        $periodLabel = $period->format('F Y');

        $reportData = $this->getReportByType($type, $startDate, $endDate);

        if ($format === 'csv') {
            return $this->downloadCsv($type, $reportData, $periodLabel);
        }

        return response()->view('reports.print', [
            'reportType' => $type,
            'reportData' => $reportData,
            'periodLabel' => $periodLabel,
            'generatedAt' => now()->format('F d, Y h:i A'),
        ]);
    }

    private function getReportByType(string $type, ?string $startDate, ?string $endDate)
    {
        return match ($type) {
            'Population' => $this->getPopulationReport($startDate, $endDate),
            'Environmental' => $this->getEnvironmentalReport($startDate, $endDate),
            'Inventory' => $this->getInventoryReport($startDate, $endDate),
            'Biosecurity' => $this->getBiosecurityReport($startDate, $endDate),
            'Weight Sampling' => $this->getWeightSamplingReport($startDate, $endDate),
            'Tasks' => $this->getTasksReport($startDate, $endDate),
            default => [],
        };
    }

    private function getPopulationReport(?string $startDate, ?string $endDate): array
    {
        if (!Schema::hasTable('house') || !Schema::hasTable('pen')) {
            return [];
        }

        $query = DB::table('pen')
            ->leftJoin('house', 'pen.house_id', '=', 'house.id')
            ->leftJoin('flock_batches as fb', function ($join) {
                $join->on('fb.pen_id', '=', 'pen.id')
                     ->where('fb.status', 'Running');
            })
            ->select(
                'fb.batch_code',
                'house.start_date',
                'house.house_number',
                'pen.pen_name',
                'pen.capacity',
                'pen.population',
                'pen.mortality',
                'pen.eggs_hatched',
                'pen.recorded_at'
            )
            ->orderBy('house.house_number')
            ->orderBy('pen.pen_name');

        $this->applyDateRange($query, 'pen.recorded_at', $startDate, $endDate);

        return $query->get()->map(function ($row) {
            return [
                'batch_id' => $row->batch_code ?? '--',
                'house_number' => $row->house_number ?? '--',
                'pen_no' => $row->pen_name ?? '--',
                'start_date' => $this->formatDate($row->start_date),
                'end_date' => '--',
                'initial_population' => $row->capacity ?? 0,
                'running_population' => $row->population ?? 0,
                'mortalities' => $row->mortality ?? 0,
                'eggs_hatched' => $row->eggs_hatched ?? 0,
                'reporting_date' => $this->formatDate($row->recorded_at),
            ];
        })->values()->all();
    }

    private function getEnvironmentalReport(?string $startDate, ?string $endDate): array
    {
        $feeds = [];

        if (Schema::hasTable('feed_refill_records') && Schema::hasTable('house') && Schema::hasTable('pen') && Schema::hasTable('inventories')) {
            $query = DB::table('feed_refill_records as f')
                ->join('inventories as i', 'f.inventory_id', '=', 'i.id')
                ->leftJoin('house', 'f.house_id', '=', 'house.id')
                ->leftJoin('pen', 'f.pen_id', '=', 'pen.id')
                ->select(
                    'house.house_number',
                    'f.recorded_at',
                    'f.kilograms_used',
                    'house.house_number as batch_code',
                    'pen.pen_name',
                    'f.feeder_number'
                )
                ->orderByDesc('f.recorded_at');

            $this->applyDateRange($query, 'f.recorded_at', $startDate, $endDate);

            $feeds = $query->get()->map(function ($row) {
                return [
                    'batch' => $row->batch_code ?? '--',
                    'date' => $this->formatDate($row->recorded_at),
                    'feeds_level' => $row->kilograms_used ?? 0,
                    'house' => $row->house_number ?? '--',
                    'pen' => $row->pen_name ?? '--',
                    'feeder_number' => $row->feeder_number ?? '--',
                ];
            })->values()->all();
        }

        return [
            'temperature' => [],
            'ammonia' => [],
            'feeds' => $feeds,
            'water' => [],
        ];
    }

    private function getInventoryReport(?string $startDate, ?string $endDate): array
    {
        if (!Schema::hasTable('inventories') || !Schema::hasTable('inventory_records')) {
            return [
                'feeds' => [],
                'vitamins' => [],
            ];
        }

        $query = DB::table('inventory_records as r')
            ->join('inventories as i', 'r.inventory_id', '=', 'i.id')
            ->select(
                'i.item_name',
                'i.type',
                'i.unit',
                'i.purchase_date',
                'r.monitoring_date',
                'r.initial_stock',
                'r.remaining_stock'
            )
            ->orderBy('r.monitoring_date', 'desc');

        $this->applyDateRange($query, 'r.monitoring_date', $startDate, $endDate);

        $records = $query->get();

        $feeds = [];
        $vitamins = [];

        foreach ($records as $row) {
            if ($row->type === 'feed') {
                $feeds[] = [
                    'purchase_date' => $this->formatDate($row->purchase_date),
                    'date_of_monitoring' => $this->formatDateTime($row->monitoring_date),
                    'type_of_feed' => $row->item_name ?? '--',
                    'initial_stock' => $row->initial_stock ?? 0,
                    'remaining_stock' => $row->remaining_stock ?? 0,
                ];
            }

            if ($row->type === 'vitamin') {
                $vitamins[] = [
                    'purchase_date' => $this->formatDate($row->purchase_date),
                    'date_of_monitoring' => $this->formatDateTime($row->monitoring_date),
                    'type_of_vitamin' => $row->item_name ?? '--',
                    'initial_stock' => $row->initial_stock ?? 0,
                    'remaining_stock' => $row->remaining_stock ?? 0,
                ];
            }
        }

        return [
            'feeds' => $feeds,
            'vitamins' => $vitamins,
        ];
    }

    private function getBiosecurityReport(?string $startDate, ?string $endDate): array
    {
        if (!Schema::hasTable('biosecurity_logs')) {
            return [
                'cleaning' => [],
                'personnel_biosecurity_logs' => [],
                'visitors' => [],
                'personnel_entry_logs' => [],
            ];
        }

        $query = DB::table('biosecurity_logs')
            ->orderBy('date', 'desc')
            ->orderBy('time', 'desc');

        $this->applyDateRange($query, 'date', $startDate, $endDate);

        $logs = $query->get();

        $cleaning = [];
        $personnelBiosecurityLogs = [];
        $visitors = [];
        $personnelEntryLogs = [];

        foreach ($logs as $log) {
            switch ($log->type) {
                case 'Cleaning':
                    $cleaning[] = [
                        'house' => $log->house ?? '--',
                        'pen' => $log->pen ?? '--',
                        'activity' => $log->activity ?? '--',
                        'date' => $this->formatDate($log->date),
                        'time' => $this->formatTime($log->time),
                        'disinfectant_used' => $log->disinfectant_used ?? '--',
                        'performed_by' => $log->performed_by ?? '--',
                    ];
                    break;

                case 'Personnel Biosecurity Logs':
                    $personnelBiosecurityLogs[] = [
                        'name' => $log->name ?? '--',
                        'role' => $log->role ?? '--',
                        'house' => $log->house ?? '--',
                        'date' => $this->formatDate($log->date),
                        'time' => $this->formatTime($log->time),
                        'foot_bath' => $log->foot_bath ?? '--',
                        'boots_changed' => $log->boots_changed ?? '--',
                        'protective_clothing' => $log->protective_clothing ?? '--',
                    ];
                    break;

                case 'Visitors':
                    $visitors[] = [
                        'date' => $this->formatDate($log->date),
                        'time_in' => $this->formatTime($log->time_in),
                        'time_out' => $this->formatTime($log->time_out),
                        'name' => $log->name ?? '--',
                        'purpose' => $log->purpose ?? '--',
                        'foot_bath' => $log->foot_bath ?? '--',
                        'sanitation' => $log->sanitation ?? '--',
                        'ppe' => $log->ppe ?? '--',
                        'monitored_by' => $log->monitored_by ?? '--',
                    ];
                    break;

                case 'Personnel Entry Logs':
                    $personnelEntryLogs[] = [
                        'name' => $log->name ?? '--',
                        'role' => $log->role ?? '--',
                        'house' => $log->house ?? '--',
                        'date' => $this->formatDate($log->date),
                        'time' => $this->formatTime($log->time),
                    ];
                    break;
            }
        }

        return [
            'cleaning' => $cleaning,
            'personnel_biosecurity_logs' => $personnelBiosecurityLogs,
            'visitors' => $visitors,
            'personnel_entry_logs' => $personnelEntryLogs,
        ];
    }

    private function getWeightSamplingReport(?string $startDate, ?string $endDate): array
    {
        if (!Schema::hasTable('biosecurity_logs')) {
            return [];
        }

        $query = DB::table('biosecurity_logs')
            ->where('type', 'Weight Sampling')
            ->orderBy('date', 'desc')
            ->orderBy('time', 'desc');

        $this->applyDateRange($query, 'date', $startDate, $endDate);

        return $query->get()->map(function ($log) {
            return [
                'date' => $this->formatDate($log->date),
                'time' => $this->formatTime($log->time),
                'house' => $log->house ?? '--',
                'pen' => $log->pen ?? '--',
                'batch' => $log->batch ?? '--',
                'flocks_with_cases' => $log->flocks_with_cases ?? '--',
                'age' => $log->age ?? '--',
                'average_weight' => $log->average_weight ?? '--',
                'target' => $log->target ?? '--',
                'status' => $log->status ?? '--',
            ];
        })->values()->all();
    }

    private function getTasksReport(?string $startDate, ?string $endDate): array
    {
        if (!Schema::hasTable('tasks')) {
            return [];
        }

        $query = DB::table('tasks')
            ->leftJoin('user', 'tasks.user_employeeid', '=', 'user.EmployeeId')
            ->leftJoin('house', 'tasks.house_houseid', '=', 'house.id')
            ->leftJoin('pen', 'tasks.pennumber', '=', 'pen.id')
            ->select(
                'tasks.tasktype',
                'tasks.detailedtask',
                'tasks.timeassigned',
                'tasks.finishby',
                'tasks.time_completed',
                'tasks.prioritylevel',
                'tasks.notes',
                'tasks.photourl',
                'house.house_number',
                'pen.pen_name',
                'user.FirstName',
                'user.MiddleName',
                'user.LastName',
                'user.Suffix'
            )
            ->orderByDesc('tasks.timeassigned');

        $this->applyDateRange($query, 'tasks.timeassigned', $startDate, $endDate);

        return $query->get()->map(function ($row) {
            $name = trim(collect([
                $row->FirstName,
                $row->MiddleName,
                $row->LastName,
                $row->Suffix,
            ])->filter()->implode(' '));

            return [
                'name' => $name !== '' ? $name : '--',
                'task_assigned' => $row->tasktype ?? '--',
                'house_number' => $row->house_number ?? '--',
                'pen_number' => $row->pen_name ?? '--',
                'detailed_task' => $row->detailedtask ?? '--',
                'photo' => $row->photourl ?? '',
                'priority' => $row->prioritylevel ?? '--',
                'notes' => $row->notes ?? '--',
                'time_assigned' => $this->formatDateTime($row->timeassigned),
                'finish_by' => $this->formatDateTime($row->finishby),
                'time_completed' => $this->formatDateTime($row->time_completed),
            ];
        })->values()->all();
    }

    private function downloadCsv(string $type, $reportData, string $periodLabel)
    {
        $filename = strtolower(str_replace(' ', '_', $type)) . '_report_' . strtolower(str_replace(' ', '_', $periodLabel)) . '.csv';
        $rows = $this->flattenReportForCsv($type, $reportData);

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');

            if (!empty($rows)) {
                fputcsv($handle, array_keys($rows[0]));
                foreach ($rows as $row) {
                    fputcsv($handle, $row);
                }
            } else {
                fputcsv($handle, ['message']);
                fputcsv($handle, ['No data available']);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function flattenReportForCsv(string $type, $reportData): array
    {
        if ($type === 'Population' || $type === 'Weight Sampling' || $type === 'Tasks') {
            return is_array($reportData) ? $reportData : [];
        }

        $rows = [];

        if ($type === 'Environmental' && is_array($reportData)) {
            foreach (['temperature', 'ammonia', 'feeds', 'water'] as $section) {
                foreach (($reportData[$section] ?? []) as $row) {
                    $rows[] = array_merge(['section' => ucfirst($section)], $row);
                }
            }
        }

        if ($type === 'Inventory' && is_array($reportData)) {
            foreach (['feeds', 'vitamins'] as $section) {
                foreach (($reportData[$section] ?? []) as $row) {
                    $rows[] = array_merge(['section' => ucfirst($section)], $row);
                }
            }
        }

        if ($type === 'Biosecurity' && is_array($reportData)) {
            foreach (['cleaning', 'personnel_biosecurity_logs', 'visitors', 'personnel_entry_logs'] as $section) {
                foreach (($reportData[$section] ?? []) as $row) {
                    $rows[] = array_merge(['section' => $section], $row);
                }
            }
        }

        return $rows;
    }

    private function applyDateRange($query, string $column, ?string $startDate, ?string $endDate): void
    {
        if ($startDate) {
            $query->whereDate($column, '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate($column, '<=', $endDate);
        }
    }

    private function formatDate($value): string
    {
        if (empty($value)) {
            return '--';
        }

        try {
            return Carbon::parse($value)->format('m-d-y');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }

    private function formatTime($value): string
    {
        if (empty($value)) {
            return '--';
        }

        try {
            return Carbon::parse($value)->format('h:i A');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }

    private function formatDateTime($value): string
    {
        if (empty($value)) {
            return '--';
        }

        try {
            return Carbon::parse($value)->format('m-d-y h:i A');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }

    private function formatHouseNumber($value): string
    {
        if (empty($value)) {
            return '--';
        }

        return str_starts_with((string) $value, 'House ')
            ? (string) $value
            : 'House ' . $value;
    }

    private function normalizeHouseNumber($value): string
    {
        return trim(preg_replace('/^House\s+/i', '', (string) $value));
    }

    private function getHouseFilterValues($value): array
    {
        $normalized = $this->normalizeHouseNumber($value);

        return array_values(array_unique([
            (string) $value,
            $normalized,
            $this->formatHouseNumber($normalized),
        ]));
    }

    private function formatNumber($value): string
    {
        if ($value === null || $value === '') {
            return '0';
        }

        $number = (float) $value;

        return floor($number) === $number
            ? (string) (int) $number
            : number_format($number, 2, '.', '');
    }
}
