<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryController extends Controller
{
    public function managerIndex()
    {
        $viewData = Cache::remember('manager_inventory_view_data', now()->addSeconds(30), function () {
            $items = DB::table('inventories')
            ->whereNull('archived_at')
            ->orderBy('id', 'asc')
            ->get();

            $feedItems = $items->where('type', 'feed')
                ->map(fn ($item) => $this->formatItem($item));

            $vitaminItems = $items->where('type', 'vitamin')
                ->map(fn ($item) => $this->formatItem($item));

            $feedTypeOptions = DB::table('inventories')
                ->select('item_name as name')
                ->where('type', 'feed')
                ->whereNull('archived_at')
                ->orderBy('item_name')
                ->get();

            $vitaminTypeOptions = DB::table('inventories')
                ->select('item_name as name')
                ->where('type', 'vitamin')
                ->whereNull('archived_at')
                ->orderBy('item_name')
                ->get();

            $analysisHouseOptions = DB::table('house as h')
                ->join('pen as p', 'p.house_id', '=', 'h.id')
                ->join('flock_batches as fb', 'fb.pen_id', '=', 'p.id')
                ->select('h.id', 'h.house_number')
                ->whereNull('h.archived_at')
                ->whereNull('p.archived_at')
                ->where('fb.status', 'Running')
                ->distinct()
                ->orderBy('h.house_number')
                ->get();

            return compact('feedItems', 'vitaminItems', 'feedTypeOptions', 'vitaminTypeOptions', 'analysisHouseOptions');
        });

        return view('manager.inventory', $viewData);
    }

    public function snapshot()
    {
        $items = Cache::remember('manager_inventory_snapshot', now()->addSeconds(30), function () {
            return DB::table('inventories')
            ->whereNull('archived_at')
            ->orderBy('id', 'asc')
            ->get()
            ->map(fn ($item) => $this->formatItem($item))
            ->values();
        });

        return response()->json([
            'items' => $items,
        ]);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'item_name' => 'required|string|max:255',
                'type' => 'required|in:feed,vitamin',
                'stock_to_add' => 'required|numeric|min:0',
                'purchase_date' => 'required|date',
            ]);

            $itemName = trim($validated['item_name']);
            $itemType = $validated['type'];

            $unit = $itemType === 'vitamin' ? 'bottle' : 'kg';
            $stockToAdd = (float) $validated['stock_to_add'];

            $existingInventory = DB::table('inventories')
                ->whereRaw('LOWER(item_name) = ?', [strtolower($itemName)])
                ->where('type', $itemType)
                ->whereNull('archived_at')
                ->first();

            if (!$existingInventory) {
                return response()->json([
                    'error' => 'Inventory item not found or already archived.',
                ], 404);
            }

            $newRemainingStock = (float) $existingInventory->remaining_stock + $stockToAdd;

            DB::table('inventories')
                ->where('id', $existingInventory->id)
                ->update([
                    'unit' => $unit,
                    'remaining_stock' => $newRemainingStock,
                    'purchase_date' => $validated['purchase_date'],
                    'updated_at' => now(),
                ]);

            DB::table('inventory_records')->insert([
                'inventory_id' => $existingInventory->id,
                'initial_stock' => $existingInventory->initial_stock,
                'remaining_stock' => $newRemainingStock,
                'deducted' => 0,
                'added' => $stockToAdd,
                'initial_purchase_date' => $this->initialPurchaseDate($existingInventory->id, $existingInventory->purchase_date),
                'recent_purchase_date' => $validated['purchase_date'],
                'monitoring_date' => now(),
            ]);

            $this->clearInventoryCaches();

            return response()->json([
                'success' => true,
                'message' => 'Stock added successfully.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'stock_to_reduce' => 'required|numeric|min:0',
                'reduced_date' => 'required|date',
            ]);

            $inventory = DB::table('inventories')
                ->where('id', $id)
                ->whereNull('archived_at')
                ->first();

            if (!$inventory) {
                return response()->json([
                    'error' => 'Inventory not found.',
                ], 404);
            }

            $stockToReduce = (float) $validated['stock_to_reduce'];
            $remainingStock = (float) $inventory->remaining_stock;

            if ($stockToReduce > $remainingStock) {
                return response()->json([
                    'error' => 'Stock to reduce cannot be higher than the remaining stock.',
                ], 422);
            }

            $newRemainingStock = $remainingStock - $stockToReduce;

            DB::table('inventories')
                ->where('id', $id)
                ->update([
                    'remaining_stock' => $newRemainingStock,
                    'updated_at' => now(),
                ]);

            DB::table('inventory_records')->insert([
                'inventory_id' => $id,
                'initial_stock' => $inventory->initial_stock,
                'remaining_stock' => $newRemainingStock,
                'deducted' => $stockToReduce,
                'added' => 0,
                'initial_purchase_date' => $this->initialPurchaseDate($id, $inventory->purchase_date),
                'reduced_date' => $validated['reduced_date'],
                'monitoring_date' => now(),
            ]);

            $this->clearInventoryCaches();

            return response()->json([
                'success' => true,
                'message' => 'Stock reduced successfully.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $inventory = DB::table('inventories')
                ->where('id', $id)
                ->whereNull('archived_at')
                ->first();

            if (!$inventory) {
                DB::rollBack();

                return response()->json([
                    'error' => 'Inventory not found or already archived.',
                ], 404);
            }

            DB::table('inventories')
                ->where('id', $id)
                ->update([
                    'archived_at' => now(),
                    'updated_at' => now(),
                ]);

            DB::commit();

            $this->clearInventoryCaches();

            return response()->json([
                'success' => true,
                'message' => 'Inventory archived successfully.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function records(Request $request)
    {
        $type = $request->type;
        $itemName = $request->item_name;

        $query = DB::table('inventory_records as r')
            ->join('inventories as i', 'r.inventory_id', '=', 'i.id')
            ->whereNull('i.archived_at')
            ->select(
                'r.id',
                'i.item_name',
                'i.type',
                'r.initial_stock',
                'r.remaining_stock',
                'r.deducted',
                'r.added',
                'r.initial_purchase_date',
                'r.recent_purchase_date',
                'r.reduced_date',
                'r.monitoring_date'
            )
            ->orderBy('r.monitoring_date', 'desc')
            ->orderBy('r.id', 'desc');

        if ($type) {
            $query->whereIn(DB::raw('LOWER(TRIM(i.type))'), $this->inventoryTypeAliases($type));
        }

        if ($itemName) {
            $query->where('i.item_name', $itemName);
        }

        return response()->json($query->get());
    }

    public function generateAnalysis(Request $request)
    {
        $validated = $request->validate([
            'house_id' => 'required|integer|exists:house,id',
        ]);

        $house = DB::table('house')
            ->where('id', $validated['house_id'])
            ->whereNull('archived_at')
            ->first();

        if (!$house) {
            return response()->json([
                'message' => 'Selected house was not found.',
            ], 404);
        }

        $pens = DB::table('pen as p')
            ->join('flock_batches as fb', function ($join) {
                $join->on('fb.id', '=', 'p.current_batch_id')
                    ->on('fb.pen_id', '=', 'p.id')
                    ->on('fb.house_id', '=', 'p.house_id');
            })
            ->where('p.house_id', $house->id)
            ->whereNull('p.archived_at')
            ->where('fb.status', 'Running')
            ->select(
                'p.id',
                'p.pen_name',
                'p.population',
                'p.current_batch_id',
                'fb.batch_code',
                'fb.started_at'
            )
            ->orderBy('p.pen_name')
            ->orderBy('p.id')
            ->get();

        if ($pens->isEmpty()) {
            return response()->json([
                'message' => 'This house has no pens with a running batch.',
            ], 422);
        }

        $feedInventory = $this->feedInventorySnapshot();
        $rows = $pens->map(function ($pen) use ($feedInventory) {
            $weightLog = $this->latestWeightLogForPen($pen);
            $ageDays = $this->ageDaysFromBatchStart($pen->started_at);
            $feedProfile = $this->feedProfileForAge($ageDays);
            $inventory = $this->inventoryForFeedType($feedInventory, $feedProfile['feed_type']);
            $actualWeight = $this->numericValue($weightLog->average_weight ?? null);
            $targetWeight = $this->numericValue($weightLog->target ?? null);
            $achievement = $actualWeight !== null && $targetWeight !== null && $targetWeight > 0
                ? round(($actualWeight / $targetWeight) * 100, 1)
                : null;
            $weightStatus = $this->weightStatusForAchievement($achievement);
            $stockLevel = !$inventory
                ? 'Critical'
                : (((float) $inventory->remaining_stock <= (float) ($inventory->critical ?? 0)) ? 'Critical' : 'Normal');
            $priorityScore = $this->priorityScore($weightStatus, $stockLevel);

            return [
                'pen_id' => $pen->id,
                'pen_name' => $pen->pen_name ?? "Pen {$pen->id}",
                'batch_code' => $pen->batch_code,
                'age_days' => $ageDays,
                'growth_stage' => $feedProfile['growth_stage'],
                'feed_type_needed' => $feedProfile['feed_type'],
                'feed_item' => $inventory->item_name ?? null,
                'feed_stock_quantity' => $inventory ? (float) $inventory->remaining_stock : null,
                'feed_critical_level' => $inventory ? (float) ($inventory->critical ?? 0) : null,
                'feed_stock_level' => $stockLevel,
                'actual_average_weight' => $actualWeight,
                'target_weight' => $targetWeight,
                'weight_achievement' => $achievement,
                'weight_status' => $weightStatus,
                'weight_need_score' => $this->weightNeedScore($weightStatus),
                'feed_stock_risk_score' => $this->feedStockRiskScore($stockLevel),
                'priority_score' => $priorityScore,
                'weight_recorded_at' => $weightLog?->date,
            ];
        })->sortByDesc('priority_score')->values();

        $snapshot = [
            'house_id' => $house->id,
            'house_name' => $house->house_number,
            'generated_at' => now()->toIso8601String(),
            'feed_rules' => [
                ['age' => '0-10 days', 'growth_stage' => 'Newly hatched chicks', 'feed_type' => 'Starter'],
                ['age' => '11-24 days', 'growth_stage' => 'Rapid growth', 'feed_type' => 'Grower'],
                ['age' => '25 days until market', 'growth_stage' => 'Muscle development and weight gain', 'feed_type' => 'Finisher'],
            ],
            'pens' => $rows->all(),
        ];

        $fallbackInsight = $this->fallbackFeedAnalysisInsight($snapshot);
        $aiInsight = $this->aiFeedAnalysisInsight($snapshot, $fallbackInsight);

        return response()->json([
            'success' => true,
            'house_name' => $house->house_number,
            'generated_at' => $snapshot['generated_at'],
            'insight' => $aiInsight['text'],
            'used_ai' => $aiInsight['used_ai'],
            'pens' => $rows,
        ]);
    }

    public function clearInventoryCaches(): void
    {
        Cache::forget('manager_inventory_view_data');
        Cache::forget('manager_inventory_snapshot');
    }

    private function formatItem($item)
    {
        $initialStock = (float) $item->initial_stock;
        $remainingStock = (float) $item->remaining_stock;
        $critical = (float) ($item->critical ?? 0);

        $percentage = $initialStock > 0
            ? round(($remainingStock / $initialStock) * 100)
            : 0;

        if ($remainingStock <= $critical) {
            $status = ['Critical', 'critical'];
        } else {
            $status = ['Normal', 'high'];
        }

        return [
            'id' => $item->id,
            'item_name' => $item->item_name,
            'initial_stock' => $item->initial_stock,
            'remaining_stock' => $item->remaining_stock,
            'critical' => $item->critical ?? 0,
            'purchase_date' => $item->purchase_date,
            'unit' => $item->type === 'vitamin' ? 'bottle' : $item->unit,
            'percentage' => $percentage,
            'status' => $status[0],
            'status_class' => $status[1],
        ];
    }

    private function initialPurchaseDate($inventoryId, $fallback = null)
    {
        return DB::table('inventory_records')
            ->where('inventory_id', $inventoryId)
            ->whereNotNull('initial_purchase_date')
            ->orderBy('id')
            ->value('initial_purchase_date') ?? $fallback;
    }

    public function items(Request $request)
    {
        $type = $request->type;

        $items = DB::table('inventories')
            ->whereNull('archived_at')
            ->when($type, fn ($q) => $q->whereIn(DB::raw('LOWER(TRIM(type))'), $this->inventoryTypeAliases($type)))
            ->pluck('item_name');

        return response()->json($items);
    }

    private function inventoryTypeAliases($type): array
    {
        $normalized = strtolower(trim((string) $type));

        return match ($normalized) {
            'feed', 'feeds' => ['feed', 'feeds'],
            'vitamin', 'vitamins' => ['vitamin', 'vitamins'],
            default => [$normalized],
        };
    }

    private function feedInventorySnapshot()
    {
        return DB::table('inventories')
            ->whereNull('archived_at')
            ->whereIn(DB::raw('LOWER(TRIM(type))'), $this->inventoryTypeAliases('feed'))
            ->orderBy('item_name')
            ->get();
    }

    private function latestWeightLogForPen($pen)
    {
        return DB::table('weight_sampling_logs')
            ->where(function ($query) use ($pen) {
                $query->where('pen_id', $pen->id)
                    ->orWhere(function ($fallbackQuery) use ($pen) {
                        $fallbackQuery->where('pen', $pen->pen_name)
                            ->where('batch', $pen->batch_code);
                    });
            })
            ->where(function ($query) use ($pen) {
                $query->whereNull('batch_id')
                    ->orWhere('batch_id', $pen->current_batch_id);
            })
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->orderByDesc('id')
            ->first();
    }

    private function ageDaysFromBatchStart($startedAt): ?int
    {
        if (!$startedAt) {
            return null;
        }

        $ageDays = \Illuminate\Support\Carbon::parse($startedAt)
            ->startOfDay()
            ->diffInDays(now()->startOfDay(), false);

        return max(0, (int) $ageDays);
    }

    private function feedProfileForAge(?int $ageDays): array
    {
        return match (true) {
            $ageDays !== null && $ageDays <= 10 => [
                'growth_stage' => 'Newly hatched chicks',
                'feed_type' => 'Starter',
            ],
            $ageDays !== null && $ageDays <= 24 => [
                'growth_stage' => 'Rapid growth',
                'feed_type' => 'Grower',
            ],
            $ageDays !== null => [
                'growth_stage' => 'Muscle development and weight gain',
                'feed_type' => 'Finisher',
            ],
            default => [
                'growth_stage' => 'Unknown',
                'feed_type' => 'Unknown',
            ],
        };
    }

    private function inventoryForFeedType($feedInventory, string $feedType)
    {
        $keywords = match (strtolower($feedType)) {
            'starter' => ['starter'],
            'grower' => ['grower'],
            'finisher' => ['finisher', 'broiler'],
            default => [],
        };

        if (empty($keywords)) {
            return null;
        }

        return $feedInventory->first(function ($item) use ($keywords) {
            $name = strtolower((string) $item->item_name);

            foreach ($keywords as $keyword) {
                if (str_contains($name, $keyword)) {
                    return true;
                }
            }

            return false;
        });
    }

    private function numericValue($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = preg_replace('/[^0-9.\-]/', '', (string) $value);

        return is_numeric($normalized) ? round((float) $normalized, 2) : null;
    }

    private function weightStatusForAchievement(?float $achievement): string
    {
        return match (true) {
            $achievement === null => 'No weight record',
            $achievement >= 90 => 'Normal',
            $achievement >= 75 => 'Slightly underweight',
            default => 'Underweight / priority',
        };
    }

    private function weightNeedScore(string $status): int
    {
        return match ($status) {
            'Underweight / priority' => 100,
            'Slightly underweight' => 70,
            'Normal' => 40,
            default => 0,
        };
    }

    private function feedStockRiskScore(string $stockLevel): int
    {
        return $stockLevel === 'Critical' ? 100 : 40;
    }

    private function priorityScore(string $weightStatus, string $stockLevel): float
    {
        if ($weightStatus === 'No weight record') {
            return 0;
        }

        return round(($this->weightNeedScore($weightStatus) * 0.60) + ($this->feedStockRiskScore($stockLevel) * 0.40), 1);
    }

    private function fallbackFeedAnalysisInsight(array $snapshot): string
    {
        $priorityRows = collect($snapshot['pens'])->sortByDesc('priority_score')->values();
        $top = $priorityRows->first();

        if (!$top) {
            return 'No running-batch pens were available for feed allocation analysis.';
        }

        $feedType = $top['feed_type_needed'] && $top['feed_type_needed'] !== 'Unknown'
            ? $top['feed_type_needed'] . ' Feed'
            : 'The needed feed';
        $stockPhrase = $top['feed_stock_level'] === 'Critical'
            ? 'is currently at critical level'
            : 'is currently at normal level';
        $second = $priorityRows->slice(1)->first();
        $normalLowerPriority = $priorityRows
            ->slice(1)
            ->first(fn ($row) => $row['weight_status'] === 'Normal');

        $sentences = [
            "{$feedType} {$stockPhrase}.",
            "{$top['pen_name']} is prioritized because {$this->priorityReason($top)}.",
        ];

        if ($second && $second['pen_id'] !== $top['pen_id']) {
            $nextSentence = "{$second['pen_name']} is next due to {$this->shortWeightReason($second)}";

            if ($normalLowerPriority && $normalLowerPriority['pen_id'] !== $second['pen_id']) {
                $nextSentence .= ", while {$normalLowerPriority['pen_name']} has lower priority because its weight is within the normal range";
            }

            $sentences[] = "{$nextSentence}.";
        } elseif ($normalLowerPriority) {
            $sentences[] = "{$normalLowerPriority['pen_name']} has lower priority because its weight is within the normal range.";
        }

        return implode(' ', $sentences);
    }

    private function priorityReason(array $row): string
    {
        $achievement = $row['weight_achievement'] !== null
            ? ' (' . $row['weight_achievement'] . '% of target weight)'
            : '';

        return match ($row['weight_status']) {
            'Underweight / priority' => "its average weight is significantly below the expected weight for its stage{$achievement}",
            'Slightly underweight' => "its average weight is slightly below the expected weight for its stage{$achievement}",
            'Normal' => "it has the highest computed priority score while remaining within the expected weight range{$achievement}",
            default => 'there is no latest weight monitoring record for this pen',
        };
    }

    private function shortWeightReason(array $row): string
    {
        return match ($row['weight_status']) {
            'Underweight / priority' => 'underweight status',
            'Slightly underweight' => 'slight underweight status',
            'Normal' => 'normal weight status',
            default => 'missing weight monitoring data',
        };
    }

    private function aiFeedAnalysisInsight(array $snapshot, string $fallback): array
    {
        try {
            if (!config('services.openai.api_key')) {
                throw new \RuntimeException('OpenAI API key not configured.');
            }

            $response = \OpenAI::client(config('services.openai.api_key'))->chat()->create([
                'model' => config('services.openai.model') ?? 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You write concise broiler feed allocation insights for SmartFeather. Use only supplied JSON facts. Follow the feed-stage rules, weight achievement formula, and priority scoring already calculated by the system. Do not invent missing data. Do not mention OpenAI or prompts. Return only one short paragraph with no headings, bullets, labels, tables, or markdown.',
                    ],
                    [
                        'role' => 'user',
                        'content' => "Create one manager-facing AI insight for this inventory modal. Match this paragraph style exactly, using the real feed type, stock level, and pen names from the JSON: Grower Feed is currently at critical level. Pen B is prioritized because its average weight is significantly below the expected weight for its stage. Pen A is next due to slight underweight status, while Pen C has lower priority because its weight is within the normal range.\n\nUse only this JSON:\n" . json_encode($snapshot, JSON_PRETTY_PRINT),
                    ],
                ],
                'temperature' => 0.2,
                'max_tokens' => 450,
            ]);

            $text = $this->normalizeFeedAnalysisInsight(
                trim($response->choices[0]->message->content ?? ''),
                $fallback
            );

            return [
                'text' => $text,
                'used_ai' => true,
            ];
        } catch (\Throwable $e) {
            Log::error('Inventory feed analysis AI error: ' . $e->getMessage());

            return [
                'text' => $fallback,
                'used_ai' => false,
            ];
        }
    }

    private function normalizeFeedAnalysisInsight(string $text, string $fallback): string
    {
        if ($text === '') {
            return $fallback;
        }

        $normalized = trim(preg_replace('/\s+/', ' ', strip_tags(str_replace(['**', '- '], '', $text))));

        if (
            $normalized === '' ||
            str_contains($normalized, 'Feed Type Needed:') ||
            str_contains($normalized, 'Stock Condition:') ||
            str_contains($normalized, 'Pen Priority:')
        ) {
            return $fallback;
        }

        return $normalized;
    }
}
