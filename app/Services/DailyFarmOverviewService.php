<?php

namespace App\Services;

use App\Models\Pen;
use App\Models\PopulationRecord;
use Carbon\Carbon;

class DailyFarmOverviewService
{
    /**
     * Get daily farm overview statistics
     * 
     * @return array
     */
    public function getDailyOverview(): array
    {
        try {
            $today = Carbon::today();
            
            // Get total chickens from pens with running batches
            $totalChickens = $this->getTotalChickensWithRunningBatch();
            
            // Get today's eggs and mortalities from population records
            $eggsAndMortalities = $this->getTodaysEggsAndMortalities($today);
            
            return [
                'totalChickens' => (int) $totalChickens,
                'totalEggs' => (int) $eggsAndMortalities['totalEggs'],
                'totalMortalities' => (int) $eggsAndMortalities['totalMortalities'],
            ];
        } catch (\Exception $e) {
            // Log the error and return default values
            \Log::error('Error getting daily farm overview: ' . $e->getMessage());
            
            return [
                'totalChickens' => 0,
                'totalEggs' => 0,
                'totalMortalities' => 0,
            ];
        }
    }

    /**
     * Get total chickens from all pens with running batches
     * 
     * @return int
     */
    private function getTotalChickensWithRunningBatch(): int
    {
        try {
            $total = Pen::whereNotNull('current_batch_id')
                ->whereNull('archived_at')
                ->sum('population');
            
            return (int) ($total ?? 0);
        } catch (\Exception $e) {
            \Log::error('Error getting total chickens: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get today's total eggs hatched and mortalities
     * If no records for today, get the most recent records
     * 
     * @param Carbon $today
     * @return array
     */
    private function getTodaysEggsAndMortalities(Carbon $today): array
    {
        try {
            // Get records for today
            $todaysRecords = PopulationRecord::whereBetween('recorded_at', [
                $today->startOfDay(),
                $today->endOfDay(),
            ])->get();

            $totalEggs = (int) ($todaysRecords->sum('eggs_hatched') ?? 0);
            $totalMortalities = (int) ($todaysRecords->sum('mortality') ?? 0);

            // If no records for today, get the most recent records
            if ($todaysRecords->isEmpty()) {
                $latestRecords = PopulationRecord::orderBy('recorded_at', 'desc')
                    ->first();

                if ($latestRecords) {
                    $totalEggs = (int) ($latestRecords->eggs_hatched ?? 0);
                    $totalMortalities = (int) ($latestRecords->mortality ?? 0);
                }
            }

            return [
                'totalEggs' => $totalEggs,
                'totalMortalities' => $totalMortalities,
            ];
        } catch (\Exception $e) {
            \Log::error('Error getting eggs and mortalities: ' . $e->getMessage());
            
            return [
                'totalEggs' => 0,
                'totalMortalities' => 0,
            ];
        }
    }
}
