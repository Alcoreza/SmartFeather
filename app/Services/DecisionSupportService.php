<?php

namespace App\Services;

use App\Models\DecisionSupportLog;
use App\Models\House;
use App\Models\SensorReading;
use App\Models\Sensor;
use App\Models\Inventory;
use Illuminate\Support\Facades\Log;

class DecisionSupportService
{
    private const DEFAULT_FEED_CRITICAL_LEVEL = 25;
    private const DEFAULT_WATER_CRITICAL_LEVEL = 25;
    private const LEGACY_FAILURE_RECOMMENDATION = 'Unable to generate recommendations at this time. Please check system logs.';

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
    public function generateRecommendations($houseId = null)
    {
        try {
            // Get houses to analyze
            $houses = $houseId 
                ? House::where('id', $houseId)->get() 
                : House::all();

            if ($houses->isEmpty()) {
                Log::warning('No houses found for decision support generation');
                return true;
            }

            foreach ($houses as $house) {
                // Check if we already have a recent active recommendation
                $existingRec = DecisionSupportLog::forHouse($house->id)
                    ->active()
                    ->orderBy('generated_at', 'desc')
                    ->first();

                if ($existingRec && $this->isLegacyFailureRecommendation($existingRec->recommendation_text)) {
                    $existingRec->update(['status' => 'archived']);
                    $existingRec = null;
                }

                // If a valid recommendation exists and is not expired, skip
                if ($existingRec && $existingRec->expires_at > now()) {
                    continue;
                }

                // Gather current data
                $dataSnapshot = $this->gatherHouseData($house);

                // Apply predefined rules before AI summarization.
                $dataSnapshot['rule_decisions'] = $this->evaluateRules($dataSnapshot);

                // Generate prompt from rule decisions, not from raw data alone.
                $prompt = $this->buildPrompt($house, $dataSnapshot);

                // Use AI only to summarize system-generated decisions.
                $recommendation = $this->getAIRecommendation($prompt, $dataSnapshot['rule_decisions']);

                // Store recommendation
                DecisionSupportLog::create([
                    'house_id' => $house->id,
                    'recommendation_text' => $recommendation,
                    'data_snapshot' => $dataSnapshot,
                    'status' => 'active',
                    'generated_at' => now(),
                    'expires_at' => now()->addHours(1), // Valid for 1 hour
                ]);

                Log::info("Decision support generated for house {$house->id}");
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
    protected function gatherHouseData($house)
    {
        // Get all sensors for this house with their configured thresholds.
        $sensors = Sensor::with('configuration')
            ->where('house_houseid', $house->id)
            ->get();
        
        $sensorData = [];
        foreach ($sensors as $sensor) {
            $reading = SensorReading::where('sensorid', $sensor->sensorid)
                ->orderBy('recorded_at', 'desc')
                ->first();
            
            if ($reading) {
                $sensorData[] = [
                    'sensor_name' => $sensor->sensorname,
                    'sensor_type' => $sensor->sensortype,
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
            'timestamp' => now()->toIso8601String(),
            'sensors' => $sensorData,
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
    protected function evaluateRules(array $dataSnapshot): array
    {
        $decisions = [];

        foreach ($dataSnapshot['sensors'] as $sensor) {
            $value = (float) $sensor['value'];
            $lowest = is_numeric($sensor['lowest_threshold']) ? (float) $sensor['lowest_threshold'] : null;
            $highest = is_numeric($sensor['highest_threshold']) ? (float) $sensor['highest_threshold'] : null;
            $label = "{$sensor['sensor_name']} ({$sensor['sensor_type']})";

            if ($lowest !== null && $value < $lowest) {
                $decisions[] = [
                    'category' => 'environment',
                    'severity' => 'warning',
                    'rule' => 'sensor_below_minimum_threshold',
                    'finding' => "{$label} is below the configured minimum threshold.",
                    'evidence' => "Current value: {$value}; minimum threshold: {$lowest}.",
                    'action' => "Inspect {$sensor['sensor_type']} conditions in {$dataSnapshot['house_name']} and correct the source of the low reading.",
                ];
                continue;
            }

            if ($highest !== null && $value > $highest) {
                $decisions[] = [
                    'category' => 'environment',
                    'severity' => 'critical',
                    'rule' => 'sensor_above_maximum_threshold',
                    'finding' => "{$label} is above the configured maximum threshold.",
                    'evidence' => "Current value: {$value}; maximum threshold: {$highest}.",
                    'action' => "Check ventilation, cooling, litter condition, and related equipment for {$dataSnapshot['house_name']} immediately.",
                ];
            }
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
                'rule' => 'all_monitored_values_within_predefined_limits',
                'finding' => 'Environmental readings, feed stock, and water stock are within predefined limits.',
                'evidence' => 'No rule violations were detected during this decision-support run.',
                'action' => 'Continue routine monitoring and maintain the current farm management schedule.',
            ];
        }

        return $decisions;
    }

    /**
     * Build the prompt for AI summarization based on system rule decisions.
     */
    protected function buildPrompt($house, $dataSnapshot)
    {
        $prompt = "Summarize the system-generated poultry farm decision-support findings into 2-3 concise, actionable insights for a farm manager.\n\n";
        $prompt .= "**Farm Context:**\n";
        $prompt .= "- House: {$dataSnapshot['house_name']}\n";
        $prompt .= "- Timestamp: {$dataSnapshot['timestamp']}\n\n";

        $prompt .= "**Rule-Based Decisions:**\n";
        foreach ($dataSnapshot['rule_decisions'] as $decision) {
            $prompt .= "- Category: {$decision['category']}; Severity: {$decision['severity']}; Rule: {$decision['rule']}\n";
            $prompt .= "  Finding: {$decision['finding']}\n";
            $prompt .= "  Evidence: {$decision['evidence']}\n";
            $prompt .= "  Required action: {$decision['action']}\n";
        }

        $prompt .= "**Instructions:**\n";
        $prompt .= "1. Do not invent new findings or override the rule-based decisions.\n";
        $prompt .= "2. Translate the findings into manager-friendly action steps.\n";
        $prompt .= "3. Prioritize critical items before warnings or normal findings.\n";
        $prompt .= "4. Start directly with the recommendations without preamble.\n";

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
                        'content' => 'You summarize rule-based poultry farm decisions into clear action steps. Do not create recommendations that are not supported by the supplied rules.',
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
        return collect($ruleDecisions)
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

    protected function isLegacyFailureRecommendation(?string $recommendation): bool
    {
        return trim((string) $recommendation) === self::LEGACY_FAILURE_RECOMMENDATION;
    }

    /**
     * Get latest active recommendation for a house
     */
    public function getLatestRecommendation($houseId)
    {
        return DecisionSupportLog::forHouse($houseId)
            ->active()
            ->orderBy('generated_at', 'desc')
            ->first();
    }

    /**
     * Get active recommendations for all houses
     */
    public function getAllActiveRecommendations()
    {
        return DecisionSupportLog::active()
            ->orderBy('generated_at', 'desc')
            ->get();
    }
}

