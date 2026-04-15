<?php

namespace App\Http\Controllers;

use App\Models\House;
use App\Models\Pen;
use Illuminate\Http\Request;

class HouseController extends Controller
{
    /**
     * Get all houses with their pens
     */
    public function index()
    {
        try {
            $houses = House::with('pens')
                ->orderBy('id', 'asc')
                ->get();

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
                'house_number' => 'required|string|max:255',
                'number_of_pens' => 'required|integer|min:1|max:100',
                'batch_code' => 'nullable|string|max:255',
                'status' => 'nullable|string|max:50',
                'start_date' => 'nullable|date',
            ]);

            $batchCode = trim($validated['batch_code'] ?? '');
            if ($batchCode === '' || strcasecmp($batchCode, 'Batch-New') === 0) {
                $batchCode = $this->generateNextBatchCode();
            }

            // Create the house
            $house = House::create([
                'house_number' => $validated['house_number'],
                'number_of_pens' => $validated['number_of_pens'],
                'batch_code' => $batchCode,
                'status' => $validated['status'] ?? 'active',
                'start_date' => $validated['start_date'] ?? now()->format('Y-m-d'),
            ]);

            // Create pens automatically with zero production values
            for ($i = 1; $i <= $validated['number_of_pens']; $i++) {
                Pen::create([
                    'house_id' => $house->id,
                    'pen_name' => "Pen $i",
                    'capacity' => 0,
                    'population' => 0,
                    'eggs_hatched' => 0,
                    'mortality' => 0,
                    'recorded_at' => now(),
                ]);
            }

            // Reload with relationships
            $house->load('pens');

            return response()->json([
                'success' => true,
                'data' => $house,
                'message' => 'House created successfully'
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
     * Generate the next batch code for the current year.
     *
     * Example: Batch_2026_1, Batch_2026_2, Batch_2026_3, ...
     */
    private function generateNextBatchCode()
    {
        $year = now()->year;
        $prefix = "Batch_{$year}_";

        $batchCodes = House::where('batch_code', 'like', "{$prefix}%")
            ->pluck('batch_code');

        $maxNumber = 0;

        foreach ($batchCodes as $code) {
            if (preg_match('/^Batch[_-]' . preg_quote($year, '/') . '[_-](\d+)$/', $code, $matches)) {
                $maxNumber = max($maxNumber, (int) $matches[1]);
            }
        }

        return $prefix . ($maxNumber + 1);
    }

    /**
     * Get a specific house
     */
    public function show($id)
    {
        try {
            $house = House::with('pens')->find($id);

            if (!$house) {
                return response()->json([
                    'success' => false,
                    'message' => 'House not found'
                ], 404);
            }

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
            $house = House::find($id);

            if (!$house) {
                return response()->json([
                    'success' => false,
                    'message' => 'House not found'
                ], 404);
            }

            $validated = $request->validate([
                'house_number' => 'nullable|string|max:255',
                'batch_code' => 'nullable|string|max:255',
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
     * Delete a house
     */
    public function destroy($id)
    {
        try {
            $house = House::find($id);

            if (!$house) {
                return response()->json([
                    'success' => false,
                    'message' => 'House not found'
                ], 404);
            }

            // Delete related pens (cascading)
            $house->pens()->delete();
            $house->delete();

            return response()->json([
                'success' => true,
                'message' => 'House deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting house: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update pen data (capacity, population, and production data)
     */
    public function updatePen(Request $request, $penId)
    {
        try {
            $pen = Pen::find($penId);

            if (!$pen) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pen not found'
                ], 404);
            }

            $validated = $request->validate([
                'capacity' => 'nullable|integer|min:0',
                'population' => 'nullable|integer|min:0',
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
     * Get all pens for records display
     */
    public function getPenRecords()
    {
        try {
            $houses = House::with('pens')
                ->orderBy('id', 'asc')
                ->get()
                ->map(function ($house) {
                    return [
                        'id' => $house->id,
                        'name' => $house->house_number,
                        'pens' => $house->pens->map(function ($pen) use ($house) {
                            return [
                                'id' => $pen->id,
                                'house_name' => 'House ' . $house->house_number,
                                'pen_name' => $pen->pen_name,
                                'capacity' => $pen->capacity,
                                'population' => $pen->population,
                                'eggs_hatched' => $pen->eggs_hatched,
                                'mortality' => $pen->mortality,
                                'recorded_at' => $pen->recorded_at,
                            ];
                        }),
                    ];
                });

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

            $pen = Pen::find($penId);

            if (!$pen) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pen not found'
                ], 404);
            }

            // Update production data
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
}
