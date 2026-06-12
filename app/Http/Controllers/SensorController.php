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
use Illuminate\Support\Facades\DB;

class SensorController extends Controller
{
    public function index()
    {
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

                        // ✅ latest reading
                        $latestValue = $sensor->latestReading?->value;

                        return [
                            'id' => $sensor->sensorid,
                            'name' => $sensor->sensorname,
                            'house_number' => $this->formatHouseNumber($sensor->house?->house_number),
                            'pen_number' => $this->formatPenNumber($sensor->pen?->pen_name),
                            'feeder_number' => $sensor->feeder_number,
                            'drinker_number' => $sensor->drinker_number,

                            // ✅ VALUE FIELDS
                            'value' => $latestValue,
                            'formatted_value' => $this->formatSensorValue($sensor->sensortype, $latestValue),

                            // ✅ FIX: expose timestamp
                            'timestamp' => $sensor->latestReading?->recorded_at,

                            'status' => $status,
                            'house_id' => $sensor->house?->id,
                            'pen_id' => $sensor->pen?->id,
                            'lowest_threshold' => $sensor->configuration?->lowestthreshold,
                            'highest_threshold' => $sensor->configuration?->highestthreshold,
                        ];
                    })->values(),
                ];
            })
            ->values();

        return response()->json(['sections' => $sections]);
    }


    public function formOptions()
    {
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

        return response()->json([
            'sensor_types' => $sensorTypes,
            'houses' => $houses,
        ]);
    }

    public function getPensForHouse($houseId)
    {
        $pens = Pen::where('house_id', $houseId)
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
            'house_houseid' => 'required|integer|exists:house,id',
            'pen_penid' => 'required|integer|exists:pen,id',
            'feeder_number' => 'nullable|integer|min:1',
            'drinker_number' => 'nullable|integer|min:1',
        ]);

        $this->ensureSensorAssignmentHasRunningBatch(
            $validated['house_houseid'],
            $validated['pen_penid']
        );
        $resourceNumbers = $this->validateSensorResourceNumber(
            $validated['sensor_type'],
            $validated['house_houseid'],
            $validated['pen_penid'],
            $validated['feeder_number'] ?? null,
            $validated['drinker_number'] ?? null
        );

        $sensor = Sensor::create([
            'sensortype' => $validated['sensor_type'],
            'sensorname' => $validated['sensor_name'],
            'house_houseid' => $validated['house_houseid'],
            'pen_penid' => $validated['pen_penid'],
            'feeder_number' => $resourceNumbers['feeder_number'],
            'drinker_number' => $resourceNumbers['drinker_number'],
            'status' => $this->determineStoredSensorStatus(
                $validated['house_houseid'] ?? null,
                $validated['pen_penid'] ?? null,
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

        return response()->json($sensor, 201);
    }

    public function update(Request $request, $sensorId)
    {
        $validated = $request->validate([
            'sensor_type' => 'required|string|max:255',
            'sensor_name' => 'required|string|max:255',
            'house_houseid' => 'required|integer|exists:house,id',
            'pen_penid' => 'required|integer|exists:pen,id',
            'feeder_number' => 'nullable|integer|min:1',
            'drinker_number' => 'nullable|integer|min:1',
        ]);

        $sensor = Sensor::findOrFail($sensorId);

        $this->ensureSensorAssignmentHasRunningBatch(
            $validated['house_houseid'],
            $validated['pen_penid']
        );
        $resourceNumbers = $this->validateSensorResourceNumber(
            $validated['sensor_type'],
            $validated['house_houseid'],
            $validated['pen_penid'],
            $validated['feeder_number'] ?? null,
            $validated['drinker_number'] ?? null,
            $sensor->sensorid
        );

        $sensor->update([
            'sensortype' => $validated['sensor_type'],
            'sensorname' => $validated['sensor_name'],
            'house_houseid' => $validated['house_houseid'],
            'pen_penid' => $validated['pen_penid'],
            'feeder_number' => $resourceNumbers['feeder_number'],
            'drinker_number' => $resourceNumbers['drinker_number'],
            'status' => $this->determineStoredSensorStatus(
                $validated['house_houseid'] ?? null,
                $validated['pen_penid'] ?? null,
                $sensor->status
            ),
        ]);

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

        return response()->json(['message' => 'Sensor deleted']);
    }

    public function updateStatus(Request $request, $sensorId)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:Active,Under Maintenance',
        ]);

        $sensor = Sensor::findOrFail($sensorId);

        $sensor->update([
            'status' => $this->determineStoredSensorStatus(
                $sensor->house_houseid,
                $sensor->pen_penid,
                $validated['status']
            ),
        ]);

        return response()->json([
            'message' => 'Sensor status updated successfully.',
            'sensor' => $sensor,
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

        return response()->json(['updated' => $sensors->count()]);
    }

    public function managerMaintenanceRecords()
    {
        $records = SensorMaintenance::with(['sensor.house'])
            ->orderByDesc('startdate')
            ->get()
            ->map(function (SensorMaintenance $record) {
                return [
                    'sensor_type' => $record->sensor?->sensortype ?? '',
                    'name' => $record->sensor?->sensorname ?? '',
                    'house_number' => $this->formatHouseNumber($record->sensor?->house?->house_number),
                    'start_date' => $record->startdate,
                    'end_date' => $record->enddate,
                    'status' => $record->status,
                ];
            });

        return response()->json(['records' => $records]);
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
        $houseIsArchived = House::where('id', $houseId)
            ->whereNotNull('archived_at')
            ->exists();

        if ($houseIsArchived) {
            return 'Inactive';
        }

        $status = $this->normalizeSensorStatus($preferredStatus);

        return $status === 'Inactive' ? 'Active' : $status;
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

        if (preg_match('/\d+$/', $houseNumber, $matches)) {
            return $matches[0];
        }

        return $houseNumber;
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

    // ✅ VALUE FORMATTER
    private function formatSensorValue($type, $value)
    {
        if ($value === null) return 'No Data';

        switch ($type) {
            case 'Temperature Sensor':
                return number_format($value, 1) . ' °C';

            case 'Ammonia Sensor':
                return $value . ' ppm';

            case 'Feed Sensor':
                return $value . ' mm';

            case 'Water Sensor':
                return $value . ' level';

            default:
                return $value;
        }
    }

    /**
     * Get all sensor readings with latest value for each sensor
     */
    public function sensorReadings()
    {
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

            return [
                'sensor_id' => $sensor->sensorid,
                'sensor_name' => $sensor->sensorname,
                'sensor_type' => $sensor->sensortype,
                'house_number' => $this->formatHouseNumber($sensor->house?->house_number),
                'pen_number' => $this->formatPenNumber($sensor->pen?->pen_name),
                'feeder_number' => $sensor->feeder_number,
                'drinker_number' => $sensor->drinker_number,
                'value' => $latestValue,
                'formatted_value' => $this->formatSensorValue($sensor->sensortype, $latestValue),
                'recorded_at' => $sensor->latestReading?->recorded_at,
                'lowest_threshold' => $sensor->configuration?->lowestthreshold,
                'highest_threshold' => $sensor->configuration?->highestthreshold,
            ];
        });

        return response()->json(['readings' => $readings]);
    }

    /**
     * Get sensor health/status for farm management dashboard
     */
    public function sensorHealth()
    {
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

            // Determine alert level based on thresholds
            $alertLevel = 'normal'; // normal, warning, critical
            if ($latestValue !== null && $config) {
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
            if ($latestReading) {
                $lastReadingMinutesAgo = $latestReading->recorded_at->diffInMinutes(now());
                $isStaleFeed = $lastReadingMinutesAgo > 30;
            }

            return [
                'sensor_id' => $sensor->sensorid,
                'sensor_name' => $sensor->sensorname,
                'sensor_type' => $sensor->sensortype,
                'house_number' => $this->formatHouseNumber($sensor->house?->house_number),
                'pen_number' => $this->formatPenNumber($sensor->pen?->pen_name),
                'feeder_number' => $sensor->feeder_number,
                'drinker_number' => $sensor->drinker_number,
                'current_value' => $latestValue,
                'formatted_value' => $this->formatSensorValue($sensor->sensortype, $latestValue),
                'lowest_threshold' => $config?->lowestthreshold,
                'highest_threshold' => $config?->highestthreshold,
                'status' => $status,
                'alert_level' => $alertLevel,
                'is_stale' => $isStaleFeed,
                'last_reading_at' => $latestReading?->recorded_at,
                'minutes_since_last_reading' => $latestReading ? $latestReading->recorded_at->diffInMinutes(now()) : null,
            ];
        });

        return response()->json(['health' => $health]);
    }
}
