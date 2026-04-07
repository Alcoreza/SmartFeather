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
        $sensors = Sensor::with(['house', 'pen', 'configuration'])
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
                        return [
                            'id' => $sensor->sensorid,
                            'name' => $sensor->sensorname,
                            'house_number' => $this->formatHouseNumber($sensor->house?->house_number),
                            'pen_number' => $this->formatPenNumber($sensor->pen?->pen_name),
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

        $houses = House::orderBy('house_number')
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
        ]);

        $sensor = Sensor::create([
            'sensortype' => $validated['sensor_type'],
            'sensorname' => $validated['sensor_name'],
            'house_houseid' => $validated['house_houseid'],
            'pen_penid' => $validated['pen_penid'],
        ]);

        // If this sensor type already has thresholds configured, copy them to the new sensor.
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
        ]);

        $sensor = Sensor::findOrFail($sensorId);
        $sensor->update([
            'sensortype' => $validated['sensor_type'],
            'sensorname' => $validated['sensor_name'],
            'house_houseid' => $validated['house_houseid'],
            'pen_penid' => $validated['pen_penid'],
        ]);

        return response()->json($sensor);
    }

    public function destroy($sensorId)
    {
        $sensor = Sensor::findOrFail($sensorId);
        $sensor->delete();

        return response()->json(['message' => 'Sensor deleted']);
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
                ],
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
}
