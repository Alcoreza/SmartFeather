<?php

namespace App\Http\Controllers;

use App\Models\CleaningLog;
use App\Models\Employee;
use App\Models\PersonnelBiosecurityLog;
use App\Models\VisitorLog;
use App\Models\PersonnelEntryLog;
use App\Models\WeightSamplingLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BiosecurityLogController extends Controller
{
    /**
     * Get all biosecurity logs grouped by type
     */
    public function index(Request $request)
    {
        try {
            $requestedCategories = $this->getRequestedLogCategories($request);
            $cacheKey = 'biosecurity_logs_index:' . md5(implode('|', $requestedCategories));

            return response()->json(Cache::remember($cacheKey, now()->addSeconds(15), function () use ($requestedCategories) {
            $groupedLogs = [];

            if (in_array('Personnel Biosecurity Logs', $requestedCategories, true)) {
                $groupedLogs['Personnel Biosecurity Logs'] = $this->formatPersonnelBiosecurityLogs(
                    PersonnelEntryLog::orderBy('date')->orderBy('time')->orderBy('id')->get(),
                );
            }

            if (in_array('Visitors', $requestedCategories, true)) {
                $groupedLogs['Visitors'] = VisitorLog::orderBy('created_at', 'desc')
                    ->get()
                    ->map(fn($log) => $this->formatVisitorLog($log));
            }

            if (in_array('Cleaning', $requestedCategories, true)) {
                $groupedLogs['Cleaning'] = CleaningLog::orderBy('created_at', 'desc')
                    ->get()
                    ->map(fn($log) => $this->formatCleaningLog($log));
            }

            if (in_array('Personnel Entry Logs', $requestedCategories, true)) {
                $groupedLogs['Personnel Entry Logs'] = PersonnelEntryLog::orderBy('created_at', 'desc')
                    ->get()
                    ->map(fn($log) => $this->formatPersonnelEntryLog($log));
            }

            if (in_array('Weight Sampling', $requestedCategories, true)) {
                $groupedLogs['Weight Sampling'] = WeightSamplingLog::orderBy('created_at', 'desc')
                    ->get()
                    ->map(fn($log) => $this->formatWeightSamplingLog($log));
            }

            // Get overview stats
            $overview = $this->getOverview();

            return [
                'overview' => $overview,
                'logs' => $groupedLogs,
            ];
            }));
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'overview' => [],
                'logs' => [],
            ], 500);
        }
    }

    private function getRequestedLogCategories(Request $request): array
    {
        $availableCategories = [
            'Cleaning',
            'Personnel Biosecurity Logs',
            'Visitors',
            'Personnel Entry Logs',
            'Weight Sampling',
        ];

        $categories = $request->query('categories');

        if ($categories === 'all') {
            return $availableCategories;
        }

        if (is_string($categories) && trim($categories) !== '') {
            $requested = array_map('trim', explode(',', $categories));

            return array_values(array_intersect($availableCategories, $requested));
        }

        return ['Personnel Biosecurity Logs', 'Visitors'];
    }

    /**
     * Store a new biosecurity log
     */
    public function store(Request $request)
    {
        $type = $request->input('type', 'Cleaning');
        $request->merge(['type' => $type]);

        if ($type === 'Personnel Biosecurity Logs') {
            return response()->json([
                'message' => 'Personnel logs are generated from personnel scan activity.',
            ], 422);
        }

        $validated = $request->validate($this->getValidationRules($type));
        unset($validated['type']);

        // If a captured photo was attached (DataURL), upload it now and set photo_url
        if ($type === 'Visitors' && $request->filled('photo_data')) {
            try {
                $path = $this->uploadPhotoFromData($request->input('photo_data'));
                if ($path) {
                    // Ensure stored field matches validation rule 'photo_url'
                    $validated['photo_url'] = $path;
                }
            } catch (\Exception $e) {
                return response()->json(['message' => 'Failed to upload photo during save', 'error' => $e->getMessage()], 500);
            }
        }

        // Convert IDs to display values
        $validated = $this->convertIdsToValues($type, $validated);

        $log = $this->createLog($type, $validated);
        $this->clearBiosecurityLogCaches();

        return response()->json([
            'message' => 'Log created successfully',
            'log' => $this->formatLogByType($type, $log),
        ], 201);
    }

    /**
     * Upload a visitor photo to Supabase storage
     */
    public function uploadVisitorPhoto(Request $request)
    {
        $validated = $request->validate([
            'photo_data' => ['required', 'string'],
            'mime_type' => ['nullable', 'string', 'in:image/jpeg,image/png,image/webp'],
        ]);
        $data = $validated['photo_data'];

        try {
            $path = $this->uploadPhotoFromData($data, $validated['mime_type'] ?? null);
            return response()->json([
                'success' => true,
                'photo_path' => $path,
                'photo_url' => $this->buildVisitorPhotoUrl($path),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to upload photo', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Handle DataURL upload and return the stored path.
     */
    private function uploadPhotoFromData(string $data, ?string $mimeType = null): string
    {
        $matches = [];

        if (!preg_match('/^data:image\/(jpeg|png|webp);base64,(.*)$/i', $data, $matches)) {
            throw new \RuntimeException('Invalid photo data format.');
        }

        $detected = strtolower($matches[1] ?? 'jpeg');
        $base64Data = $matches[2] ?? '';
        $decoded = base64_decode($base64Data);

        if ($decoded === false) {
            throw new \RuntimeException('Invalid base64 photo data.');
        }

        $mime = $mimeType ?: match ($detected) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };

        $extension = match ($mime) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };

        $path = sprintf('visitors/%s.%s', Str::uuid()->toString(), $extension);
        $bucket = config('services.supabase.visitor_photos_bucket');
        $baseUrl = rtrim(config('services.supabase.url'), '/');
        $serviceRoleKey = config('services.supabase.service_role_key');

        if (blank($bucket) || blank($baseUrl) || blank($serviceRoleKey)) {
            throw new \RuntimeException('Supabase storage is not configured correctly.');
        }

        $uploadEndpoint = sprintf('%s/storage/v1/object/%s/%s', $baseUrl, $bucket, $path);
        $uploadResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $serviceRoleKey,
            'apikey' => $serviceRoleKey,
            'Content-Type' => $mime,
            'x-upsert' => 'false',
        ])->withBody($decoded, $mime)->post($uploadEndpoint);

        if (!$uploadResponse->successful()) {
            throw new \RuntimeException('Failed to upload visitor photo: ' . $uploadResponse->body());
        }

        return $path;
    }

    /**
     * Update an existing biosecurity log
     */
    public function update(Request $request, $id)
    {
        $type = $request->input('type');
        $request->merge(['type' => $type]);

        $validated = $request->validate($this->getValidationRules($type, true));
        unset($validated['type']);

        // Find the log based on type after validating the submitted category.
        $log = $this->findLog($type, $id);

        // Convert IDs to display values
        $validated = $this->convertIdsToValues($type, $validated, $log);

        $log->update($validated);
        $this->clearBiosecurityLogCaches();

        return response()->json([
            'message' => 'Log updated successfully',
            'log' => $this->formatLogByType($type, $log),
        ]);
    }

    /**
     * Delete a biosecurity log
     */
    public function destroy(Request $request, $id)
    {
        $type = $request->query('type', 'Cleaning');

        $log = $this->findLog($type, $id);
        $log->delete();
        $this->clearBiosecurityLogCaches();

        return response()->json([
            'message' => 'Log deleted successfully',
        ]);
    }

    /**
     * Get validation rules based on type
     */
    private function getValidationRules($type, $isUpdate = false)
    {
        $baseRules = [
            'type' => 'required|in:Cleaning,Personnel Biosecurity Logs,Visitors,Personnel Entry Logs,Weight Sampling',
        ];

        $rules = [
            'Cleaning' => [
                'house' => 'nullable|string|max:50',
                'pen' => 'nullable|string|max:50',
                'activity' => 'nullable|string|max:255',
                'disinfectant_used' => 'nullable|string|max:255',
                'performed_by' => 'nullable|string|max:100',
                'date' => 'nullable|date|before_or_equal:today',
                'time' => 'nullable|date_format:H:i',
            ],
            'Personnel Biosecurity Logs' => [
                'name' => 'required|string|max:100',
                'role' => 'required|string|max:100',
                'date' => 'required|date|before_or_equal:today',
                'time' => 'required|date_format:H:i',
                'status' => 'required|string|in:IN,OUT',
                'foot_bath' => 'nullable|string|max:10',
                'boots_changed' => 'nullable|string|max:10',
                'protective_clothing' => 'nullable|string|max:10',
            ],
            'Visitors' => [
                'date' => 'required|date|before_or_equal:today',
                'time_in' => 'required|date_format:H:i',
                'time_out' => 'required|date_format:H:i|after_or_equal:time_in',
                'name' => 'required|string|max:100',
                'purpose' => 'required|string|max:255',
                'foot_bath' => 'required|string|in:Yes,No',
                'sanitation' => 'required|string|in:Yes,No',
                'ppe' => 'required|string|in:Yes,No',
                'monitored_by' => 'required|string|max:100',
                'photo_url' => 'nullable|string|max:255',
                'photo_data' => 'nullable|string',
            ],
            'Personnel Entry Logs' => [
                'name' => 'nullable|string|max:100',
                'role' => 'nullable|string|max:100',
                'house' => 'nullable|string|max:50',
                'date' => 'nullable|date|before_or_equal:today',
                'time' => 'nullable|date_format:H:i',
            ],
            'Weight Sampling' => [
                'date' => 'nullable|date|before_or_equal:today',
                'time' => 'nullable|date_format:H:i',
                'house' => 'nullable|string|max:50',
                'pen' => 'nullable|string|max:50',
                'batch' => 'nullable|string|max:50',
                'flocks_with_cases' => 'nullable|string|max:50',
                'age' => 'nullable|string|max:50',
                'average_weight' => 'nullable|string|max:50',
                'target' => 'nullable|string|max:50',
                'status' => 'nullable|string|max:50',
            ],
        ];

        return array_merge($baseRules, $rules[$type] ?? []);
    }

    /**
     * Convert IDs to display values
     */
    private function convertIdsToValues($type, $validated, $existingLog = null)
    {
        // House ID to house number
        if (in_array($type, ['Cleaning', 'Personnel Biosecurity Logs', 'Personnel Entry Logs', 'Weight Sampling']) && !empty($validated['house'])) {
            $house = \App\Models\House::find($validated['house']);
            if ($house) {
                $validated['house'] = $house->house_number;
            }
        }

        // Pen ID to pen name
        if (in_array($type, ['Cleaning', 'Weight Sampling']) && !empty($validated['pen'])) {
            $pen = \App\Models\Pen::find($validated['pen']);
            if ($pen) {
                $validated['pen'] = $pen->pen_name;
            }
        }

        // Employee ID to name
        $workerField = null;
        if ($type === 'Cleaning') {
            $workerField = 'performed_by';
        } elseif ($type === 'Visitors') {
            $workerField = 'monitored_by';
        }

        if ($workerField && !empty($validated[$workerField])) {
            $worker = \App\Models\Employee::find($validated[$workerField]);
            if ($worker) {
                $fullName = trim(sprintf(
                    '%s %s %s %s',
                    $worker->FirstName ?? '',
                    $worker->MiddleName ?? '',
                    $worker->LastName ?? '',
                    $worker->Suffix ?? ''
                ));
                $validated[$workerField] = $fullName ?: $worker->EmployeeId;
            }
        }

        return $validated;
    }

    /**
     * Create log based on type
     */
    private function createLog($type, $validated)
    {
        return match ($type) {
            'Cleaning' => CleaningLog::create($validated),
            'Personnel Biosecurity Logs' => PersonnelEntryLog::create($validated),
            'Visitors' => VisitorLog::create($validated),
            'Personnel Entry Logs' => PersonnelEntryLog::create($validated),
            'Weight Sampling' => WeightSamplingLog::create($validated),
            default => throw new \InvalidArgumentException("Unknown log type: $type"),
        };
    }

    /**
     * Find log based on type
     */
    private function findLog($type, $id)
    {
        return match ($type) {
            'Cleaning' => CleaningLog::findOrFail($id),
            'Personnel Biosecurity Logs' => PersonnelBiosecurityLog::findOrFail($id),
            'Visitors' => VisitorLog::findOrFail($id),
            'Personnel Entry Logs' => PersonnelEntryLog::findOrFail($id),
            'Weight Sampling' => WeightSamplingLog::findOrFail($id),
            default => throw new \InvalidArgumentException("Unknown log type: $type"),
        };
    }

    /**
     * Format log based on type
     */
    private function formatLogByType($type, $log)
    {
        return match ($type) {
            'Cleaning' => $this->formatCleaningLog($log),
            'Personnel Biosecurity Logs' => $this->formatPersonnelBiosecurityLog($log),
            'Visitors' => $this->formatVisitorLog($log),
            'Personnel Entry Logs' => $this->formatPersonnelEntryLog($log),
            'Weight Sampling' => $this->formatWeightSamplingLog($log),
            default => $log,
        };
    }

    private function formatCleaningLog($log)
    {
        return [
            'id' => $log->id,
            'type' => 'Cleaning',
            'house' => $log->house,
            'pen' => $log->pen,
            'activity' => $log->activity,
            'disinfectant_used' => $log->disinfectant_used,
            'performed_by' => $log->performed_by,
            'date' => $log->date ? $log->date->format('Y-m-d') : '',
            'time' => $log->time ? \Carbon\Carbon::parse($log->time)->format('h:i A') : '',
        ];
    }

    private function formatPersonnelBiosecurityLog($log)
    {
        $entryStatus = strtoupper((string) ($log->status ?? $log->entry_status ?? 'IN'));
        $time = $log->time ? \Carbon\Carbon::parse($log->time)->format('h:i A') : '';

        return [
            'id' => $log->id,
            'type' => 'Personnel Biosecurity Logs',
            'name' => $log->name,
            'role' => $log->role,
            'date' => $log->date ? $log->date->format('Y-m-d') : '',
            'time' => $time,
            'time_in' => $time,
            'time_out' => $entryStatus === 'OUT' ? $time : '',
            'status' => $entryStatus,
            'remarks' => $entryStatus === 'OUT' ? '' : 'Pending Out',
        ];
    }

    private function formatPersonnelBiosecurityLogs($logs)
    {
        $employees = Employee::whereIn(
            'EmployeeId',
            $logs->pluck('employee_id')->filter()->unique()->values(),
        )->get()->keyBy('EmployeeId');

        $rows = [];
        $openRowsByPerson = [];

        foreach ($logs as $log) {
            $employee = $log->employee_id ? $employees->get($log->employee_id) : null;
            $personKey = $this->getPersonnelEntryPersonKey($log);
            $status = strtoupper(trim((string) ($log->status ?: 'IN')));
            $date = $log->date ? $log->date->format('Y-m-d') : '';
            $time = $log->time ? Carbon::parse($log->time)->format('h:i A') : '';
            $sortValue = sprintf('%s %s %010d', $date, $log->time ? Carbon::parse($log->time)->format('H:i:s') : '00:00:00', $log->id);

            if ($status === 'OUT') {
                $openIndex = null;
                if (!empty($openRowsByPerson[$personKey])) {
                    $openIndex = array_shift($openRowsByPerson[$personKey]);
                }

                if ($openIndex !== null && isset($rows[$openIndex])) {
                    $rows[$openIndex]['time_out'] = $time;
                    $rows[$openIndex]['remarks'] = '';
                    $rows[$openIndex]['out_entry_id'] = $log->id;
                    $rows[$openIndex]['sort_value'] = $sortValue;
                    continue;
                }

                $rows[] = [
                    'id' => $log->id,
                    'type' => 'Personnel Biosecurity Logs',
                    'name' => $this->getPersonnelEntryName($log, $employee),
                    'role' => $this->getPersonnelEntryRole($log, $employee),
                    'date' => $date,
                    'time' => $time,
                    'time_in' => '',
                    'time_out' => $time,
                    'status' => 'OUT',
                    'remarks' => '',
                    'sort_value' => $sortValue,
                    'can_edit' => false,
                ];
                continue;
            }

            $rows[] = [
                'id' => $log->id,
                'type' => 'Personnel Biosecurity Logs',
                'name' => $this->getPersonnelEntryName($log, $employee),
                'role' => $this->getPersonnelEntryRole($log, $employee),
                'date' => $date,
                'time' => $time,
                'time_in' => $time,
                'time_out' => '',
                'status' => 'IN',
                'remarks' => 'Pending Out',
                'sort_value' => $sortValue,
                'can_edit' => false,
            ];

            $openRowsByPerson[$personKey][] = array_key_last($rows);
        }

        return collect($rows)
            ->sortByDesc('sort_value')
            ->map(function ($row) {
                unset($row['sort_value']);
                return $row;
            })
            ->values();
    }

    private function getPersonnelEntryPersonKey($log): string
    {
        if ($log->employee_id) {
            return 'employee:' . $log->employee_id;
        }

        return 'name:' . mb_strtolower(trim(($log->name ?? '') . '|' . ($log->role ?? '')));
    }

    private function getPersonnelEntryName($log, $employee): string
    {
        if (!blank($log->name)) {
            return $log->name;
        }

        if (!$employee) {
            return '';
        }

        return trim(sprintf(
            '%s %s %s %s',
            $employee->FirstName ?? '',
            $employee->MiddleName ?? '',
            $employee->LastName ?? '',
            $employee->Suffix ?? '',
        ));
    }

    private function getPersonnelEntryRole($log, $employee): string
    {
        if (!blank($log->role)) {
            return $log->role;
        }

        return $employee->Role ?? '';
    }

    private function formatVisitorLog($log)
    {
        return [
            'id' => $log->id,
            'type' => 'Visitors',
            'date' => $log->date ? $log->date->format('Y-m-d') : '',
            'time_in' => $log->time_in ? \Carbon\Carbon::parse($log->time_in)->format('h:i A') : '',
            'time_out' => $log->time_out ? \Carbon\Carbon::parse($log->time_out)->format('h:i A') : '',
            'name' => $log->name,
            'purpose' => $log->purpose,
            'foot_bath' => $log->foot_bath,
            'sanitation' => $log->sanitation,
            'ppe' => $log->ppe,
            'monitored_by' => $log->monitored_by,
            'photo_url' => $this->buildVisitorPhotoUrl($log->photo_url),
            'photo_name' => $log->photo_url ? basename($log->photo_url) : '',
        ];
    }

    private function buildVisitorPhotoUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        $baseUrl = rtrim((string) config('services.supabase.url'), '/');
        $bucket = config('services.supabase.visitor_photos_bucket');

        if (blank($baseUrl) || blank($bucket)) {
            return $path;
        }

        return sprintf(
            '%s/storage/v1/object/public/%s/%s',
            $baseUrl,
            $bucket,
            ltrim($path, '/'),
        );
    }

    private function formatPersonnelEntryLog($log)
    {
        return [
            'id' => $log->id,
            'type' => 'Personnel Entry Logs',
            'name' => $log->name,
            'role' => $log->role,
            'house' => $log->house,
            'date' => $log->date ? $log->date->format('Y-m-d') : '',
            'time' => $log->time ? \Carbon\Carbon::parse($log->time)->format('h:i A') : '',
        ];
    }

    private function formatWeightSamplingLog($log)
    {
        return [
            'id' => $log->id,
            'type' => 'Weight Sampling',
            'date' => $log->date ? $log->date->format('Y-m-d') : '',
            'time' => $log->time ? \Carbon\Carbon::parse($log->time)->format('h:i A') : '',
            'house' => $log->house,
            'pen' => $log->pen,
            'batch' => $log->batch,
            'flocks_with_cases' => $log->flocks_with_cases,
            'age' => $log->age,
            'average_weight' => $log->average_weight,
            'target' => $log->target,
            'status' => $log->status,
        ];
    }

    /**
     * Get overview statistics
     */
    private function getOverview()
    {
        $today = Carbon::today()->toDateString();

        $personnelEntered = PersonnelEntryLog::whereDate('date', $today)
            ->whereRaw('UPPER(status) = ?', ['IN'])
            ->count();

        $visitorsEntered = VisitorLog::whereDate('date', $today)
            ->whereNotNull('time_in')
            ->count();

        return [
            'personnel_entered' => $personnelEntered,
            'visitors_entered' => $visitorsEntered,
        ];
    }

    private function clearBiosecurityLogCaches(): void
    {
        foreach ([
            'Cleaning|Personnel Biosecurity Logs|Visitors|Personnel Entry Logs|Weight Sampling',
            'Personnel Biosecurity Logs|Visitors',
        ] as $categories) {
            Cache::forget('biosecurity_logs_index:' . md5($categories));
        }
    }
}
