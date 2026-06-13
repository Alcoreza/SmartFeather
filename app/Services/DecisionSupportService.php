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
                $pens = $house->pens()
                    ->whereNull('archived_at')
                    ->orderBy('pen_name')
                    ->orderBy('id')
                    ->get();

                if ($pens->isEmpty()) {
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

                    // If a valid recommendation exists and is not expired, skip.
                    if ($existingRec && $existingRec->expires_at > now()) {
                        continue;
                    }

                    // Gather current data for this pen.
                    $dataSnapshot = $this->gatherHouseData($house, $pen);

                    // Apply predefined rules before AI summarization.
                    $dataSnapshot['rule_decisions'] = $this->evaluateRules($dataSnapshot);

                    // Generate prompt from rule decisions, not from raw data alone.
                    $prompt = $this->buildPrompt($house, $dataSnapshot);

                    // Use AI only to summarize system-generated decisions.
                    $recommendation = $this->getAIRecommendation($prompt, $dataSnapshot['rule_decisions']);
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
            ->where('house_houseid', $house->id);

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
                    'sensor_name' => $sensor->sensorname,
                    'sensor_type' => $sensor->sensortype,
                    'pen_id' => $sensor->pen_penid,
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

        $inventory = $dataSnapshot['inventory'];

        if ((int) $inventory['feed_items_count'] === 0) {
            $decisions[] = [
                'category' => 'feed',
                'severity' => 'warning',
                'rule' => 'feed_inventory_missing',
                'finding' => 'No feed inventory record is available for decision support.',
                'evidence' => 'Feed items count: 0.',
                'action' => 'Add or update feed inventory so stock-based recommendations can be generated.',
            ];
        } elseif ((float) $inventory['feed_level'] <= (float) $inventory['feed_critical_level']) {
            $decisions[] = [
                'category' => 'feed',
                'severity' => 'critical',
                'rule' => 'feed_at_or_below_critical_level',
                'finding' => 'Feed stock is at or below the predefined critical level.',
                'evidence' => "Current feed stock: {$inventory['feed_level']}; critical level: {$inventory['feed_critical_level']}.",
                'action' => 'Schedule feed replenishment and verify feeding plans for the active flock.',
            ];
        }

        if ((int) $inventory['water_items_count'] === 0) {
            $decisions[] = [
                'category' => 'water',
                'severity' => 'warning',
                'rule' => 'water_inventory_missing',
                'finding' => 'No water inventory record is available for decision support.',
                'evidence' => 'Water items count: 0.',
                'action' => 'Add or update water inventory or water-supply tracking for decision support.',
            ];
        } elseif ((float) $inventory['water_level'] <= (float) $inventory['water_critical_level']) {
            $decisions[] = [
                'category' => 'water',
                'severity' => 'critical',
                'rule' => 'water_at_or_below_critical_level',
                'finding' => 'Water stock is at or below the predefined critical level.',
                'evidence' => "Current water stock: {$inventory['water_level']}; critical level: {$inventory['water_critical_level']}.",
                'action' => 'Check water supply, refill storage, and inspect drinker lines for blockage or leaks.',
            ];
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
        $pen = $dataSnapshot['pen'] ?? null;
        $penName = $pen['name'] ?? 'Unassigned Pen';

        $prompt = "You are an expert AI Decision Support System for commercial broiler poultry farms operating in tropical climates like the Philippines. Analyze the supplied, already-calculated flock, temperature, and ammonia facts and produce immediate operational guidance for the farm manager.\n\n";
        $prompt .= "**Farm Context:**\n";
        $prompt .= "- House: {$dataSnapshot['house_name']}\n";
        $prompt .= "- Pen: {$penName}\n";
        $prompt .= "- Current Date: {$flock['current_date']}\n";
        $prompt .= "- Placement Date: " . ($flock['placement_date'] ?? 'No active placement date') . "\n";
        $prompt .= "- Flock Age: " . ($flock['age_days'] ?? 'Unknown') . " Days Old (" . ($flock['week_label'] ?? 'Unknown Week') . ")\n";
        $prompt .= "- Current Temperature: " . ($dataSnapshot['environment']['temperature_celsius'] ?? 'Unknown') . "°C\n";
        $prompt .= "- Thermal Condition: " . ($thermal['condition'] ?? 'Unknown') . "\n";
        $prompt .= "- Target Range: " . ($thermal['target_range'] ?? 'Unknown') . "\n";
        $prompt .= "- Current Ammonia Level: " . ($dataSnapshot['environment']['ammonia_ppm'] ?? 'Unknown') . " ppm\n";
        $prompt .= "- Ammonia Condition: " . ($ammonia['condition'] ?? 'Unknown') . "\n";
        $prompt .= "- House Type: tropical broiler house\n\n";

        $prompt .= "**Rule-Based Decisions:**\n";
        foreach ($dataSnapshot['rule_decisions'] as $decision) {
            $prompt .= "- Category: {$decision['category']}; Severity: {$decision['severity']}; Rule: {$decision['rule']}\n";
            $prompt .= "  Finding: {$decision['finding']}\n";
            $prompt .= "  Evidence: {$decision['evidence']}\n";
            $prompt .= "  Required action: {$decision['action']}\n";
        }

        $prompt .= "**Instructions:**\n";
        $prompt .= "1. Calculate nothing new; use only the supplied House, Pen, Current Date, Placement Date, Flock Age, Week, Current Temperature, Thermal Condition, Current Ammonia Level, and Ammonia Condition.\n";
        $prompt .= "2. If Thermal Condition is Heat Stress, Cold Stress, or Severe Heat Danger, or if Ammonia Condition is High Risk or Critical Danger, the first visible section must be **🚨 EMERGENCY ACTION REQUIRED** before any other text.\n";
        $prompt .= "3. Use this exact structure and headings:\n";
        $prompt .= "   1. **Flock Status Analysis:**\n";
        $prompt .= "   2. **🚨 EMERGENCY ACTION REQUIRED (Only show this if condition is STRESS, HIGH RISK, or DANGER):**\n";
        $prompt .= "   3. **Standard Management Actions (Next 1-2 Hours):**\n";
        $prompt .= "   4. **Critical Warning / Observation Note:**\n";
        $prompt .= "4. In Flock Status Analysis, always include Current Date, Placement Date, Flock Age, Current Temperature, and Thermal Condition.\n";
        $prompt .= "5. Add a separate ammonia section with Current Ammonia Level and Ammonia Condition, even when ammonia data is missing.\n";
        $prompt .= "6. Emergency actions must be 3-4 short, punchy, physical actions executable within 5 minutes. For ammonia High Risk or Critical Danger, focus on evacuating gas from the house.\n";
        $prompt .= "7. Standard management actions must be 3-4 hyper-specific, bolded operational steps for longer-term stabilization.\n";
        $prompt .= "8. Ammonia thresholds are: 0-10 ppm Safe, 11-19 ppm Moderate Risk, 20-24 ppm High Risk, 25+ ppm Critical Danger.\n";
        $prompt .= "9. Do not mention OpenAI, prompts, or uncertainty. Do not invent unavailable data.\n";

        return $prompt;
    }

    /**
     * Get AI recommendation from OpenAI
     */
    protected function getAIRecommendation($prompt, array $ruleDecisions)
    {
        try {
            $response = $this->getClient()->chat()->create([
                'model' => config('services.openai.model') ?? 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You generate broiler poultry decision support from supplied rule calculations. Preserve the requested section order exactly and prioritize emergency mortality-prevention actions when thermal stress, ammonia high risk, or ammonia danger is present.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.3,
                'max_tokens' => 500,
            ]);

            return $response->choices[0]->message->content;
        } catch (\Exception $e) {
            Log::error('OpenAI API Error: ' . $e->getMessage());
            return $this->formatRuleRecommendations($ruleDecisions);
        }
    }

    protected function criticalLevelFor($items, float $fallback): float
    {
        $configuredCritical = $items
            ->filter(fn ($item) => is_numeric($item->critical ?? null) && (float) $item->critical > 0)
            ->sum('critical');

        return $configuredCritical > 0 ? (float) $configuredCritical : $fallback;
    }

    protected function formatRuleRecommendations(array $ruleDecisions): string
    {
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

    protected function ensureFlockEnvironmentStatusIsVisible(string $recommendation, array $dataSnapshot): string
    {
        $requiredLines = [
            'Current Date:',
            'Placement Date:',
            'Flock Age:',
            'Current Temperature:',
            'Thermal Condition:',
            'Current Ammonia Level:',
            'Ammonia Condition:',
        ];
        $hasAllStatusLines = collect($requiredLines)
            ->every(fn ($line) => str_contains($recommendation, $line));

        if ($hasAllStatusLines) {
            return $recommendation;
        }

        return $this->formatFlockEnvironmentStatusBlock($dataSnapshot) . "\n" . ltrim($recommendation);
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
        $temperatureText = is_numeric($temperature) ? "{$temperature}°C" : 'No temperature reading';
        $ammoniaText = is_numeric($ammonia) ? "{$ammonia} ppm" : 'No ammonia reading';

        return implode("\n", [
            '1. **Flock Status Analysis:**',
            '   - Current Date: ' . ($flock['current_date'] ?? now()->toDateString()),
            '   - Placement Date: ' . ($flock['placement_date'] ?? 'No active placement date'),
            "   - Flock Age: {$age} Days Old ({$week})",
            "   - Current Temperature: {$temperatureText}",
            "   - Thermal Condition: {$thermalCondition}",
            '',
            '2. **Ammonia Status Analysis:**',
            "   - Current Ammonia Level: {$ammoniaText}",
            "   - Ammonia Condition: {$ammoniaCondition}",
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
        $lines[] = '   - Current Temperature: ' . (is_numeric($temperature) ? "{$temperature}°C" : 'No temperature reading');
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
        $lines[] = '   - Current Ammonia Level: ' . (is_numeric($ammonia) ? "{$ammonia} ppm" : 'No ammonia reading');
        $lines[] = "   - Ammonia Condition: {$condition}";
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

        return !isset($snapshot['flock'], $snapshot['environment'], $snapshot['thermal_status'], $snapshot['ammonia_status'], $snapshot['pen']);
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
                $query->whereNull('archived_at');
            })
            ->orderBy('generated_at', 'desc')
            ->get();
    }
}

