<?php

namespace App\Http\Controllers;

use App\Models\House;
use App\Models\Pen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MobileFeedsRefillController extends Controller
{
    public function getContext(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|integer|exists:user,EmployeeId',
        ]);

        $latestEntry = DB::table('personnel_entry_logs')
            ->where('employee_id', $validated['employee_id'])
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->orderByDesc('id')
            ->first();

        if (!$latestEntry || strtoupper((string) $latestEntry->status) !== 'IN') {
            return response()->json([
                'message' => 'Please scan IN and complete personnel biosecurity before accessing feeds refill.',
                'access_allowed' => false,
                'house_id' => null,
                'house_number' => null,
                'pen_options' => [],
            ], 403);
        }

        $latestBiosecurity = DB::table('personnel_biosecurity_logs')
            ->where('personnel_entry_log_id', $latestEntry->id)
            ->orderByDesc('id')
            ->first();

        if (!$latestBiosecurity) {
            return response()->json([
                'message' => 'Please submit the personnel biosecurity form first before accessing feeds refill.',
                'access_allowed' => false,
                'house_id' => null,
                'house_number' => null,
                'pen_options' => [],
            ], 403);
        }

        $house = House::find($latestBiosecurity->house_id);

        if (!$house) {
            return response()->json([
                'message' => 'Assigned house from personnel biosecurity was not found.',
                'access_allowed' => false,
                'house_id' => null,
                'house_number' => null,
                'pen_options' => [],
            ], 422);
        }

        $pens = Pen::where('house_id', $house->id)
            ->orderBy('id', 'asc')
            ->get(['id', 'pen_name'])
            ->map(function (Pen $pen) {
                return [
                    'id' => $pen->id,
                    'pen_name' => $pen->pen_name,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'access_allowed' => true,
            'house_id' => $house->id,
            'house_number' => $house->house_number,
            'pen_options' => $pens,
        ]);
    }

    public function getFeedInventoryOptions()
    {
        $items = DB::table('inventories')
            ->whereIn(DB::raw('LOWER(TRIM(type))'), ['feed', 'feeds'])
            ->whereNull('archived_at')
            ->orderBy('item_name')
            ->get([
                'id',
                'item_name',
                'remaining_stock',
                'unit',
            ])
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'item_name' => $item->item_name,
                    'remaining_stock' => (int) $item->remaining_stock,
                    'unit' => $item->unit ?: 'kg',
                ];
            })
            ->values();

        return response()->json($items);
    }

    public function submit(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|integer|exists:user,EmployeeId',
            'inventory_id' => 'required|integer|exists:inventories,id',
            'house_id' => 'required|integer|exists:house,id',
            'pen_id' => 'required|integer|exists:pen,id',
            'feeder_number' => 'required|integer|min:1',
            'kilograms' => 'required|integer|min:1',
            'recorded_at' => 'required|date',
        ]);

        $latestEntry = DB::table('personnel_entry_logs')
            ->where('employee_id', $validated['employee_id'])
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->orderByDesc('id')
            ->first();

        if (!$latestEntry || strtoupper((string) $latestEntry->status) !== 'IN') {
            return response()->json([
                'message' => 'Please scan IN and complete personnel biosecurity before recording feeds refill.'
            ], 403);
        }

        $latestBiosecurity = DB::table('personnel_biosecurity_logs')
            ->where('personnel_entry_log_id', $latestEntry->id)
            ->orderByDesc('id')
            ->first();

        if (!$latestBiosecurity) {
            return response()->json([
                'message' => 'Please submit the personnel biosecurity form first before recording feeds refill.'
            ], 403);
        }

        if ((int) $latestBiosecurity->house_id !== (int) $validated['house_id']) {
            return response()->json([
                'message' => 'You can only record feeds refill for the house selected in your personnel biosecurity form.'
            ], 403);
        }

        $pen = Pen::where('id', $validated['pen_id'])
            ->where('house_id', $validated['house_id'])
            ->first();

        if (!$pen) {
            return response()->json([
                'message' => 'Selected pen does not belong to the selected house.',
            ], 422);
        }

        try {
            DB::transaction(function () use ($validated) {
                $inventory = DB::table('inventories')
                    ->where('id', $validated['inventory_id'])
                    ->whereIn(DB::raw('LOWER(TRIM(type))'), ['feed', 'feeds'])
                    ->whereNull('archived_at')
                    ->lockForUpdate()
                    ->first();

                if (!$inventory) {
                    abort(response()->json([
                        'message' => 'Selected feed is no longer active. Please choose another feed type.',
                    ], 422));
                }

                $remainingStock = (int) $inventory->remaining_stock;
                $initialStock = (int) $inventory->initial_stock;
                $kilograms = (int) $validated['kilograms'];

                if ($remainingStock < $kilograms) {
                    abort(response()->json([
                        'message' => 'Not enough remaining feed stock.',
                    ], 422));
                }

                $newRemaining = $remainingStock - $kilograms;

                DB::table('feed_refill_records')->insert([
                    'inventory_id' => $validated['inventory_id'],
                    'house_id' => $validated['house_id'],
                    'pen_id' => $validated['pen_id'],
                    'feeder_number' => $validated['feeder_number'],
                    'kilograms_used' => $kilograms,
                    'recorded_at' => $validated['recorded_at'],
                ]);

                DB::table('inventories')
                    ->where('id', $validated['inventory_id'])
                    ->update([
                        'remaining_stock' => $newRemaining,
                        'updated_at' => now(),
                    ]);

                DB::table('inventory_records')->insert([
                    'inventory_id' => $validated['inventory_id'],
                    'initial_stock' => $initialStock,
                    'remaining_stock' => $newRemaining,
                    'monitoring_date' => $validated['recorded_at'],
                ]);
            });
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            throw $exception;
        }

        return response()->json([
            'success' => true,
            'message' => 'Feeds refill submitted successfully.',
        ]);
    }
}