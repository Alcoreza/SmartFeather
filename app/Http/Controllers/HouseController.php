<?php

namespace App\Http\Controllers;

use App\Models\House;
use App\Models\Pen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


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

            // Create the house
            $house = House::create([
                'house_number' => $validated['house_number'],
                'number_of_pens' => $validated['number_of_pens'],
                'batch_code' => $validated['batch_code'] ?? 'Batch-New',
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
            $houses = DB::table('house as h')
                ->leftJoin('pen as p', 'p.house_id', '=', 'h.id')
                ->leftJoin('population_record as pr', 'pr.pen_id', '=', 'p.id')
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

            $pen = Pen::find($penId);

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

}
