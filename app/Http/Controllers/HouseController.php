<?php

namespace App\Http\Controllers;

use App\Models\House;
use App\Models\Pen;
use App\Models\Sensor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;


class HouseController extends Controller
{
    /**
     * Get all houses with their pens
     */
    public function index()
    {
        try {
            $houses = House::with([
                'pens' => function ($query) {
                    $query->whereNull('archived_at');
                },
                'pens.currentBatch:id,batch_code,started_at,status',
                'pens.runningBatch:id,batch_code,pen_id,started_at,status',
                'pens.latestWeightSamplingLog' => function ($query) {
                    $query->select(
                        'weight_sampling_logs.id',
                        'weight_sampling_logs.pen_id',
                        'weight_sampling_logs.status',
                        'weight_sampling_logs.date',
                        'weight_sampling_logs.time',
                        'weight_sampling_logs.created_at'
                    );
                },
            ])
                ->whereNull('archived_at')
                ->orderBy('id', 'asc')
                ->get();

            $this->attachLatestSensorReadings($houses);

            return response()->json([
                'success' => true,
                'data' => $houses,
                'message' => 'Houses retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving houses: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created house in database
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'house_number' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('house', 'house_number')->whereNull('archived_at'),
                ],
                'number_of_pens' => 'required|integer|min:1|max:100',
                'status' => 'nullable|string|max:50',
                'start_date' => 'nullable|date',
                'pen_capacities' => 'nullable|array',
                'pen_capacities.*' => 'nullable|integer|min:0',
                'pen_feeder_counts' => 'required|array',
                'pen_feeder_counts.*' => 'required|integer|min:0',
                'pen_drinker_counts' => 'required|array',
                'pen_drinker_counts.*' => 'required|integer|min:0',
            ]);

            $penCapacities = $validated['pen_capacities'] ?? [];
            $penFeederCounts = $validated['pen_feeder_counts'] ?? [];
            $penDrinkerCounts = $validated['pen_drinker_counts'] ?? [];

            $house = DB::transaction(function () use ($validated, $penCapacities, $penFeederCounts, $penDrinkerCounts) {
                // Create the house
                $house = House::create([
                    'house_number' => $validated['house_number'],
                    'number_of_pens' => $validated['number_of_pens'],
                    'status' => $validated['status'] ?? 'active',
                    'start_date' => $validated['start_date'] ?? now()->format('Y-m-d'),
                ]);

                // Create pens automatically with the requested capacity values
                for ($i = 1; $i <= $validated['number_of_pens']; $i++) {
                    Pen::create([
                        'house_id' => $house->id,
                        'pen_name' => "Pen $i",
                        'capacity' => $penCapacities[$i - 1] ?? 0,
                        'population' => 0,
                        'eggs_hatched' => 0,
                        'mortality' => 0,
                        'feeder_count' => $penFeederCounts[$i - 1] ?? 0,
                        'drinker_count' => $penDrinkerCounts[$i - 1] ?? 0,
                        'recorded_at' => now(),
                    ]);
                }

                return $house;
            });

            // Reload with relationships
            $house->load('pens');

            return response()->json([
                'success' => true,
                'data' => $house,
                'message' => 'House and pen setup saved successfully.'
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating house: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific house
     */
    public function show($id)
    {
        try {
            $house = House::with([
                'pens' => function ($query) {
                    $query->whereNull('archived_at');
                },
                'pens.currentBatch:id,batch_code,started_at,status',
                'pens.runningBatch:id,batch_code,pen_id,started_at,status',
                'pens.latestWeightSamplingLog' => function ($query) {
                    $query->select(
                        'weight_sampling_logs.id',
                        'weight_sampling_logs.pen_id',
                        'weight_sampling_logs.status',
                        'weight_sampling_logs.date',
                        'weight_sampling_logs.time',
                        'weight_sampling_logs.created_at'
                    );
                },
            ])
                ->whereNull('archived_at')
                ->find($id);

            if (!$house) {
                return response()->json([
                    'success' => false,
                    'message' => 'House not found'
                ], 404);
            }

            $this->attachLatestSensorReadings(collect([$house]));

            return response()->json([
                'success' => true,
                'data' => $house,
                'message' => 'House retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving house: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a house
     */
    public function update(Request $request, $id)
    {
        try {
            $house = House::whereNull('archived_at')->find($id);

            if (!$house) {
                return response()->json([
                    'success' => false,
                    'message' => 'House not found'
                ], 404);
            }

            $validated = $request->validate([
                'house_number' => [
                    'nullable',
                    'string',
                    'max:255',
                    Rule::unique('house', 'house_number')->whereNull('archived_at')->ignore($house->id),
                ],
                'status' => 'nullable|string|max:50',
                'start_date' => 'nullable|date',
            ]);

            $house->update($validated);
            $house->load('pens');

            return response()->json([
                'success' => true,
                'data' => $house,
                'message' => 'House updated successfully'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating house: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Soft archive a house and its pens.
     */
    public function archive($id)
    {
        try {
            $house = House::whereNull('archived_at')->find($id);

            if (!$house) {
                return response()->json([
                    'success' => false,
                    'message' => 'House not found or already archived'
                ], 404);
            }

            $hasActiveBatch = Pen::where('house_id', $house->id)
                ->whereNull('archived_at')
                ->whereNotNull('current_batch_id')
                ->whereHas('currentBatch', function ($query) {
                    $query->where('status', 'Running');
                })
                ->exists();

            if ($hasActiveBatch) {
                return response()->json([
                    'success' => false,
                    'message' => 'End all active batches before archiving this house.'
                ], 422);
            }

            DB::transaction(function () use ($house) {
                $archivedAt = now();

                $house->update([
                    'archived_at' => $archivedAt,
                ]);

                Pen::where('house_id', $house->id)
                    ->whereNull('archived_at')
                    ->update([
                        'archived_at' => $archivedAt,
                    ]);

                Sensor::where('house_houseid', $house->id)
                    ->update([
                        'status' => 'Inactive',
                    ]);

                DB::table('decision_support_logs')
                    ->where('house_id', $house->id)
                    ->where('status', 'active')
                    ->update([
                        'status' => 'archived',
                        'updated_at' => $archivedAt,
                    ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'House archived successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error archiving house: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * End all pens in a house by resetting their values instead of deleting the house
     */
    public function destroy($id)
    {
        \Log::info('HouseController@destroy called with id: ' . $id);

        try {
            $house = House::whereNull('archived_at')->find($id);

            if (!$house) {
                \Log::warning('House not found with id: ' . $id);
                return response()->json([
                    'success' => false,
                    'message' => 'House not found'
                ], 404);
            }

            $pens = $house->pens()->get();
            \Log::info('Found house: ' . $house->house_number . ' with ' . $pens->count() . ' pens');

            DB::beginTransaction();

            try {
                foreach ($pens as $pen) {
                    $this->resetPenState($pen);
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'All pens in house ended successfully'
                ]);
            } catch (\Exception $e) {
                DB::rollback();
                \Log::error('Error during house end transaction: ' . $e->getMessage());
                throw $e;
            }
        } catch (\Exception $e) {
            \Log::error('Error ending house: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error ending house: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset a pen's current batch and production values.
     */
    private function resetPenState(Pen $pen): void
    {
        \Log::info('Ending pen ' . $pen->id);
        \Log::info('Before: population=' . $pen->population . ', eggs_hatched=' . $pen->eggs_hatched . ', mortality=' . $pen->mortality . ', batch_started_at=' . $pen->batch_started_at . ', current_batch_id=' . $pen->current_batch_id);

        DB::table('flock_batches')
            ->where('pen_id', $pen->id)
            ->where('status', 'Running')
            ->update([
                'status' => 'Ended',
                'ended_at' => now(),
                'updated_at' => now(),
            ]);

        $pen->population = 0;
        $pen->eggs_hatched = 0;
        $pen->mortality = 0;
        $pen->batch_started_at = null;
        $pen->current_batch_id = null;
        $pen->save();

        \Log::info('After: population=' . $pen->population . ', eggs_hatched=' . $pen->eggs_hatched . ', mortality=' . $pen->mortality . ', batch_started_at=' . $pen->batch_started_at . ', current_batch_id=' . $pen->current_batch_id);
    }

    /**
     * End a specific pen by resetting its values instead of deleting it
     */
    public function deletePen($penId)
    {
        try {
            $pen = Pen::whereNull('archived_at')->find($penId);

            if (!$pen) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pen not found'
                ], 404);
            }

            DB::beginTransaction();

            try {
                $this->resetPenState($pen);
                DB::commit();

                $updatedPen = Pen::find($penId);
                \Log::info('Verified after save: batch_started_at=' . ($updatedPen->batch_started_at ?? 'NULL') . ', current_batch_id=' . ($updatedPen->current_batch_id ?? 'NULL'));

                return response()->json([
                    'success' => true,
                    'message' => 'Pen ended successfully with values reset',
                    'pen' => $updatedPen
                ]);
            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error ending pen: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update pen data (capacity, population, and production data)
     */
    public function updatePen(Request $request, $penId)
    {
        try {
            $pen = Pen::whereNull('archived_at')->find($penId);

            if (!$pen) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pen not found'
                ], 404);
            }

            $validated = $request->validate([
                'capacity' => 'nullable|integer|min:0',
                'population' => 'nullable|integer|min:0',
                'feeder_count' => 'nullable|integer|min:0',
                'drinker_count' => 'nullable|integer|min:0',
            ]);

            $pen->update($validated);

            return response()->json([
                'success' => true,
                'data' => $pen,
                'message' => 'Pen data updated successfully'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating pen: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all pens for records display - returns only latest recording per pen
     */
    public function getPenRecords()
    {
        try {
            $houses = DB::table('house as h')
                ->leftJoin('pen as p', 'p.house_id', '=', 'h.id')
                ->leftJoinSub(
                    DB::table('population_record')
                        ->selectRaw('pen_id, MAX(recorded_at) as recorded_at')
                        ->groupBy('pen_id'),
                    'latest_pr',
                    function ($join) {
                        $join->on('p.id', '=', 'latest_pr.pen_id');
                    }
                )
                ->leftJoin('population_record as pr', function ($join) {
                    $join->on('pr.pen_id', '=', 'p.id')
                         ->on('pr.recorded_at', '=', 'latest_pr.recorded_at');
                })
                ->whereNull('h.archived_at')
                ->where(function ($query) {
                    $query->whereNull('p.id')
                        ->orWhereNull('p.archived_at');
                })
                ->select(
                    'h.id as house_id',
                    'h.house_number',
                    'p.id as pen_id',
                    'p.pen_name',
                    'p.capacity',
                    'pr.eggs_hatched',
                    'pr.mortality',
                    'pr.running_population',
                    'pr.recorded_at'
                )
                ->orderBy('h.id', 'asc')
                ->orderBy('p.id', 'asc')
                ->orderBy('pr.recorded_at', 'desc')
                ->get()
                ->groupBy('house_id')
                ->map(function ($records) {
                    $first = $records->first();

                    $pens = $records
                        ->filter(function ($record) {
                            return $record->pen_id !== null && $record->recorded_at !== null;
                        })
                        ->map(function ($record) use ($first) {
                            return [
                                'id' => $record->pen_id,
                                'house_name' => 'House ' . $first->house_number,
                                'pen_name' => $record->pen_name,
                                'capacity' => $record->capacity ?? 0,
                                'population' => $record->running_population ?? 0,
                                'eggs_hatched' => $record->eggs_hatched ?? 0,
                                'mortality' => $record->mortality ?? 0,
                                'recorded_at' => $record->recorded_at,
                            ];
                        })
                        ->values();

                    return [
                        'id' => $first->house_id,
                        'name' => $first->house_number,
                        'pens' => $pens,
                    ];
                })
                ->values();

            return response()->json([
                'success' => true,
                'data' => $houses,
                'message' => 'Pen records retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving pen records: ' . $e->getMessage()
            ], 500);
        }
    }





    /**
     * Update pen production data
     */
    public function updatePenProduction(Request $request, $penId)
    {
        try {
            $validated = $request->validate([
                'eggs_hatched' => 'nullable|integer|min:0',
                'mortality' => 'nullable|integer|min:0',
            ]);

            $pen = Pen::whereNull('archived_at')->find($penId);

            if (!$pen) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pen not found'
                ], 404);
            }

            if (isset($validated['eggs_hatched'])) {
                $pen->eggs_hatched = $validated['eggs_hatched'];
            }

            if (isset($validated['mortality'])) {
                $pen->mortality = $validated['mortality'];
            }


            $pen->recorded_at = now();
            $pen->save();

            return response()->json([
                'success' => true,
                'data' => $pen,
                'message' => 'Pen production data updated successfully'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating pen: ' . $e->getMessage()
            ], 500);
        }
    }

    private function attachLatestSensorReadings($houses): void
    {
        $houseIds = $houses
            ->pluck('id')
            ->filter()
            ->values();

        if ($houseIds->isEmpty()) {
            return;
        }

        $sensors = Sensor::with('latestReading')
            ->whereIn('house_houseid', $houseIds)
            ->whereRaw("LOWER(TRIM(status)) = 'active'")
            ->get();

        $readingsByHouse = [];
        $readingsByPen = [];

        foreach ($sensors as $sensor) {
            $reading = $sensor->latestReading;
            $sensorType = $this->normalizeSensorReadingType($sensor->sensortype);

            if (!$sensorType || $sensor->house_houseid === null) {
                continue;
            }

            $readingData = [
                'sensor_id' => $sensor->sensorid,
                'sensor_name' => $sensor->sensorname,
                'sensor_type' => $sensorType,
                'value' => $this->sensorReadingDisplayValue($sensorType, $reading?->value),
                'formatted_value' => $this->formatSensorReadingValue($sensorType, $reading?->value),
                'recorded_at' => $reading?->recorded_at,
            ];

            $houseId = (int) $sensor->house_houseid;
            $penId = $sensor->pen_penid !== null ? (int) $sensor->pen_penid : null;

            // Handle feed and water sensors specially - group by feeder/drinker number
            if ($sensorType === 'feed' && $penId !== null) {
                if (!isset($readingsByPen[$penId]['feeders'])) {
                    $readingsByPen[$penId]['feeders'] = [];
                }
                $feederNum = $sensor->feeder_number ?? 0;
                if ($feederNum > 0) {
                    $readingData['label'] = 'Feeder ' . $feederNum;
                    $readingsByPen[$penId]['feeders'][$feederNum] = $this->newerSensorReading(
                        $readingsByPen[$penId]['feeders'][$feederNum] ?? null,
                        $readingData
                    );
                }
            } elseif ($sensorType === 'water' && $penId !== null) {
                if (!isset($readingsByPen[$penId]['drinkers'])) {
                    $readingsByPen[$penId]['drinkers'] = [];
                }
                $drinkerNum = $sensor->drinker_number ?? 0;
                if ($drinkerNum > 0) {
                    $readingData['label'] = 'Drinker ' . $drinkerNum;
                    $readingsByPen[$penId]['drinkers'][$drinkerNum] = $this->newerSensorReading(
                        $readingsByPen[$penId]['drinkers'][$drinkerNum] ?? null,
                        $readingData
                    );
                }
            } elseif ($reading) {
                // Temperature and ammonia sensors
                $readingsByHouse[$houseId][$sensorType] = $this->newerSensorReading(
                    $readingsByHouse[$houseId][$sensorType] ?? null,
                    $readingData
                );

                if ($penId !== null) {
                    $readingsByPen[$penId][$sensorType] = $this->newerSensorReading(
                        $readingsByPen[$penId][$sensorType] ?? null,
                        $readingData
                    );
                }
            }
        }

        foreach ($houses as $house) {
            $houseReadings = $this->sensorReadingDefaults($readingsByHouse[(int) $house->id] ?? []);
            $house->setAttribute('sensor_readings', $houseReadings);

            foreach ($house->pens as $pen) {
                $penReadings = $this->sensorReadingDefaults(
                    $readingsByPen[(int) $pen->id] ?? [],
                    (int) ($pen->feeder_count ?? 0),
                    (int) ($pen->drinker_count ?? 0)
                );
                $pen->setAttribute('sensor_readings', $penReadings);
            }
        }
    }

    private function sensorReadingDefaults(array $readings, int $feederCount = 0, int $drinkerCount = 0): array
    {
        return [
            'temperature' => $readings['temperature'] ?? null,
            'ammonia' => $readings['ammonia'] ?? null,
            'feeders' => $this->numberedResourceReadingDefaults(
                $readings['feeders'] ?? [],
                $feederCount,
                'feed'
            ),
            'drinkers' => $this->numberedResourceReadingDefaults(
                $readings['drinkers'] ?? [],
                $drinkerCount,
                'water'
            ),
        ];
    }

    private function numberedResourceReadingDefaults(array $readings, int $configuredCount, string $sensorType): array
    {
        $labelPrefix = $sensorType === 'feed' ? 'Feeder' : 'Drinker';
        $readingNumbers = array_filter(
            array_map('intval', array_keys($readings)),
            fn ($number) => $number > 0
        );
        $count = max($configuredCount, empty($readingNumbers) ? 0 : max($readingNumbers));
        $defaults = [];

        for ($number = 1; $number <= $count; $number++) {
            $defaults[$number] = array_merge([
                'label' => $labelPrefix . ' ' . $number,
            ], $readings[$number] ?? [
                'sensor_id' => null,
                'sensor_name' => null,
                'sensor_type' => $sensorType,
                'value' => 0,
                'formatted_value' => $this->formatSensorReadingValue($sensorType, null),
                'recorded_at' => null,
            ]);
        }

        return $defaults;
    }

    private function normalizeSensorReadingType(?string $type): ?string
    {
        $type = strtolower(trim((string) $type));

        if (str_contains($type, 'temp')) {
            return 'temperature';
        }

        if (str_contains($type, 'ammonia') || str_contains($type, 'nh3')) {
            return 'ammonia';
        }

        if (str_contains($type, 'feed')) {
            return 'feed';
        }

        if (str_contains($type, 'water') || str_contains($type, 'drink')) {
            return 'water';
        }

        return null;
    }

    private function formatSensorReadingValue(string $type, $value): string
    {
        if ($value === null) {
            if ($type === 'temperature') {
                return '0 deg';
            } elseif ($type === 'feed' || $type === 'water') {
                return '0%';
            }
            return '0 ppm';
        }

        if ($type === 'temperature') {
            return number_format((float) $value, 1) . ' deg';
        }

        if ($type === 'feed' || $type === 'water') {
            return number_format($this->resourceLevelPercent($value), 0) . '%';
        }

        return number_format((float) $value, 1) . ' ppm';
    }

    private function sensorReadingDisplayValue(string $type, $value): float
    {
        if ($value === null) {
            return 0;
        }

        if ($type === 'feed' || $type === 'water') {
            return $this->resourceLevelPercent($value);
        }

        return (float) $value;
    }

    private function resourceLevelPercent($value): float
    {
        $containerHeightInches = 8.5;

        if ($containerHeightInches <= 0) {
            return 0;
        }

        $remainingInches = $containerHeightInches - (float) $value;
        $percent = ($remainingInches / $containerHeightInches) * 100;

        return round(max(0, min(100, $percent)), 1);
    }

    private function newerSensorReading(?array $current, array $candidate): array
    {
        if (!$current) {
            return $candidate;
        }

        return strcmp((string) ($candidate['recorded_at'] ?? ''), (string) ($current['recorded_at'] ?? '')) >= 0
            ? $candidate
            : $current;
    }

}
