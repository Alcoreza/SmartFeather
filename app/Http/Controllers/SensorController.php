<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Sensor;
use App\Models\House;
use App\Models\Pen;
use App\Models\SensorConfiguration;
use App\Models\SensorMaintenance;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SensorController extends Controller
{
    public function index()
    {
        return response()->json(Cache::remember('sensors_index', now()->addSeconds(10), function () {
        $sensors = Sensor::with([
                'house',
                'pen',
                'configuration',
                'maintenances',
                'latestReading' // ✅ NEW
            ])
            ->orderBy('sensortype')
            ->orderBy('sensorname')
            ->get();

        $sections = $sensors
            ->groupBy('sensortype')
            ->map(function ($group, $type) {
                return [
                    'id' => Str::slug($type ?: 'sensor'),
                    'title' => $type ?: 'Unknown Sensor',
                    'sensor_type' => $type,
                    'items' => $group->map(function (Sensor $sensor) {

                        $status = $this->effectiveSensorStatus($sensor);

                        if (!$status || $status === 'Active') {
                            $latestMaintenance = $sensor->maintenances
                                ->sortByDesc('startdate')
                                ->first();

                            if ($latestMaintenance?->status) {
                                $status = $latestMaintenance->status;
                            }
                        }

                        $status = $this->normalizeSensorStatus($status);
                        $isInactive = $status === 'Inactive';
                        $usesLiveReading = $status === 'Active';

                        // ✅ latest reading
                        $latestValue = $sensor->latestReading?->value;

                        return [
                            'id' => $sensor->sensorid,
                            'name' => $sensor->sensorname,
                            'house_number' => $isInactive ? '' : $this->formatHouseNumber($sensor->house?->house_number),
                            'pen_number' => $isInactive ? '' : $this->formatPenNumber($sensor->pen?->pen_name),
                            'feeder_number' => $isInactive ? null : $sensor->feeder_number,
                            'drinker_number' => $isInactive ? null : $sensor->drinker_number,

                            // ✅ VALUE FIELDS
                            'value' => $usesLiveReading ? $latestValue : null,
                            'formatted_value' => $this->formatSensorValue($sensor->sensortype, $usesLiveReading ? $latestValue : null),

                            // ✅ FIX: expose timestamp
                            'timestamp' => $usesLiveReading ? $sensor->latestReading?->recorded_at : null,

                            'status' => $status,
                            'house_id' => $isInactive ? null : $sensor->house?->id,
                            'pen_id' => $isInactive ? null : $sensor->pen?->id,
                            'lowest_threshold' => $sensor->configuration?->lowestthreshold,
                            'highest_threshold' => $sensor->configuration?->highestthreshold,
                        ];
                    })->values(),
                ];
            })
            ->values();

        return ['sections' => $sections];
        }));
    }


    public function formOptions()
    {
        return response()->json(Cache::remember('sensors_form_options', now()->addSeconds(30), function () {
        $sensorTypes = [
            ['value' => 'Temperature Sensor', 'label' => 'Temperature Sensor'],
            ['value' => 'Ammonia Sensor', 'label' => 'Ammonia Sensor'],
            ['value' => 'Feed Sensor', 'label' => 'Feed Sensor'],
            ['value' => 'Water Sensor', 'label' => 'Water Sensor'],
        ];

        $houses = House::whereNull('archived_at')
            ->whereHas('pens', function ($query) {
                $query->whereNull('archived_at')
                    ->whereHas('runningBatch');
            })
            ->orderBy('house_number')
            ->get()
            ->map(function (House $house) {
                return [
                    'value' => $house->id,
                    'label' => $this->formatHouseNumber($house->house_number),
                ];
            });

        return [
            'sensor_types' => $sensorTypes,
            'houses' => $houses,
        ];
        }));
    }

    public function getPensForHouse($houseId)
    {
        $pens = Cache::remember("sensors_pens_for_house:{$houseId}", now()->addSeconds(30), function () use ($houseId) {
            return Pen::where('house_id', $houseId)
            ->whereNull('archived_at')
            ->whereHas('runningBatch')
            ->orderBy('pen_name')
            ->get()
            ->map(function (Pen $pen) {
                return [
                    'value' => $pen->id,
                    'label' => $this->formatPenNumber($pen->pen_name),
                ];
            });
        });

        return response()->json(['pens' => $pens]);
    }

    public function getAvailableFeedersForPen(Request $request, $penId)
    {
        $houseId = $request->query('house_id');
        $ignoreSensorId = $request->query('ignore_sensor_id');

        $pen = Pen::where('id', $penId)
            ->when($houseId, function ($query) use ($houseId) {
                $query->where('house_id', $houseId);
            })
            ->whereNull('archived_at')
            ->whereHas('runningBatch')
            ->firstOrFail();

        $usedFeeders = Sensor::where('sensortype', 'Feed Sensor')
            ->where('house_houseid', $pen->house_id)
            ->where('pen_penid', $pen->id)
            ->whereNotNull('feeder_number')
            ->whereRaw("COALESCE(NULLIF(LOWER(TRIM(status)), ''), 'active') != 'inactive'")
            ->when($ignoreSensorId, function ($query) use ($ignoreSensorId) {
                $query->where('sensorid', '!=', $ignoreSensorId);
            })
            ->pluck('feeder_number')
            ->map(fn ($number) => (int) $number)
            ->all();

        $availableFeeders = [];
        for ($number = 1; $number <= (int) $pen->feeder_count; $number++) {
            if (!in_array($number, $usedFeeders, true)) {
                $availableFeeders[] = [
                    'value' => $number,
                    'label' => 'Feeder ' . $number,
                ];
            }
        }

        return response()->json([
            'feeders' => $availableFeeders,
            'feeder_count' => (int) $pen->feeder_count,
        ]);
    }

    public function getAvailableDrinkersForPen(Request $request, $penId)
    {
        $houseId = $request->query('house_id');
        $ignoreSensorId = $request->query('ignore_sensor_id');

        $pen = Pen::where('id', $penId)
            ->when($houseId, function ($query) use ($houseId) {
                $query->where('house_id', $houseId);
            })
            ->whereNull('archived_at')
            ->whereHas('runningBatch')
            ->firstOrFail();

        $usedDrinkers = Sensor::where('sensortype', 'Water Sensor')
            ->where('house_houseid', $pen->house_id)
            ->where('pen_penid', $pen->id)
            ->whereNotNull('drinker_number')
            ->whereRaw("COALESCE(NULLIF(LOWER(TRIM(status)), ''), 'active') != 'inactive'")
            ->when($ignoreSensorId, function ($query) use ($ignoreSensorId) {
                $query->where('sensorid', '!=', $ignoreSensorId);
            })
            ->pluck('drinker_number')
            ->map(fn ($number) => (int) $number)
            ->all();

        $availableDrinkers = [];
        for ($number = 1; $number <= (int) $pen->drinker_count; $number++) {
            if (!in_array($number, $usedDrinkers, true)) {
                $availableDrinkers[] = [
                    'value' => $number,
                    'label' => 'Drinker ' . $number,
                ];
            }
        }

        return response()->json([
            'drinkers' => $availableDrinkers,
            'drinker_count' => (int) $pen->drinker_count,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sensor_type' => 'required|string|max:255',
            'sensor_name' => 'required|string|max:255',
            'house_houseid' => 'nullable|integer|exists:house,id',
            'pen_penid' => 'nullable|integer|exists:pen,id',
            'feeder_number' => 'nullable|integer|min:1',
            'drinker_number' => 'nullable|integer|min:1',
        ]);

        $assignment = $this->normalizeSensorAssignment($validated);
        $resourceNumbers = ['feeder_number' => null, 'drinker_number' => null];

        if ($this->sensorHasAssignment($assignment)) {
            $this->ensureSensorAssignmentHasRunningBatch(
                $assignment['house_houseid'],
                $assignment['pen_penid']
            );
            $resourceNumbers = $this->validateSensorResourceNumber(
                $validated['sensor_type'],
                $assignment['house_houseid'],
                $assignment['pen_penid'],
                $validated['feeder_number'] ?? null,
                $validated['drinker_number'] ?? null
            );
        }

        $sensor = Sensor::create([
            'sensortype' => $validated['sensor_type'],
            'sensorname' => $validated['sensor_name'],
            'house_houseid' => $assignment['house_houseid'],
            'pen_penid' => $assignment['pen_penid'],
            'feeder_number' => $resourceNumbers['feeder_number'],
            'drinker_number' => $resourceNumbers['drinker_number'],
            'status' => $this->determineStoredSensorStatus(
                $assignment['house_houseid'],
                $assignment['pen_penid'],
                'Active'
            ),
        ]);

        $existingThreshold = SensorConfiguration::whereIn(
            'sensors_sensorid',
            Sensor::where('sensortype', $validated['sensor_type'])
                ->where('sensorid', '!=', $sensor->sensorid)
                ->pluck('sensorid')
        )->first();

        if ($existingThreshold) {
            SensorConfiguration::create([
                'sensors_sensorid' => $sensor->sensorid,
                'lowestthreshold' => $existingThreshold->lowestthreshold,
                'highestthreshold' => $existingThreshold->highestthreshold,
            ]);
        }

        $this->clearSensorCaches();

        return response()->json($sensor, 201);
    }

    public function update(Request $request, $sensorId)
    {
        $validated = $request->validate([
            'sensor_type' => 'required|string|max:255',
            'sensor_name' => 'required|string|max:255',
            'house_houseid' => 'nullable|integer|exists:house,id',
            'pen_penid' => 'nullable|integer|exists:pen,id',
            'feeder_number' => 'nullable|integer|min:1',
            'drinker_number' => 'nullable|integer|min:1',
        ]);

        $sensor = Sensor::findOrFail($sensorId);
        $assignment = $this->normalizeSensorAssignment($validated);
        $resourceNumbers = ['feeder_number' => null, 'drinker_number' => null];

        if ($this->sensorHasAssignment($assignment)) {
            $this->ensureSensorAssignmentHasRunningBatch(
                $assignment['house_houseid'],
                $assignment['pen_penid']
            );
            $resourceNumbers = $this->validateSensorResourceNumber(
                $validated['sensor_type'],
                $assignment['house_houseid'],
                $assignment['pen_penid'],
                $validated['feeder_number'] ?? null,
                $validated['drinker_number'] ?? null,
                $sensor->sensorid
            );
        }

        $sensor->update([
            'sensortype' => $validated['sensor_type'],
            'sensorname' => $validated['sensor_name'],
            'house_houseid' => $assignment['house_houseid'],
            'pen_penid' => $assignment['pen_penid'],
            'feeder_number' => $resourceNumbers['feeder_number'],
            'drinker_number' => $resourceNumbers['drinker_number'],
            'status' => $this->determineStoredSensorStatus(
                $assignment['house_houseid'],
                $assignment['pen_penid'],
                $sensor->status
            ),
        ]);

        $this->clearSensorCaches();

        return response()->json($sensor);
    }

    public function destroy($sensorId)
    {
        $sensor = Sensor::findOrFail($sensorId);

        DB::transaction(function () use ($sensor) {
            $sensor->configuration()->delete();
            $sensor->maintenances()->delete();
            $sensor->readings()->delete();
            $sensor->delete();
        });

        $this->clearSensorCaches();

        return response()->json(['message' => 'Sensor deleted']);
    }

    public function updateStatus(Request $request, $sensorId)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:Active,Under Maintenance,Inactive',
        ]);

        $sensor = Sensor::findOrFail($sensorId);
        $requestedStatus = $this->normalizeSensorStatus($validated['status']);

        DB::transaction(function () use ($sensor, $requestedStatus) {
            if ($requestedStatus === 'Inactive') {
                $sensor->update([
                    'house_houseid' => null,
                    'pen_penid' => null,
                    'feeder_number' => null,
                    'drinker_number' => null,
                    'status' => 'Inactive',
                ]);

                $this->recordSensorMaintenanceStatus($sensor, 'Inactive');

                return;
            }

            $storedStatus = $this->determineStoredSensorStatus(
                $sensor->house_houseid,
                $sensor->pen_penid,
                $requestedStatus
            );

            $sensor->update([
                'status' => $storedStatus,
            ]);

            $this->recordSensorMaintenanceStatus($sensor, $storedStatus);
        });

        $this->clearSensorCaches();

        return response()->json([
            'message' => 'Sensor status updated successfully.',
            'sensor' => $sensor->fresh(),
        ]);
    }

    public function updateThresholds(Request $request)
    {
        $validated = $request->validate([
            'sensor_type' => 'required|string|max:255',
            'lowest_threshold' => 'nullable|integer',
            'highest_threshold' => 'nullable|integer',
        ]);

        $sensors = Sensor::where('sensortype', $validated['sensor_type'])->get();

        foreach ($sensors as $sensor) {
            SensorConfiguration::updateOrCreate(
                ['sensors_sensorid' => $sensor->sensorid],
                [
                    'lowestthreshold' => $validated['lowest_threshold'],
                    'highestthreshold' => $validated['highest_threshold'],
                ]
            );
        }

        $this->clearSensorCaches();

        return response()->json(['updated' => $sensors->count()]);
    }

    public function managerMaintenanceRecords()
    {
        $this->syncCurrentMaintenanceSensorsToLog();

        $records = SensorMaintenance::with(['sensor.house', 'sensor.pen'])
            ->orderByDesc('startdate')
            ->orderByDesc('maintenanceid')
            ->get()
            ->map(function (SensorMaintenance $record) {
                $sensor = $record->sensor;

                return [
                    'name' => $sensor?->sensorname ?? '',
                    'sensor_type' => $sensor?->sensortype ?? '',
                    'house_number' => $this->formatHouseNumber($sensor?->house?->house_number),
                    'pen_number' => $this->formatPenNumber($sensor?->pen?->pen_name),
                    'maintenance_date' => $this->formatMaintenanceDate($record->startdate),
                ];
            });

        return response()->json(['records' => $records]);
    }

    public function adminMaintenanceRecords()
    {
        return $this->managerMaintenanceRecords();
    }

    private function normalizeSensorStatus($status)
    {
        $status = trim(strtolower((string) $status));

        if ($status === '' || $status === 'active') {
            return 'Active';
        }

        if ($status === 'inactive') {
            return 'Inactive';
        }

        if (str_contains($status, 'maintenance')) {
            return 'Under Maintenance';
        }

        return 'Active';
    }

    private function effectiveSensorStatus(Sensor $sensor): string
    {
        return $this->determineStoredSensorStatus(
            $sensor->house_houseid,
            $sensor->pen_penid,
            $sensor->status
        );
    }

    private function determineStoredSensorStatus($houseId, $penId, $preferredStatus): string
    {
        if (!$houseId && !$penId) {
            return 'Inactive';
        }

        $houseIsArchived = House::where('id', $houseId)
            ->whereNotNull('archived_at')
            ->exists();

        if ($houseIsArchived) {
            return 'Inactive';
        }

        $status = $this->normalizeSensorStatus($preferredStatus);

        return $status === 'Inactive' ? 'Active' : $status;
    }

    private function normalizeSensorAssignment(array $validated): array
    {
        $houseId = $validated['house_houseid'] ?? null;
        $penId = $validated['pen_penid'] ?? null;

        $houseId = $houseId === '' ? null : $houseId;
        $penId = $penId === '' ? null : $penId;

        if (($houseId && !$penId) || (!$houseId && $penId)) {
            throw ValidationException::withMessages([
                'pen_penid' => ['Select both a house and pen, or leave both blank to keep the sensor inactive.'],
            ]);
        }

        return [
            'house_houseid' => $houseId ? (int) $houseId : null,
            'pen_penid' => $penId ? (int) $penId : null,
        ];
    }

    private function sensorHasAssignment(array $assignment): bool
    {
        return !empty($assignment['house_houseid']) && !empty($assignment['pen_penid']);
    }

    private function recordSensorMaintenanceStatus(Sensor $sensor, string $status): void
    {
        if ($status === 'Under Maintenance') {
            $this->createOpenMaintenanceLogIfMissing($sensor);

            return;
        }

        if (in_array($status, ['Active', 'Inactive'], true)) {
            $this->closeOpenMaintenanceLog($sensor);
        }
    }

    private function closeOpenMaintenanceLog(Sensor $sensor): void
    {
        $openMaintenance = SensorMaintenance::where('sensors_sensorid', $sensor->sensorid)
            ->whereNull('enddate')
            ->orderByDesc('startdate')
            ->first();

        $openMaintenance?->update([
            'enddate' => now()->toDateString(),
            'status' => 'Completed',
        ]);
    }

    private function syncCurrentMaintenanceSensorsToLog(): void
    {
        Sensor::whereRaw('LOWER(TRIM(status)) = ?', ['under maintenance'])
            ->get()
            ->each(function (Sensor $sensor) {
                $this->createOpenMaintenanceLogIfMissing($sensor);
            });
    }

    private function createOpenMaintenanceLogIfMissing(Sensor $sensor): void
    {
        $openMaintenance = SensorMaintenance::where('sensors_sensorid', $sensor->sensorid)
            ->whereNull('enddate')
            ->orderByDesc('startdate')
            ->first();

        if ($openMaintenance) {
            return;
        }

        SensorMaintenance::create([
            'sensors_sensorid' => $sensor->sensorid,
            'startdate' => now()->toDateString(),
            'enddate' => null,
            'status' => 'Maintenance',
        ]);
    }

    private function ensureSensorAssignmentHasRunningBatch($houseId, $penId): void
    {
        $hasRunningBatch = Pen::where('id', $penId)
            ->where('house_id', $houseId)
            ->whereNull('archived_at')
            ->whereHas('runningBatch')
            ->exists();

        if (!$hasRunningBatch) {
            throw ValidationException::withMessages([
                'pen_penid' => ['Select a pen with a running batch in the selected house.'],
            ]);
        }
    }

    private function validateSensorResourceNumber(string $sensorType, $houseId, $penId, $feederNumber, $drinkerNumber, $ignoreSensorId = null): array
    {
        $pen = Pen::find($penId);
        $normalizedType = strtolower(trim($sensorType));

        if ($normalizedType === 'feed sensor') {
            if (!$feederNumber) {
                throw ValidationException::withMessages([
                    'feeder_number' => ['Feeder number is required for feed sensors.'],
                ]);
            }

            if ((int) $feederNumber > (int) ($pen?->feeder_count ?? 0)) {
                throw ValidationException::withMessages([
                    'feeder_number' => [
                        'Feeder number cannot exceed this pen\'s configured feeder count (' . (int) ($pen?->feeder_count ?? 0) . ').',
                    ],
                ]);
            }

            $feederIsAssigned = Sensor::where('sensortype', 'Feed Sensor')
                ->where('house_houseid', $houseId)
                ->where('pen_penid', $penId)
                ->where('feeder_number', (int) $feederNumber)
                ->whereRaw("COALESCE(NULLIF(LOWER(TRIM(status)), ''), 'active') != 'inactive'")
                ->when($ignoreSensorId, function ($query) use ($ignoreSensorId) {
                    $query->where('sensorid', '!=', $ignoreSensorId);
                })
                ->exists();

            if ($feederIsAssigned) {
                throw ValidationException::withMessages([
                    'feeder_number' => ['This feeder already has an assigned feed sensor.'],
                ]);
            }

            return [
                'feeder_number' => (int) $feederNumber,
                'drinker_number' => null,
            ];
        }

        if ($normalizedType === 'water sensor') {
            if (!$drinkerNumber) {
                throw ValidationException::withMessages([
                    'drinker_number' => ['Drinker number is required for water sensors.'],
                ]);
            }

            if ((int) $drinkerNumber > (int) ($pen?->drinker_count ?? 0)) {
                throw ValidationException::withMessages([
                    'drinker_number' => [
                        'Drinker number cannot exceed this pen\'s configured drinker count (' . (int) ($pen?->drinker_count ?? 0) . ').',
                    ],
                ]);
            }

            $drinkerIsAssigned = Sensor::where('sensortype', 'Water Sensor')
                ->where('house_houseid', $houseId)
                ->where('pen_penid', $penId)
                ->where('drinker_number', (int) $drinkerNumber)
                ->whereRaw("COALESCE(NULLIF(LOWER(TRIM(status)), ''), 'active') != 'inactive'")
                ->when($ignoreSensorId, function ($query) use ($ignoreSensorId) {
                    $query->where('sensorid', '!=', $ignoreSensorId);
                })
                ->exists();

            if ($drinkerIsAssigned) {
                throw ValidationException::withMessages([
                    'drinker_number' => ['This drinker already has an assigned water sensor.'],
                ]);
            }

            return [
                'feeder_number' => null,
                'drinker_number' => (int) $drinkerNumber,
            ];
        }

        if (in_array($normalizedType, ['temperature sensor', 'ammonia sensor'], true)) {
            $sensorIsAssigned = Sensor::whereRaw('lower(sensortype) = ?', [$normalizedType])
                ->where('house_houseid', $houseId)
                ->where('pen_penid', $penId)
                ->whereRaw("COALESCE(NULLIF(LOWER(TRIM(status)), ''), 'active') != 'inactive'")
                ->when($ignoreSensorId, function ($query) use ($ignoreSensorId) {
                    $query->where('sensorid', '!=', $ignoreSensorId);
                })
                ->exists();

            if ($sensorIsAssigned) {
                throw ValidationException::withMessages([
                    'sensor_type' => [ucfirst(str_replace(' sensor', '', $normalizedType)) . ' sensor is already assigned to this pen.'],
                ]);
            }
        }

        return [
            'feeder_number' => null,
            'drinker_number' => null,
        ];
    }

    private function formatHouseNumber($houseNumber)
    {
        if (!$houseNumber) {
            return '';
        }

        return (string) $houseNumber;
    }

    private function formatPenNumber($penName)
    {
        if (!$penName) {
            return '';
        }

        if (preg_match('/\d+$/', $penName, $matches)) {
            return $matches[0];
        }

        return $penName;
    }

    private function formatMaintenanceDate($date): string
    {
        if (!$date) {
            return '';
        }

        return \Carbon\Carbon::parse($date)->toDateString();
    }

    // ✅ VALUE FORMATTER
    private function formatSensorValue($type, $value)
    {
        if ($value === null) return 'No Data';

        $displayValue = number_format($this->truncateToFirstDecimal((float) $value), 1);

        switch ($type) {
            case 'Temperature Sensor':
                return $displayValue . ' °C';

            case 'Ammonia Sensor':
                return $displayValue . ' ppm';

            case 'Feed Sensor':
                return $displayValue . ' mm';

            case 'Water Sensor':
                return $displayValue . ' level';

            default:
                return is_numeric($value) ? $displayValue : $value;
        }
    }

    private function truncateToFirstDecimal(float $value): float
    {
        $shifted = $value * 10;

        return ($value < 0 ? ceil($shifted) : floor($shifted)) / 10;
    }

    /**
     * Get all sensor readings with latest value for each sensor
     */
    public function sensorReadings()
    {
        return response()->json(Cache::remember('sensors_readings', now()->addSeconds(10), function () {
        $sensors = Sensor::with([
                'house',
                'pen',
                'configuration',
                'latestReading'
            ])
            ->orderBy('sensortype')
            ->orderBy('sensorname')
            ->get();

        $readings = $sensors->map(function (Sensor $sensor) {
            $latestValue = $sensor->latestReading?->value;
            $status = $this->normalizeSensorStatus($this->effectiveSensorStatus($sensor));
            $isInactive = $status === 'Inactive';
            $usesLiveReading = $status === 'Active';

            return [
                'sensor_id' => $sensor->sensorid,
                'sensor_name' => $sensor->sensorname,
                'sensor_type' => $sensor->sensortype,
                'house_number' => $isInactive ? '' : $this->formatHouseNumber($sensor->house?->house_number),
                'pen_number' => $isInactive ? '' : $this->formatPenNumber($sensor->pen?->pen_name),
                'feeder_number' => $isInactive ? null : $sensor->feeder_number,
                'drinker_number' => $isInactive ? null : $sensor->drinker_number,
                'value' => $usesLiveReading ? $latestValue : null,
                'formatted_value' => $this->formatSensorValue($sensor->sensortype, $usesLiveReading ? $latestValue : null),
                'recorded_at' => $usesLiveReading ? $sensor->latestReading?->recorded_at : null,
                'status' => $status,
                'lowest_threshold' => $sensor->configuration?->lowestthreshold,
                'highest_threshold' => $sensor->configuration?->highestthreshold,
            ];
        });

        return ['readings' => $readings];
        }));
    }

    /**
     * Get sensor health/status for farm management dashboard
     */
    public function sensorHealth()
    {
        return response()->json(Cache::remember('sensors_health', now()->addSeconds(10), function () {
        $sensors = Sensor::with(['house', 'pen', 'configuration', 'latestReading', 'maintenances'])
            ->orderBy('sensortype')
            ->orderBy('sensorname')
            ->get();

        $health = $sensors->map(function (Sensor $sensor) {
            $latestValue = $sensor->latestReading?->value;
            $latestReading = $sensor->latestReading;
            $config = $sensor->configuration;

            // Determine status
            $status = $this->effectiveSensorStatus($sensor);
            if ($status === 'Active') {
                $latestMaintenance = $sensor->maintenances->sortByDesc('startdate')->first();
                if ($latestMaintenance?->status === 'Maintenance') {
                    $status = 'Under Maintenance';
                }
            }
            $status = $this->normalizeSensorStatus($status);
            $isInactive = $status === 'Inactive';
            $usesLiveReading = $status === 'Active';

            // Determine alert level based on thresholds
            $alertLevel = 'normal'; // normal, warning, critical
            if ($usesLiveReading && $latestValue !== null && $config) {
                if ($latestValue < $config->lowestthreshold || $latestValue > $config->highestthreshold) {
                    $alertLevel = 'critical';
                } elseif (
                    ($config->highestthreshold && $latestValue > $config->highestthreshold * 0.9) ||
                    ($config->lowestthreshold && $latestValue < $config->lowestthreshold * 1.1)
                ) {
                    $alertLevel = 'warning';
                }
            }

            // Check if sensor is stale (no readings in 30 minutes)
            $isStaleFeed = false;
            if ($usesLiveReading && $latestReading) {
                $lastReadingMinutesAgo = $latestReading->recorded_at->diffInMinutes(now());
                $isStaleFeed = $lastReadingMinutesAgo > 30;
            }

            return [
                'sensor_id' => $sensor->sensorid,
                'sensor_name' => $sensor->sensorname,
                'sensor_type' => $sensor->sensortype,
                'house_number' => $isInactive ? '' : $this->formatHouseNumber($sensor->house?->house_number),
                'pen_number' => $isInactive ? '' : $this->formatPenNumber($sensor->pen?->pen_name),
                'feeder_number' => $isInactive ? null : $sensor->feeder_number,
                'drinker_number' => $isInactive ? null : $sensor->drinker_number,
                'current_value' => $usesLiveReading ? $latestValue : null,
                'formatted_value' => $this->formatSensorValue($sensor->sensortype, $usesLiveReading ? $latestValue : null),
                'lowest_threshold' => $config?->lowestthreshold,
                'highest_threshold' => $config?->highestthreshold,
                'status' => $status,
                'alert_level' => $alertLevel,
                'is_stale' => $isStaleFeed,
                'last_reading_at' => $usesLiveReading ? $latestReading?->recorded_at : null,
                'minutes_since_last_reading' => $usesLiveReading && $latestReading ? $latestReading->recorded_at->diffInMinutes(now()) : null,
            ];
        });

        return ['health' => $health];
        }));
    }

    private function clearSensorCaches(): void
    {
        Cache::forget('sensors_index');
        Cache::forget('sensors_form_options');
        Cache::forget('sensors_readings');
        Cache::forget('sensors_health');
    }
}
