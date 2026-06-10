<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Sensor;
use App\Models\House;
use App\Models\Pen;
use App\Models\SensorConfiguration;
use App\Models\SensorMaintenance;

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

                        $status = $sensor->status ?? null;

                        if (!$status) {
                            $latestMaintenance = $sensor->maintenances
                                ->sortByDesc('startdate')
                                ->first();

                            $status = $latestMaintenance?->status;
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

        $sensor = Sensor::create([
            'sensortype' => $validated['sensor_type'],
            'sensorname' => $validated['sensor_name'],
            'house_houseid' => $validated['house_houseid'],
            'pen_penid' => $validated['pen_penid'],
            'feeder_number' => $validated['feeder_number'] ?? null,
            'drinker_number' => $validated['drinker_number'] ?? null,
            'status' => 'Active',
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

        $sensor->update([
            'sensortype' => $validated['sensor_type'],
            'sensorname' => $validated['sensor_name'],
            'house_houseid' => $validated['house_houseid'],
            'pen_penid' => $validated['pen_penid'],
            'feeder_number' => $validated['feeder_number'] ?? null,
            'drinker_number' => $validated['drinker_number'] ?? null,
        ]);

        return response()->json($sensor);
    }

    public function destroy($sensorId)
    {
        $sensor = Sensor::findOrFail($sensorId);
        $sensor->delete();

        return response()->json(['message' => 'Sensor deleted']);
    }

    public function updateStatus(Request $request, $sensorId)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:Active,Under Maintenance',
        ]);

        $sensor = Sensor::findOrFail($sensorId);

        $sensor->update([
            'status' => $validated['status'],
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

        if (str_contains($status, 'maintenance')) {
            return 'Under Maintenance';
        }

        return 'Active';
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
            $status = 'Active';
            if ($sensor->status === 'Under Maintenance') {
                $status = 'Under Maintenance';
            } else {
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
