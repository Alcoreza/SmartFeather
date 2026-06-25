<?php

namespace App\Services;

use App\Models\DecisionSupportLog;
use App\Models\FlockBatch;
use App\Models\House;
use App\Models\SensorReading;
use App\Models\Sensor;
use App\Models\Inventory;
use App\Models\Pen;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DecisionSupportService
{
    private const DEFAULT_FEED_CRITICAL_LEVEL = 25;
    private const DEFAULT_WATER_CRITICAL_LEVEL = 25;
    private const LEGACY_FAILURE_RECOMMENDATION = 'Unable to generate recommendations at this time. Please check system logs.';
    private const BROILER_TEMPERATURE_THRESHOLDS = [
        1 => [
            'week_label' => 'Week 1',
            'target_min' => 32,
            'target_max' => 35,
            'cold_below' => 32,
            'heat_above' => 36,
        ],
        2 => [
            'week_label' => 'Week 2',
            'target_min' => 29,
            'target_max' => 32,
            'cold_below' => 27,
            'heat_above' => 35,
        ],
        3 => [
            'week_label' => 'Week 3',
            'target_min' => 26,
            'target_max' => 29,
            'cold_below' => 24,
            'heat_above' => 32,
        ],
        4 => [
            'week_label' => 'Week 4',
            'target_min' => 23,
            'target_max' => 26,
            'cold_below' => 20,
            'heat_above' => 28,
        ],
        5 => [
            'week_label' => 'Week 5+ / Market Age',
            'target_min' => 18,
            'target_max' => 24,
            'cold_below' => 15,
            'heat_above' => 24,
            'severe_heat_at' => 30,
        ],
    ];

    protected $client;

    public function __construct()
    {
        // Lazily initialize client when needed
    }

    /**
     * Get or create OpenAI client
     */
    protected function getClient()
    {
        if (!$this->client) {
            $apiKey = config('services.openai.api_key');
            if (!$apiKey) {
                throw new \Exception('OpenAI API key not configured');
            }
            $this->client = \OpenAI::client($apiKey);
        }
        return $this->client;
    }

    /**
     * Generate decision support recommendations for a house
     */
    public function generateRecommendations($houseId = null, bool $force = false)
    {
        try {
            // Get houses to analyze
            $houses = $houseId 
                ? House::where('id', $houseId)->whereNull('archived_at')->get() 
                : House::whereNull('archived_at')->get();

            if ($houses->isEmpty()) {
                Log::warning('No houses found for decision support generation');
                return true;
            }

            foreach ($houses as $house) {
                $runningPenIds = $house->pens()
                    ->whereNull('archived_at')
                    ->whereNotNull('current_batch_id')
                    ->whereHas('currentBatch', function ($query) {
                        $query->where('status', 'Running');
                    })
                    ->pluck('id');

                DecisionSupportLog::forHouse($house->id)
                    ->active()
                    ->whereNotNull('pen_id')
                    ->whereNotIn('pen_id', $runningPenIds)
                    ->update(['status' => 'archived']);

                $pens = $house->pens()
                    ->whereNull('archived_at')
                    ->whereNotNull('current_batch_id')
                    ->whereHas('currentBatch', function ($query) {
                        $query->where('status', 'Running');
                    })
                    ->orderBy('pen_name')
                    ->orderBy('id')
                    ->get();

                if ($pens->isEmpty()) {
                    DecisionSupportLog::forHouse($house->id)
                        ->active()
                        ->update(['status' => 'archived']);

                    Log::info("No active pens found for decision support in house {$house->id}");
                    continue;
                }

                foreach ($pens as $pen) {
                    // Check if we already have a recent active recommendation for this pen.
                    $existingRec = DecisionSupportLog::forHouse($house->id)
                        ->forPen($pen->id)
                        ->active()
                        ->orderBy('generated_at', 'desc')
                        ->first();

                    if ($existingRec && $this->isLegacyFailureRecommendation($existingRec->recommendation_text)) {
                        $existingRec->update(['status' => 'archived']);
                        $existingRec = null;
                    }

                    if ($existingRec && $this->isOutdatedRecommendationSnapshot($existingRec->data_snapshot)) {
                        $existingRec->update(['status' => 'archived']);
                        $existingRec = null;
                    }

                    if ($force && $existingRec) {
                        $existingRec->update(['status' => 'archived']);
                        $existingRec = null;
                    }

                    // Gather current data for this pen.
                    $dataSnapshot = $this->gatherHouseData($house, $pen);

                    if (! $this->snapshotHasSensorReadings($dataSnapshot)) {
                        if ($existingRec) {
                            $existingRec->update(['status' => 'archived']);
                        }

                        Log::info("Decision support skipped for house {$house->id}, pen {$pen->id}: no current sensor readings.");
                        continue;
                    }

                    // If a valid recommendation exists and is not expired, skip.
                    if ($existingRec && $existingRec->expires_at > now()) {
                        continue;
                    }

                    // Apply predefined rules before AI summarization.
                    $dataSnapshot['rule_decisions'] = $this->evaluateRules($dataSnapshot);

                    // Generate prompt from rule decisions, not from raw data alone.
                    $prompt = $this->buildPrompt($house, $dataSnapshot);

                    // Use AI only to summarize system-generated decisions.
                    $recommendation = $this->getAIRecommendation($prompt, $dataSnapshot['rule_decisions'], $dataSnapshot);
                    $recommendation = $this->ensureFlockEnvironmentStatusIsVisible($recommendation, $dataSnapshot);

                    // Store recommendation.
                    DecisionSupportLog::create([
                        'house_id' => $house->id,
                        'pen_id' => $pen->id,
                        'recommendation_text' => $recommendation,
                        'data_snapshot' => $dataSnapshot,
                        'status' => 'active',
                        'generated_at' => now(),
                        'expires_at' => now()->addHours(1), // Valid for 1 hour
                    ]);

                    Log::info("Decision support generated for house {$house->id}, pen {$pen->id}");
                }
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Decision Support Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Gather current sensor, feed, and water data for a house
     */
    protected function gatherHouseData($house, ?Pen $pen = null)
    {
        // Get all sensors for this pen with their configured thresholds.
        $sensorQuery = Sensor::with('configuration')
            ->where('house_houseid', $house->id)
            ->whereRaw("COALESCE(NULLIF(LOWER(TRIM(status)), ''), 'active') = 'active'");

        if ($pen) {
            $sensorQuery->where('pen_penid', $pen->id);
        }

        $sensors = $sensorQuery->get();
        
        $sensorData = [];
        foreach ($sensors as $sensor) {
            $reading = SensorReading::where('sensorid', $sensor->sensorid)
                ->orderBy('recorded_at', 'desc')
                ->first();
            
            if ($reading) {
                $sensorData[] = [
                    'sensor_id' => $sensor->sensorid,
                    'sensor_name' => $sensor->sensorname,
                    'sensor_type' => $sensor->sensortype,
                    'pen_id' => $sensor->pen_penid,
                    'feeder_number' => $sensor->feeder_number,
                    'drinker_number' => $sensor->drinker_number,
                    'value' => (float) $reading->value,
                    'lowest_threshold' => $sensor->configuration?->lowestthreshold,
                    'highest_threshold' => $sensor->configuration?->highestthreshold,
                    'recorded_at' => $reading->recorded_at,
                ];
            }
        }

        // Get inventory levels - no house_id filter since Inventory doesn't have it in this project
        $feedItems = Inventory::whereRaw('LOWER(type) = ?', ['feed'])->get();
        $waterItems = Inventory::whereRaw('LOWER(type) = ?', ['water'])->get();

        $feedInventory = $feedItems->sum('remaining_stock') ?? 0;
        $waterInventory = $waterItems->sum('remaining_stock') ?? 0;

        return [
            'house_id' => $house->id,
            'house_name' => $house->house_number ?? "House {$house->id}",
            'pen' => $pen ? [
                'id' => $pen->id,
                'name' => $pen->pen_name ?? "Pen {$pen->id}",
                'population' => $pen->population,
                'capacity' => $pen->capacity,
            ] : null,
            'timestamp' => now()->toIso8601String(),
            'sensors' => $sensorData,
            'flock' => $this->currentFlockSnapshot($house, $pen),
            'environment' => [
                'temperature_celsius' => $this->currentHouseTemperature($sensorData),
                'ammonia_ppm' => $this->currentHouseAmmonia($sensorData),
            ],
            'resources' => $this->currentResourceSnapshot($sensorData),
            'inventory' => [
                'feed_level' => $feedInventory,
                'feed_critical_level' => $this->criticalLevelFor($feedItems, self::DEFAULT_FEED_CRITICAL_LEVEL),
                'feed_items_count' => $feedItems->count(),
                'water_level' => $waterInventory,
                'water_critical_level' => $this->criticalLevelFor($waterItems, self::DEFAULT_WATER_CRITICAL_LEVEL),
                'water_items_count' => $waterItems->count(),
            ],
        ];
    }

    /**
     * Evaluate predefined environmental, feed, and water rules.
     */
    protected function evaluateRules(array &$dataSnapshot): array
    {
        $decisions = [];
        $thermalDecision = $this->evaluateBroilerThermalCondition($dataSnapshot);
        $ammoniaDecision = $this->evaluateBroilerAmmoniaCondition($dataSnapshot);

        if ($thermalDecision) {
            $decisions[] = $thermalDecision;
        }

        if ($ammoniaDecision) {
            $decisions[] = $ammoniaDecision;
        }

        foreach ($this->evaluateResourceLevels($dataSnapshot) as $resourceDecision) {
            $decisions[] = $resourceDecision;
        }

        foreach ($this->evaluateCrossEnvironmentalDiagnostics($dataSnapshot) as $diagnosticDecision) {
            $decisions[] = $diagnosticDecision;
        }

        if (empty($decisions)) {
            $decisions[] = [
                'category' => 'overall',
                'severity' => 'normal',
                'rule' => 'broiler_temperature_within_age_specific_limits',
                'finding' => 'The current broiler temperature is within the age-specific comfort range.',
                'evidence' => 'No broiler thermal stress condition was detected during this decision-support run.',
                'action' => 'Continue routine monitoring and maintain the current broiler house management schedule.',
            ];
        }

        return $decisions;
    }

    /**
     * Build the prompt for AI summarization based on system rule decisions.
     */
    protected function buildPrompt($house, $dataSnapshot)
    {
        $flock = $dataSnapshot['flock'];
        $thermal = $dataSnapshot['thermal_status'] ?? [];
        $ammonia = $dataSnapshot['ammonia_status'] ?? [];
        $feed = $dataSnapshot['resources']['feed'] ?? [];
        $water = $dataSnapshot['resources']['water'] ?? [];
        $pen = $dataSnapshot['pen'] ?? null;
        $penName = $pen['name'] ?? 'Unassigned Pen';

        $prompt = "You are an expert AI Decision Support System for commercial broiler poultry farms operating in tropical climates like the Philippines. Analyze the supplied, already-calculated flock, temperature, and ammonia facts and produce immediate operational guidance for the farm manager.\n\n";
        $prompt .= "**Farm Context:**\n";
        $prompt .= "- House: {$dataSnapshot['house_name']}\n";
        $prompt .= "- Pen: {$penName}\n";
        $prompt .= "- Current Date: {$flock['current_date']}\n";
        $prompt .= "- Placement Date: " . ($flock['placement_date'] ?? 'No active placement date') . "\n";
        $prompt .= "- Flock Age: " . ($flock['age_days'] ?? 'Unknown') . " Days Old (" . ($flock['week_label'] ?? 'Unknown Week') . ")\n";
        $prompt .= "- Current Feeder Level: " . $this->formatResourceLevel($feed) . "\n";
        $prompt .= "- Feeder Critical Low Threshold: " . $this->formatThresholdSet($feed['critical_low_thresholds'] ?? []) . "\n";
        $prompt .= "- Feeder Warning/Refill Threshold: " . $this->formatThresholdSet($feed['warning_refill_thresholds'] ?? []) . "\n";
        $prompt .= "- Feeder Status Evaluation: " . $this->formatResourceStatus($feed['status'] ?? 'normal') . "\n";
        $prompt .= "- Current Drinker Level: " . $this->formatResourceLevel($water) . "\n";
        $prompt .= "- Drinker Critical Low Threshold: " . $this->formatThresholdSet($water['critical_low_thresholds'] ?? []) . "\n";
        $prompt .= "- Drinker Warning/Refill Threshold: " . $this->formatThresholdSet($water['warning_refill_thresholds'] ?? []) . "\n";
        $prompt .= "- Drinker Status Evaluation: " . $this->formatResourceStatus($water['status'] ?? 'normal') . "\n";
        $prompt .= "- Current Temperature: " . $this->formatTemperatureStatus($dataSnapshot['environment']['temperature_celsius'] ?? null) . "\n";
        $prompt .= "- Thermal Condition: " . ($thermal['condition'] ?? 'Unknown') . "\n";
        $prompt .= "- Target Range: " . ($thermal['target_range'] ?? 'Unknown') . "\n";
        $prompt .= "- Ammonia Status: " . $this->formatAmmoniaStatus($dataSnapshot['environment']['ammonia_ppm'] ?? null, $ammonia['condition'] ?? 'Unknown') . "\n";
        $prompt .= "- House Type: tropical broiler house\n\n";

        $prompt .= "**Rule-Based Decisions:**\n";
        foreach ($dataSnapshot['rule_decisions'] as $decision) {
            $prompt .= "- Category: {$decision['category']}; Severity: {$decision['severity']}; Rule: {$decision['rule']}\n";
            $prompt .= "  Finding: {$decision['finding']}\n";
            $prompt .= "  Evidence: {$decision['evidence']}\n";
            $prompt .= "  Required action: {$decision['action']}\n";
        }

        $prompt .= "**Instructions:**\n";
        $prompt .= "1. Use only the supplied system facts and rule decisions. The flock age, week, thresholds, status evaluations, and consumption trends are already calculated by the system.\n";
        $prompt .= "2. If Thermal Condition is Heat Stress, Cold Stress, or Severe Heat Danger, or if Ammonia Condition is High Risk or Critical Danger, the first visible section must be **🚨 EMERGENCY ACTION REQUIRED** before any other text.\n";
        $prompt .= "3. Apply this cross-environmental diagnostic logic exactly:\n";
        $prompt .= "   - HIGH Water Use + LOW/DROPPING Feed Use = Indicates HEAT STRESS.\n";
        $prompt .= "   - LOW Water Use + LOW Feed Use = Indicates COLD STRESS or HIGH AMMONIA LEVELS.\n";
        $prompt .= "   - RAPID WATER DROP + WET BEDDING = Predicts an impending AMMONIA SPIKE. If wet bedding is not measured, tell the farmer to physically inspect bedding and drinker equipment.\n";
        $prompt .= "4. Use the Final Output Contract below for section names and ordering.\n";
        $prompt .= "   2. **🚨 EMERGENCY ACTION REQUIRED (Only show this if condition is STRESS, HIGH RISK, or DANGER):**\n";
        $prompt .= "5. Do not mention OpenAI, prompts, or uncertainty. Do not invent unavailable data.\n";
        $prompt .= "\n**Final Output Contract - overrides the legacy section list above:**\n";
        $prompt .= "1. **REFILL ALERTS (Only display if levels breach or near system-configured thresholds):**\n";
        $prompt .= "2. **Cross-Environmental Diagnostics (Temperature & Ammonia Analysis):**\n";
        $prompt .= "3. **Immediate Actions (Next 5-30 Minutes):**\n";
        $prompt .= "Omit the REFILL ALERTS section entirely when feeder and drinker statuses are both normal. Refill alerts must name the specific resource and reference the breached configured threshold directly. Immediate actions must be 3-4 short, bolded physical steps involving feeding, watering, or environmental correction.\n";
        $prompt .= "When displaying ammonia, write it as [value]ppm - [condition], for example 11.7ppm - Moderate Risk. If ammonia has no reading, write No Reading.\n";

        return $prompt;
    }

    /**
     * Get AI recommendation from OpenAI
     */
    protected function getAIRecommendation($prompt, array $ruleDecisions, array $dataSnapshot)
    {
        try {
            $response = $this->getClient()->chat()->create([
                'model' => config('services.openai.model') ?? 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You generate broiler poultry decision support from supplied rule calculations. Preserve the requested section order exactly and prioritize configured feeder/drinker threshold alerts plus temperature and ammonia diagnostics.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.3,
                'max_tokens' => 700,
            ]);

            return $response->choices[0]->message->content;
        } catch (\Exception $e) {
            Log::error('OpenAI API Error: ' . $e->getMessage());
            return $this->formatRuleRecommendations($ruleDecisions, $dataSnapshot);
        }
    }

    protected function criticalLevelFor($items, float $fallback): float
    {
        $configuredCritical = $items
            ->filter(fn ($item) => is_numeric($item->critical ?? null) && (float) $item->critical > 0)
            ->sum('critical');

        return $configuredCritical > 0 ? (float) $configuredCritical : $fallback;
    }

    protected function formatResourceLevel(array $resource): string
    {
        return is_numeric($resource['current_level'] ?? null)
            ? $resource['current_level'] . '%'
            : 'No sensor reading';
    }

    protected function formatThresholdSet(array $thresholds): string
    {
        $values = collect($thresholds)
            ->filter(fn ($value) => is_numeric($value))
            ->map(fn ($value) => ((float) $value) . '%')
            ->values();

        return $values->isEmpty() ? 'Not configured' : $values->implode(', ');
    }

    protected function formatResourceStatus(string $status): string
    {
        return match ($status) {
            'critical' => 'Critical Low (Below System Threshold)',
            'warning' => 'Warning (Nearing System Threshold)',
            default => 'Normal',
        };
    }

    protected function formatDropRate($dropRate): string
    {
        return is_numeric($dropRate) ? round((float) $dropRate, 2) . '%/hour' : 'Unknown';
    }

    protected function formatAmmoniaStatus($ammonia, string $condition): string
    {
        return is_numeric($ammonia)
            ? round((float) $ammonia, 1) . 'ppm - ' . $condition
            : 'No Reading';
    }

    protected function formatTemperatureStatus($temperature): string
    {
        return is_numeric($temperature)
            ? round((float) $temperature, 1) . 'C'
            : 'No Reading';
    }

    protected function formatRuleRecommendations(array $ruleDecisions, array $dataSnapshot = []): string
    {
        if ($dataSnapshot) {
            return $this->formatEquipmentDecisionRecommendation($dataSnapshot, $ruleDecisions);
        }

        $decisions = collect($ruleDecisions);
        $criticalAmmoniaDecision = $decisions
            ->where('category', 'broiler_ammonia')
            ->where('severity', 'critical')
            ->first();

        if ($criticalAmmoniaDecision) {
            return $this->formatAmmoniaDecisionRecommendation($criticalAmmoniaDecision);
        }

        $criticalThermalDecision = $decisions
            ->where('category', 'broiler_temperature')
            ->where('severity', 'critical')
            ->first();

        if ($criticalThermalDecision) {
            return $this->formatBroilerDecisionRecommendation($criticalThermalDecision);
        }

        $warningAmmoniaDecision = $decisions
            ->where('category', 'broiler_ammonia')
            ->where('severity', 'warning')
            ->first();

        if ($warningAmmoniaDecision) {
            return $this->formatAmmoniaDecisionRecommendation($warningAmmoniaDecision);
        }

        $thermalDecision = $decisions->firstWhere('category', 'broiler_temperature');

        if ($thermalDecision) {
            return $this->formatBroilerDecisionRecommendation($thermalDecision);
        }

        $ammoniaDecision = $decisions->firstWhere('category', 'broiler_ammonia');

        if ($ammoniaDecision) {
            return $this->formatAmmoniaDecisionRecommendation($ammoniaDecision);
        }

        return $decisions
            ->sortByDesc(fn ($decision) => match ($decision['severity']) {
                'critical' => 3,
                'warning' => 2,
                default => 1,
            })
            ->take(3)
            ->values()
            ->map(fn ($decision, $index) => ($index + 1) . '. ' . $decision['action'] . ' ' . $decision['evidence'])
            ->implode("\n");
    }

    protected function formatEquipmentDecisionRecommendation(array $dataSnapshot, array $ruleDecisions): string
    {
        $feed = $dataSnapshot['resources']['feed'] ?? [];
        $water = $dataSnapshot['resources']['water'] ?? [];
        $temperature = $dataSnapshot['environment']['temperature_celsius'] ?? null;
        $ammonia = $dataSnapshot['environment']['ammonia_ppm'] ?? null;
        $thermalCondition = $dataSnapshot['thermal_status']['condition'] ?? 'Unknown';
        $ammoniaCondition = $dataSnapshot['ammonia_status']['condition'] ?? 'Unknown';
        $decisions = collect($ruleDecisions);
        $resourceAlerts = $decisions
            ->whereIn('category', ['feed', 'water'])
            ->whereIn('severity', ['critical', 'warning'])
            ->filter(fn ($decision) => str_contains((string) ($decision['rule'] ?? ''), '_level_'))
            ->values();
        $diagnostics = $decisions
            ->where('category', 'cross_environmental_diagnostic')
            ->values();
        $actions = $decisions
            ->sortByDesc(fn ($decision) => match ($decision['severity'] ?? 'normal') {
                'critical' => 3,
                'warning' => 2,
                default => 1,
            })
            ->pluck('action')
            ->filter()
            ->unique()
            ->take(4)
            ->values();

        if ($actions->isEmpty()) {
            $actions = collect([
                'Check feeder and drinker access in this pen.',
                'Inspect bird distribution, panting, huddling, and respiratory signs.',
                'Verify temperature, ventilation, ammonia, and litter condition at bird height.',
            ]);
        }

        $lines = [];

        if ($resourceAlerts->isNotEmpty()) {
            $lines[] = '1. **REFILL ALERTS (Only display if levels breach or near system-configured thresholds):**';
            foreach ($resourceAlerts as $alert) {
                $lines[] = '   - ' . $alert['action'];
            }
            $lines[] = '';
        }

        $lines[] = '2. **Cross-Environmental Diagnostics (Temperature & Ammonia Analysis):**';
        $lines[] = '   - Current Temperature: ' . $this->formatTemperatureStatus($temperature) . "; Thermal Condition: {$thermalCondition}.";
        $lines[] = '   - Ammonia: ' . $this->formatAmmoniaStatus($ammonia, $ammoniaCondition) . '.';

        if ($diagnostics->isNotEmpty()) {
            foreach ($diagnostics as $diagnostic) {
                $lines[] = '   - ' . $diagnostic['finding'] . ' ' . $diagnostic['action'];
            }
        }

        $lines[] = '';
        $lines[] = '3. **Immediate Actions (Next 5-30 Minutes):**';
        foreach ($actions as $action) {
            $lines[] = '   - **' . $action . '**';
        }

        return implode("\n", $lines);
    }

    protected function ensureFlockEnvironmentStatusIsVisible(string $recommendation, array $dataSnapshot): string
    {
        if (str_contains($recommendation, 'Cross-Environmental Diagnostics') && str_contains($recommendation, 'Immediate Actions')) {
            return $recommendation;
        }

        return $this->formatEquipmentDecisionRecommendation(
            $dataSnapshot,
            $dataSnapshot['rule_decisions'] ?? []
        ) . "\n" . ltrim($recommendation);
    }

    protected function formatFlockEnvironmentStatusBlock(array $dataSnapshot): string
    {
        $flock = $dataSnapshot['flock'] ?? [];
        $temperature = $dataSnapshot['environment']['temperature_celsius'] ?? null;
        $ammonia = $dataSnapshot['environment']['ammonia_ppm'] ?? null;
        $thermalCondition = $dataSnapshot['thermal_status']['condition'] ?? 'Unknown';
        $ammoniaCondition = $dataSnapshot['ammonia_status']['condition'] ?? 'Unknown';
        $age = $flock['age_days'] ?? 'Unknown';
        $week = $flock['week_label'] ?? 'Unknown Week';
        $temperatureText = $this->formatTemperatureStatus($temperature);
        $ammoniaText = $this->formatAmmoniaStatus($ammonia, $ammoniaCondition);

        return implode("\n", [
            '1. **Flock Status Analysis:**',
            '   - Current Date: ' . ($flock['current_date'] ?? now()->toDateString()),
            '   - Placement Date: ' . ($flock['placement_date'] ?? 'No active placement date'),
            "   - Flock Age: {$age} Days Old ({$week})",
            "   - Current Temperature: {$temperatureText}",
            "   - Thermal Condition: {$thermalCondition}",
            '',
            '2. **Ammonia Status Analysis:**',
            "   - Ammonia: {$ammoniaText}",
            '',
        ]);
    }

    protected function currentFlockSnapshot(House $house, ?Pen $pen = null): array
    {
        $runningBatchQuery = FlockBatch::running()
            ->where('house_id', $house->id)
            ->whereNotNull('started_at');

        if ($pen) {
            $runningBatchQuery->where('pen_id', $pen->id);
        }

        $runningBatches = $runningBatchQuery
            ->orderBy('started_at')
            ->get();

        $placementDate = $runningBatches->first()?->started_at;
        $currentDate = now()->startOfDay();

        if (!$placementDate) {
            return [
                'current_date' => $currentDate->toDateString(),
                'placement_date' => null,
                'age_days' => null,
                'week_number' => null,
                'week_label' => null,
                'running_batches_count' => $runningBatches->count(),
            ];
        }

        $placement = Carbon::parse($placementDate)->startOfDay();
        $ageDays = (int) max(0, $placement->diffInDays($currentDate, false));
        $weekNumber = $this->broilerWeekNumber($ageDays);

        return [
            'current_date' => $currentDate->toDateString(),
            'placement_date' => $placement->toDateString(),
            'age_days' => $ageDays,
            'week_number' => $weekNumber,
            'week_label' => self::BROILER_TEMPERATURE_THRESHOLDS[$weekNumber]['week_label'],
            'running_batches_count' => $runningBatches->count(),
        ];
    }

    protected function currentHouseTemperature(array $sensorData): ?float
    {
        $temperatureReadings = collect($sensorData)
            ->filter(fn ($sensor) => str_contains(strtolower((string) $sensor['sensor_type']), 'temp'))
            ->pluck('value')
            ->filter(fn ($value) => is_numeric($value))
            ->map(fn ($value) => (float) $value)
            ->values();

        if ($temperatureReadings->isEmpty()) {
            return null;
        }

        return round($temperatureReadings->avg(), 1);
    }

    protected function currentHouseAmmonia(array $sensorData): ?float
    {
        $ammoniaReadings = collect($sensorData)
            ->filter(function ($sensor) {
                $type = strtolower((string) $sensor['sensor_type']);

                return str_contains($type, 'ammonia') || str_contains($type, 'nh3');
            })
            ->pluck('value')
            ->filter(fn ($value) => is_numeric($value))
            ->map(fn ($value) => (float) $value)
            ->values();

        if ($ammoniaReadings->isEmpty()) {
            return null;
        }

        return round($ammoniaReadings->avg(), 1);
    }

    protected function currentResourceSnapshot(array $sensorData): array
    {
        return [
            'feed' => $this->resourceSnapshotForType($sensorData, 'feed'),
            'water' => $this->resourceSnapshotForType($sensorData, 'water'),
        ];
    }

    protected function resourceSnapshotForType(array $sensorData, string $type): array
    {
        $sensors = collect($sensorData)
            ->filter(fn ($sensor) => $this->sensorMatchesResourceType($sensor, $type))
            ->map(function ($sensor) use ($type) {
                $previousReading = SensorReading::where('sensorid', $sensor['sensor_id'])
                    ->orderBy('recorded_at', 'desc')
                    ->skip(1)
                    ->first();

                $currentValue = (float) $sensor['value'];
                $previousValue = $previousReading ? (float) $previousReading->value : null;
                $hoursSincePrevious = null;
                $dropPoints = null;
                $dropRatePerHour = null;

                if ($previousReading && $sensor['recorded_at']) {
                    $currentRecordedAt = Carbon::parse($sensor['recorded_at']);
                    $previousRecordedAt = Carbon::parse($previousReading->recorded_at);
                    $minutes = max(1, $previousRecordedAt->diffInMinutes($currentRecordedAt, false));
                    $hoursSincePrevious = round($minutes / 60, 2);
                    $dropPoints = round(max(0, $previousValue - $currentValue), 2);
                    $dropRatePerHour = round($dropPoints / max($hoursSincePrevious, 0.01), 2);
                }

                $criticalThreshold = is_numeric($sensor['lowest_threshold'] ?? null)
                    ? (float) $sensor['lowest_threshold']
                    : null;
                $warningThreshold = is_numeric($sensor['highest_threshold'] ?? null)
                    ? (float) $sensor['highest_threshold']
                    : null;

                return [
                    'sensor_id' => $sensor['sensor_id'],
                    'sensor_name' => $sensor['sensor_name'],
                    'sensor_type' => $sensor['sensor_type'],
                    'resource_number' => $type === 'feed'
                        ? $sensor['feeder_number']
                        : $sensor['drinker_number'],
                    'value' => $currentValue,
                    'previous_value' => $previousValue,
                    'drop_points' => $dropPoints,
                    'drop_rate_per_hour' => $dropRatePerHour,
                    'hours_since_previous' => $hoursSincePrevious,
                    'critical_low_threshold' => $criticalThreshold,
                    'warning_refill_threshold' => $warningThreshold,
                    'status' => $this->resourceLevelStatus($currentValue, $criticalThreshold, $warningThreshold),
                    'recorded_at' => $sensor['recorded_at'],
                ];
            })
            ->values();

        $values = $sensors->pluck('value')->filter(fn ($value) => is_numeric($value))->values();
        $dropRates = $sensors->pluck('drop_rate_per_hour')->filter(fn ($value) => is_numeric($value))->values();
        $criticalThresholds = $sensors->pluck('critical_low_threshold')->filter(fn ($value) => is_numeric($value))->values();
        $warningThresholds = $sensors->pluck('warning_refill_threshold')->filter(fn ($value) => is_numeric($value))->values();
        $statuses = $sensors->pluck('status')->values();

        return [
            'type' => $type,
            'label' => $type === 'feed' ? 'Feeder' : 'Drinker',
            'current_level' => $values->isEmpty() ? null : round($values->avg(), 1),
            'critical_low_threshold' => $criticalThresholds->isEmpty() ? null : round($criticalThresholds->max(), 1),
            'warning_refill_threshold' => $warningThresholds->isEmpty() ? null : round($warningThresholds->max(), 1),
            'critical_low_thresholds' => $criticalThresholds->unique()->sort()->values()->all(),
            'warning_refill_thresholds' => $warningThresholds->unique()->sort()->values()->all(),
            'drop_rate_per_hour' => $dropRates->isEmpty() ? null : round($dropRates->avg(), 2),
            'trend' => $this->resourceTrend($dropRates->isEmpty() ? null : (float) $dropRates->avg()),
            'status' => $this->highestResourceStatus($statuses->all()),
            'sensors_count' => $sensors->count(),
            'sensors' => $sensors->all(),
        ];
    }

    protected function sensorMatchesResourceType(array $sensor, string $type): bool
    {
        $sensorType = strtolower((string) ($sensor['sensor_type'] ?? ''));

        return $type === 'feed'
            ? str_contains($sensorType, 'feed')
            : str_contains($sensorType, 'water');
    }

    protected function resourceLevelStatus(float $value, ?float $criticalThreshold, ?float $warningThreshold): string
    {
        if ($criticalThreshold !== null && $value <= $criticalThreshold) {
            return 'critical';
        }

        if ($warningThreshold !== null && $value <= $warningThreshold) {
            return 'warning';
        }

        return 'normal';
    }

    protected function highestResourceStatus(array $statuses): string
    {
        if (in_array('critical', $statuses, true)) {
            return 'critical';
        }

        if (in_array('warning', $statuses, true)) {
            return 'warning';
        }

        return 'normal';
    }

    protected function resourceTrend(?float $dropRatePerHour): string
    {
        if ($dropRatePerHour === null) {
            return 'unknown';
        }

        if ($dropRatePerHour >= 5) {
            return 'rapid_drop';
        }

        if ($dropRatePerHour >= 1) {
            return 'dropping';
        }

        return 'stable';
    }

    protected function evaluateResourceLevels(array &$dataSnapshot): array
    {
        $decisions = [];
        $resources = $dataSnapshot['resources'] ?? [];

        foreach (['feed' => 'feeder', 'water' => 'drinker'] as $type => $resourceName) {
            $resource = $resources[$type] ?? null;

            if (!$resource || (int) ($resource['sensors_count'] ?? 0) === 0) {
                $decisions[] = [
                    'category' => $type,
                    'severity' => 'warning',
                    'rule' => "{$type}_sensor_missing",
                    'finding' => "No active {$resourceName} level sensor reading is available for this pen.",
                    'evidence' => 'Sensor count: 0.',
                    'action' => "Verify the {$resourceName} sensor assignment and latest telemetry before relying on automated refill guidance.",
                ];
                continue;
            }

            foreach ($resource['sensors'] as $sensor) {
                if ($sensor['status'] === 'critical') {
                    $decisions[] = [
                        'category' => $type,
                        'severity' => 'critical',
                        'rule' => "{$type}_level_below_configured_critical_low_threshold",
                        'finding' => ucfirst($resourceName) . " {$sensor['resource_number']} is below the configured critical low threshold.",
                        'evidence' => "Current {$resourceName} level: {$sensor['value']}%; configured critical low threshold: {$sensor['critical_low_threshold']}%.",
                        'action' => "Refill {$resourceName} {$sensor['resource_number']} immediately and confirm birds can access it.",
                    ];
                    continue;
                }

                if ($sensor['status'] === 'warning') {
                    $decisions[] = [
                        'category' => $type,
                        'severity' => 'warning',
                        'rule' => "{$type}_level_near_configured_warning_refill_threshold",
                        'finding' => ucfirst($resourceName) . " {$sensor['resource_number']} is near the configured warning/refill threshold.",
                        'evidence' => "Current {$resourceName} level: {$sensor['value']}%; configured warning/refill threshold: {$sensor['warning_refill_threshold']}%.",
                        'action' => "Schedule refill for {$resourceName} {$sensor['resource_number']} before it reaches the critical low threshold.",
                    ];
                }
            }
        }

        return $decisions;
    }

    protected function evaluateCrossEnvironmentalDiagnostics(array &$dataSnapshot): array
    {
        $feed = $dataSnapshot['resources']['feed'] ?? [];
        $water = $dataSnapshot['resources']['water'] ?? [];
        $feedDrop = $feed['drop_rate_per_hour'] ?? null;
        $waterDrop = $water['drop_rate_per_hour'] ?? null;
        $temperature = $dataSnapshot['environment']['temperature_celsius'] ?? null;
        $ammonia = $dataSnapshot['environment']['ammonia_ppm'] ?? null;
        $decisions = [];

        if (!is_numeric($feedDrop) || !is_numeric($waterDrop)) {
            return [];
        }

        if ($waterDrop >= 3 && $feedDrop <= 1) {
            $decisions[] = [
                'category' => 'cross_environmental_diagnostic',
                'severity' => 'warning',
                'rule' => 'high_water_use_low_feed_use_possible_heat_stress',
                'finding' => 'High drinker drawdown with low feeder drawdown can indicate hidden heat stress.',
                'evidence' => "Feeder drop rate: {$feedDrop}%/hour; drinker drop rate: {$waterDrop}%/hour; temperature: " . ($temperature ?? 'missing') . 'C.',
                'action' => 'Physically inspect bird panting, wing spreading, airflow, drinker temperature, and hot zones around this pen.',
            ];
        }

        if ($waterDrop <= 0.5 && $feedDrop <= 0.5) {
            $decisions[] = [
                'category' => 'cross_environmental_diagnostic',
                'severity' => 'warning',
                'rule' => 'low_water_use_low_feed_use_possible_cold_stress_or_ammonia',
                'finding' => 'Low drinker and feeder drawdown can indicate cold stress or high ammonia limiting bird movement.',
                'evidence' => "Feeder drop rate: {$feedDrop}%/hour; drinker drop rate: {$waterDrop}%/hour; ammonia: " . ($ammonia ?? 'missing') . ' ppm.',
                'action' => 'Inspect huddling, chick distribution, eye irritation, respiratory distress, litter condition, and bird-level ammonia odor.',
            ];
        }

        if ($waterDrop >= 8) {
            $decisions[] = [
                'category' => 'cross_environmental_diagnostic',
                'severity' => 'warning',
                'rule' => 'rapid_water_drop_possible_wet_bedding_ammonia_spike',
                'finding' => 'Rapid drinker drawdown can indicate leakage or spillage that may create wet bedding and an ammonia spike.',
                'evidence' => "Drinker drop rate: {$waterDrop}%/hour; current ammonia: " . ($ammonia ?? 'missing') . ' ppm.',
                'action' => 'Check nipples, drinker height, loose fittings, wet bedding, and ammonia at bird height immediately.',
            ];
        }

        return $decisions;
    }

    protected function evaluateBroilerThermalCondition(array &$dataSnapshot): ?array
    {
        $flock = $dataSnapshot['flock'];
        $temperature = $dataSnapshot['environment']['temperature_celsius'];

        if (!is_numeric($flock['age_days'] ?? null) || !is_numeric($temperature)) {
            $dataSnapshot['thermal_status'] = [
                'condition' => 'Unknown',
                'target_range' => 'Unknown',
            ];

            return [
                'category' => 'broiler_temperature',
                'severity' => 'warning',
                'rule' => 'missing_active_flock_or_temperature_data',
                'finding' => 'Decision Support needs both an active flock placement date and a current temperature reading.',
                'evidence' => 'Placement date: ' . ($flock['placement_date'] ?? 'missing') . '; temperature: ' . ($temperature ?? 'missing') . '.',
                'action' => 'Verify the active chick placement batch and the temperature sensor reading for this house.',
                'thermal_status' => $dataSnapshot['thermal_status'],
                'flock' => $flock,
            ];
        }

        $threshold = self::BROILER_TEMPERATURE_THRESHOLDS[$flock['week_number']];
        $condition = 'Normal';
        $severity = 'normal';
        $rule = 'broiler_temperature_within_age_specific_limits';

        if (($threshold['severe_heat_at'] ?? null) !== null && $temperature >= $threshold['severe_heat_at']) {
            $condition = 'Severe Heat Danger';
            $severity = 'critical';
            $rule = 'broiler_severe_heat_danger';
        } elseif ($temperature > $threshold['heat_above']) {
            $condition = 'Heat Stress';
            $severity = 'critical';
            $rule = 'broiler_heat_stress';
        } elseif ($temperature < $threshold['cold_below']) {
            $condition = 'Cold Stress';
            $severity = 'critical';
            $rule = 'broiler_cold_stress';
        }

        $thermalStatus = [
            'condition' => $condition,
            'target_range' => "{$threshold['target_min']}°C - {$threshold['target_max']}°C",
            'cold_stress_below' => $threshold['cold_below'],
            'heat_stress_above' => $threshold['heat_above'],
            'severe_heat_at' => $threshold['severe_heat_at'] ?? null,
        ];
        $dataSnapshot['thermal_status'] = $thermalStatus;

        return [
            'category' => 'broiler_temperature',
            'severity' => $severity,
            'rule' => $rule,
            'finding' => "Thermal condition is {$condition} for {$flock['week_label']} broilers.",
            'evidence' => "Current date: {$flock['current_date']}; placement date: {$flock['placement_date']}; flock age: {$flock['age_days']} days; current temperature: {$temperature}°C; target: {$thermalStatus['target_range']}.",
            'action' => $this->primaryThermalAction($condition),
            'thermal_status' => $thermalStatus,
            'flock' => $flock,
            'temperature_celsius' => $temperature,
        ];
    }

    protected function evaluateBroilerAmmoniaCondition(array &$dataSnapshot): ?array
    {
        $flock = $dataSnapshot['flock'];
        $ammonia = $dataSnapshot['environment']['ammonia_ppm'];

        if (!is_numeric($flock['age_days'] ?? null) || !is_numeric($ammonia)) {
            $dataSnapshot['ammonia_status'] = [
                'condition' => 'Unknown',
                'target_range' => '0 ppm - 10 ppm',
            ];

            return [
                'category' => 'broiler_ammonia',
                'severity' => 'warning',
                'rule' => 'missing_active_flock_or_ammonia_data',
                'finding' => 'Decision Support needs both an active flock placement date and a current ammonia reading.',
                'evidence' => 'Placement date: ' . ($flock['placement_date'] ?? 'missing') . '; ammonia: ' . ($ammonia ?? 'missing') . '.',
                'action' => 'Verify the active chick placement batch and the ammonia sensor reading for this house.',
                'ammonia_status' => $dataSnapshot['ammonia_status'],
                'flock' => $flock,
            ];
        }

        $condition = match (true) {
            $ammonia >= 25 => 'Critical Danger',
            $ammonia >= 20 => 'High Risk',
            $ammonia >= 11 => 'Moderate Risk',
            default => 'Safe',
        };
        $severity = in_array($condition, ['High Risk', 'Critical Danger'], true)
            ? 'critical'
            : ($condition === 'Moderate Risk' ? 'warning' : 'normal');
        $rule = match ($condition) {
            'Critical Danger' => 'ammonia_critical_danger',
            'High Risk' => 'ammonia_high_risk_action_required',
            'Moderate Risk' => 'ammonia_moderate_risk',
            default => 'ammonia_safe_target_zone',
        };
        $ammoniaStatus = [
            'condition' => $condition,
            'target_range' => '0 ppm - 10 ppm',
            'moderate_risk_range' => '11 ppm - 19 ppm',
            'high_risk_range' => '20 ppm - 24 ppm',
            'critical_danger_at' => 25,
        ];
        $dataSnapshot['ammonia_status'] = $ammoniaStatus;

        return [
            'category' => 'broiler_ammonia',
            'severity' => $severity,
            'rule' => $rule,
            'finding' => "Ammonia condition is {$condition} for {$flock['week_label']} broilers.",
            'evidence' => "Current date: {$flock['current_date']}; placement date: {$flock['placement_date']}; flock age: {$flock['age_days']} days; current ammonia: {$ammonia} ppm; safe target: {$ammoniaStatus['target_range']}.",
            'action' => $this->primaryAmmoniaAction($condition),
            'ammonia_status' => $ammoniaStatus,
            'flock' => $flock,
            'ammonia_ppm' => $ammonia,
        ];
    }

    protected function broilerWeekNumber(int $ageDays): int
    {
        return match (true) {
            $ageDays <= 7 => 1,
            $ageDays <= 14 => 2,
            $ageDays <= 21 => 3,
            $ageDays <= 28 => 4,
            default => 5,
        };
    }

    protected function primaryThermalAction(string $condition): string
    {
        return match ($condition) {
            'Cold Stress' => 'Add heat, reduce drafts, and physically spread chicks away from cold corners or clumps.',
            'Heat Stress', 'Severe Heat Danger' => 'Increase air movement, remove heat sources, cool drinking water, and disperse panting or piled birds immediately.',
            default => 'Maintain the current temperature program and continue close flock observation.',
        };
    }

    protected function primaryAmmoniaAction(string $condition): string
    {
        return match ($condition) {
            'Critical Danger' => 'Maximize exhaust ventilation, lift curtains, and start immediate wet-litter correction to evacuate ammonia gas.',
            'High Risk' => 'Increase ventilation now, identify wet litter sources, and apply immediate litter amendment in the affected zones.',
            'Moderate Risk' => 'Improve minimum ventilation and correct wet litter before ammonia reaches respiratory-damaging levels.',
            default => 'Maintain litter dryness, ventilation cycles, and routine ammonia monitoring.',
        };
    }

    protected function formatBroilerDecisionRecommendation(array $decision): string
    {
        $flock = $decision['flock'];
        $condition = $decision['thermal_status']['condition'] ?? 'Unknown';
        $temperature = $decision['temperature_celsius'] ?? null;
        $isEmergency = in_array($condition, ['Cold Stress', 'Heat Stress', 'Severe Heat Danger'], true);

        $lines = [];

        if ($isEmergency) {
            $lines[] = '2. **🚨 EMERGENCY ACTION REQUIRED (Only show this if condition is STRESS or DANGER):**';
            foreach ($this->emergencyActionsFor($condition) as $action) {
                $lines[] = "   - {$action}";
            }
            $lines[] = '';
        }

        $lines[] = '1. **Flock Status Analysis:**';
        $lines[] = "   - Current Date: {$flock['current_date']}";
        $lines[] = '   - Placement Date: ' . ($flock['placement_date'] ?? 'No active placement date');
        $lines[] = '   - Flock Age: ' . ($flock['age_days'] ?? 'Unknown') . ' Days Old (' . ($flock['week_label'] ?? 'Unknown Week') . ')';
        $lines[] = '   - Current Temperature: ' . $this->formatTemperatureStatus($temperature);
        $lines[] = "   - Thermal Condition: {$condition}";
        $lines[] = '';
        $lines[] = '3. **Standard Management Actions (Next 1-2 Hours):**';
        foreach ($this->standardActionsFor($condition) as $action) {
            $lines[] = "   - {$action}";
        }
        $lines[] = '';
        $lines[] = '4. **Critical Warning / Observation Note:**';
        $lines[] = '   - ' . $this->observationNoteFor($condition);

        return implode("\n", $lines);
    }

    protected function formatAmmoniaDecisionRecommendation(array $decision): string
    {
        $flock = $decision['flock'];
        $condition = $decision['ammonia_status']['condition'] ?? 'Unknown';
        $ammonia = $decision['ammonia_ppm'] ?? null;
        $isEmergency = in_array($condition, ['High Risk', 'Critical Danger'], true);

        $lines = [];

        if ($isEmergency) {
            $lines[] = '2. **🚨 EMERGENCY ACTION REQUIRED (Only show this if condition is HIGH RISK or CRITICAL DANGER):**';
            foreach ($this->ammoniaEmergencyActionsFor($condition) as $action) {
                $lines[] = "   - {$action}";
            }
            $lines[] = '';
        }

        $lines[] = '1. **Flock Status Analysis:**';
        $lines[] = "   - Current Date: {$flock['current_date']}";
        $lines[] = '   - Placement Date: ' . ($flock['placement_date'] ?? 'No active placement date');
        $lines[] = '   - Flock Age: ' . ($flock['age_days'] ?? 'Unknown') . ' Days Old (' . ($flock['week_label'] ?? 'Unknown Week') . ')';
        $lines[] = '   - Ammonia: ' . $this->formatAmmoniaStatus($ammonia, $condition);
        $lines[] = '';
        $lines[] = '3. **Standard Management Actions (Next 1-2 Hours):**';
        foreach ($this->ammoniaStandardActionsFor($condition) as $action) {
            $lines[] = "   - {$action}";
        }
        $lines[] = '';
        $lines[] = '4. **Critical Warning / Observation Note:**';
        $lines[] = '   - ' . $this->ammoniaObservationNoteFor($condition);

        return implode("\n", $lines);
    }

    protected function emergencyActionsFor(string $condition): array
    {
        return match ($condition) {
            'Cold Stress' => [
                'Turn on brooders or add safe supplemental heat now.',
                'Close side curtains enough to stop direct drafts.',
                'Physically scatter clumping chicks into the warm zone.',
                'Check gas, power, and brooder flame immediately.',
            ],
            'Severe Heat Danger' => [
                'Maximize fans and open airflow paths immediately.',
                'Cut off heaters and unnecessary lights now.',
                'Dump ice or chilled water into water tanks.',
                'Physically spread panting or piled birds away from walls.',
            ],
            'Heat Stress' => [
                'Increase fan speed and open curtains for cross-ventilation.',
                'Shut off heat sources and reduce unnecessary lighting.',
                'Flush drinker lines and supply cool fresh water.',
                'Walk the house and spread birds that are panting or piling.',
            ],
            default => [],
        };
    }

    protected function ammoniaEmergencyActionsFor(string $condition): array
    {
        return match ($condition) {
            'Critical Danger' => [
                'Maximize exhaust fans immediately.',
                'Lift side curtains to flush trapped gas.',
                'Open inlets and remove dead-air pockets now.',
                'Top-dress wet litter with litter amendment in hot spots.',
            ],
            'High Risk' => [
                'Increase fan speed and minimum ventilation now.',
                'Lift curtains enough to purge ammonia buildup.',
                'Find and isolate wet litter patches immediately.',
                'Apply litter conditioner to caked manure zones.',
            ],
            default => [],
        };
    }

    protected function standardActionsFor(string $condition): array
    {
        return match ($condition) {
            'Cold Stress' => [
                '**Stabilize brooding heat** by holding the target range and checking floor-level chick temperature every 15 minutes.',
                '**Reduce wind chill** by sealing curtain gaps while keeping minimum ventilation for air quality.',
                '**Restore feed and water access** by moving trays and drinkers closer to the warm chick zone.',
                '**Log the event** with temperature, actions taken, and chick behavior after 1 hour.',
            ],
            'Heat Stress', 'Severe Heat Danger' => [
                '**Shift feeding to cooler hours** and avoid heavy feed activity during peak afternoon heat.',
                '**Flush water lines repeatedly** until nipple-line water is cool to the touch.',
                '**Improve air exchange** by checking fan belts, inlets, curtains, and dead-air corners.',
                '**Recheck temperature every 15 minutes** until the house returns below the stress threshold.',
            ],
            'Normal' => [
                '**Maintain current ventilation settings** while checking for uneven hot or cold zones across pens.',
                '**Keep water lines flushed** so broilers maintain intake in tropical heat.',
                '**Verify sensor placement** at bird height and away from direct sun, walls, or heater blast.',
                '**Continue routine flock walks** and record any panting, huddling, or piling behavior.',
            ],
            default => [
                '**Verify flock placement data** so age-specific temperature targets can be calculated.',
                '**Confirm temperature sensor status** and restore the latest reading for this house.',
                '**Perform a manual house check** for panting, huddling, piling, drafts, and water availability.',
            ],
        };
    }

    protected function ammoniaStandardActionsFor(string $condition): array
    {
        return match ($condition) {
            'Critical Danger', 'High Risk' => [
                '**Run sustained ventilation cycles** until ammonia drops below 20 ppm and bird-level air smells clear.',
                '**Check water lines and nipples** for leaks causing wet litter under drinker paths.',
                '**Rake and remove caked manure** in wet zones, especially around drinkers, walls, and low-airflow corners.',
                '**Apply approved litter conditioner** to wet ammonia-producing areas and recheck ppm within 1 hour.',
            ],
            'Moderate Risk' => [
                '**Increase minimum ventilation** before ammonia reaches respiratory-damaging levels.',
                '**Inspect drinker height and leaks** to stop new wet litter formation.',
                '**Break up caked litter** and dry the high-moisture patches under drinker lines.',
                '**Recheck ammonia at bird height** after ventilation and litter correction.',
            ],
            'Safe' => [
                '**Maintain dry litter** by checking drinker leaks and caked manure during each house walk.',
                '**Keep minimum ventilation active** even during cooler periods to prevent gas buildup.',
                '**Measure ammonia at bird height** because ceiling-level readings can miss chick exposure.',
                '**Record the ppm reading** with the flock age for trend monitoring.',
            ],
            default => [
                '**Verify ammonia sensor status** and restore the latest NH3 reading for this house.',
                '**Perform a manual odor and bird-level air check** near drinkers and low-ventilation corners.',
                '**Inspect litter moisture** while sensor data is unavailable.',
            ],
        };
    }

    protected function observationNoteFor(string $condition): string
    {
        return match ($condition) {
            'Cold Stress' => 'Watch for loud chirping, tight huddling, piling, wet litter, and early ascites risk in chilled chicks.',
            'Heat Stress', 'Severe Heat Danger' => 'Watch for open-mouth panting, wing spreading, lethargy, heat prostration, and sudden mortality in older broilers.',
            'Normal' => 'Even in normal range, watch for uneven bird distribution because it can reveal drafts, hot spots, or sensor placement errors.',
            default => 'Missing age or temperature data can hide a real heat or cold emergency, so inspect the flock physically right now.',
        };
    }

    protected function ammoniaObservationNoteFor(string $condition): string
    {
        return match ($condition) {
            'Critical Danger', 'High Risk' => 'Watch for watery eyes, birds pawing at eyes, blindness, gasping, respiratory gurgling, and sudden collapse from airway burns.',
            'Moderate Risk' => 'Watch for mild eye irritation, head shaking, sneezing, reduced feeding, and early respiratory stress.',
            'Safe' => 'Even in the safe range, check bird-level air near wet litter because ammonia can concentrate in low-flow pockets.',
            default => 'Missing ammonia data can hide a respiratory emergency, so inspect bird behavior and litter moisture right now.',
        };
    }

    protected function isLegacyFailureRecommendation(?string $recommendation): bool
    {
        return trim((string) $recommendation) === self::LEGACY_FAILURE_RECOMMENDATION;
    }

    protected function isOutdatedRecommendationSnapshot(?array $snapshot): bool
    {
        if (!$snapshot) {
            return true;
        }

        return !isset($snapshot['flock'], $snapshot['environment'], $snapshot['resources'], $snapshot['thermal_status'], $snapshot['ammonia_status'], $snapshot['pen']);
    }

    protected function snapshotHasSensorReadings(?array $snapshot): bool
    {
        return !empty($snapshot['sensors']) && collect($snapshot['sensors'])->contains(function ($sensor) {
            return array_key_exists('value', $sensor) && is_numeric($sensor['value']);
        });
    }

    /**
     * Get latest active recommendation for a house
     */
    public function getLatestRecommendation($houseId, $penId = null)
    {
        $query = DecisionSupportLog::forHouse($houseId)->active();

        if ($penId) {
            $query->forPen($penId);
        }

        return $query->orderBy('generated_at', 'desc')->first();
    }

    /**
     * Get active recommendations for all houses
     */
    public function getAllActiveRecommendations()
    {
        return DecisionSupportLog::with(['house', 'pen'])
            ->active()
            ->whereNotNull('pen_id')
            ->whereHas('house', function ($query) {
                $query->whereNull('archived_at');
            })
            ->whereHas('pen', function ($query) {
                $query->whereNull('archived_at')
                    ->whereNotNull('current_batch_id')
                    ->whereHas('currentBatch', function ($batchQuery) {
                        $batchQuery->where('status', 'Running');
                    });
            })
            ->orderBy('generated_at', 'desc')
            ->get()
            ->filter(fn (DecisionSupportLog $recommendation) => $this->snapshotHasSensorReadings($recommendation->data_snapshot))
            ->values();
    }

    public function currentCriticalSensorFindings(): array
    {
        $criticalFindings = [];

        $houses = House::whereNull('archived_at')->get();

        foreach ($houses as $house) {
            $pens = $house->pens()
                ->whereNull('archived_at')
                ->whereNotNull('current_batch_id')
                ->whereHas('currentBatch', function ($query) {
                    $query->where('status', 'Running');
                })
                ->orderBy('pen_name')
                ->orderBy('id')
                ->get();

            foreach ($pens as $pen) {
                $dataSnapshot = $this->gatherHouseData($house, $pen);

                if (! $this->snapshotHasSensorReadings($dataSnapshot)) {
                    continue;
                }

                $ruleDecisions = $this->evaluateRules($dataSnapshot);

                foreach ($ruleDecisions as $decision) {
                    if (($decision['severity'] ?? null) !== 'critical') {
                        continue;
                    }

                    $criticalFindings[] = [
                        'house_id' => $house->id,
                        'house_name' => $house->house_number ?? "House {$house->id}",
                        'pen_id' => $pen->id,
                        'pen_name' => $pen->pen_name ?? "Pen {$pen->id}",
                        'category' => $decision['category'] ?? 'overall',
                        'rule' => $decision['rule'] ?? '',
                        'finding' => $decision['finding'] ?? '',
                        'evidence' => $decision['evidence'] ?? '',
                    ];
                }
            }
        }

        return $criticalFindings;
    }
}

