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
        $employeeId = (int) $request->attributes->get('mobile_employee_id');

        $latestEntry = $this->latestEntryLog($employeeId);

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
            ->where('employee_id', $employeeId)
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
            ->get(['id', 'pen_name', 'feeder_count', 'drinker_count'])
            ->map(function (Pen $pen) {
                return [
                    'id' => (int) $pen->id,
                    'pen_name' => $pen->pen_name,
                    'feeder_count' => (int) ($pen->feeder_count ?? 0),
                    'drinker_count' => (int) ($pen->drinker_count ?? 0),
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

    public function getFeederOptions(int $houseId, int $penId)
    {
        $pen = Pen::where('id', $penId)
            ->where('house_id', $houseId)
            ->first(['id', 'house_id', 'feeder_count']);

        if (!$pen) {
            return response()->json([
                'message' => 'Selected pen does not belong to the selected house.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'feeder_options' => $this->buildNumberOptions((int) ($pen->feeder_count ?? 0)),
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
        $employeeId = (int) $request->attributes->get('mobile_employee_id');

        $validated = $request->validate([
            'task_id' => 'nullable|integer|exists:tasks,taskid',
            'inventory_id' => 'required|integer|exists:inventories,id',
            'house_id' => 'required|integer|exists:house,id',
            'pen_id' => 'required|integer|exists:pen,id',
            'feeder_number' => 'required|integer|min:1',
            'kilograms' => 'required|integer|min:1',
            'recorded_at' => 'required|date',
        ]);

        if (!empty($validated['task_id'])) {
            $task = DB::table('tasks')
                ->where('taskid', $validated['task_id'])
                ->where('user_employeeid', $employeeId)
                ->first();

            if (!$task) {
                return response()->json([
                    'message' => 'Selected task was not found for this employee.',
                ], 404);
            }

            if (strtolower((string) $task->status) !== 'pending') {
                return response()->json([
                    'message' => 'This task is no longer pending.',
                ], 422);
            }

            if (!$this->isFeedReplenishmentTask((string) $task->tasktype)) {
                return response()->json([
                    'message' => 'This task is not a feed replenishment task.',
                ], 422);
            }

            if ((int) $task->house_houseid !== (int) $validated['house_id']) {
                return response()->json([
                    'message' => 'Selected house does not match the assigned task house.',
                ], 403);
            }

            if ((int) $task->pennumber !== (int) $validated['pen_id']) {
                return response()->json([
                    'message' => 'Selected pen does not match the assigned task pen.',
                ], 403);
            }
        }

        $latestEntry = $this->latestEntryLog($employeeId);

        if (!$latestEntry || strtoupper((string) $latestEntry->status) !== 'IN') {
            return response()->json([
                'message' => 'Please scan IN and complete personnel biosecurity before recording feeds refill.'
            ], 403);
        }

        $biosecurityQuery = DB::table('personnel_biosecurity_logs')
            ->where('personnel_entry_log_id', $latestEntry->id)
            ->where('employee_id', $employeeId)
            ->where('house_id', $validated['house_id'])
            ->orderByDesc('id');

        if (!empty($validated['task_id'])) {
            $biosecurityQuery
                ->where('task_id', $validated['task_id'])
                ->where('pen_id', $validated['pen_id']);
        }

        $latestBiosecurity = $biosecurityQuery->first();

        if (!$latestBiosecurity) {
            return response()->json([
                'message' => !empty($validated['task_id'])
                    ? 'Please complete personnel biosecurity for this assigned task before recording feed replenishment.'
                    : 'Please submit the personnel biosecurity form first before recording feeds refill.'
            ], 403);
        }

        $pen = Pen::where('id', $validated['pen_id'])
            ->where('house_id', $validated['house_id'])
            ->first(['id', 'house_id', 'feeder_count']);

        if (!$pen) {
            return response()->json([
                'message' => 'Selected pen does not belong to the selected house.',
            ], 422);
        }

        $feederCount = (int) ($pen->feeder_count ?? 0);

        if ($feederCount <= 0) {
            return response()->json([
                'message' => 'No feeders are configured for this pen.',
            ], 422);
        }

        if ((int) $validated['feeder_number'] > $feederCount) {
            return response()->json([
                'message' => 'Selected feeder does not exist for this pen.',
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
                $initialPurchaseDate = $this->initialPurchaseDate(
                    $validated['inventory_id'],
                    $inventory->purchase_date ?? null
                );
                $reducedDate = \Illuminate\Support\Carbon::parse($validated['recorded_at'])->toDateString();

                if ($remainingStock < $kilograms) {
                    abort(response()->json([
                        'message' => 'Not enough remaining feed stock.',
                    ], 422));
                }

                $newRemaining = $remainingStock - $kilograms;

                DB::table('feed_refill_records')->insert([
                    'task_id' => $validated['task_id'] ?? null,
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
                    'deducted' => $kilograms,
                    'added' => 0,
                    'initial_purchase_date' => $initialPurchaseDate,
                    'reduced_date' => $reducedDate,
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

    private function buildNumberOptions(int $count): array
    {
        if ($count <= 0) {
            return [];
        }

        return collect(range(1, $count))
            ->map(fn($number) => (string) $number)
            ->values()
            ->all();
    }

    private function latestEntryLog(int $employeeId)
    {
        return DB::table('personnel_entry_logs')
            ->where('employee_id', $employeeId)
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->orderByDesc('id')
            ->first();
    }

    private function initialPurchaseDate($inventoryId, $fallback = null)
    {
        return DB::table('inventory_records')
            ->where('inventory_id', $inventoryId)
            ->whereNotNull('initial_purchase_date')
            ->orderBy('id')
            ->value('initial_purchase_date') ?? $fallback;
    }

    private function isFeedReplenishmentTask(string $taskType): bool
    {
        $normalized = strtolower(trim($taskType));

        return $normalized === 'feed replenishment' ||
            $normalized === 'feeds replenishment' ||
            $normalized === 'feeds refill' ||
            $normalized === 'feed refill' ||
            str_contains($normalized, 'feed') && str_contains($normalized, 'replenishment');
    }
}
