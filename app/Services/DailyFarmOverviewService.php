<?php

namespace App\Services;

use App\Models\Pen;
use App\Models\PopulationRecord;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

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
                ->whereHas('currentBatch', function ($query) {
                    $query->where('status', 'Running');
                })
                ->whereHas('house', function ($query) {
                    $query->whereNull('archived_at');
                })
                ->sum('population');
            
            return (int) ($total ?? 0);
        } catch (\Exception $e) {
            \Log::error('Error getting total chickens: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get today's total eggs hatched and mortalities
     * 
     * @param Carbon $today
     * @return array
     */
    private function getTodaysEggsAndMortalities(Carbon $today): array
    {
        try {
            // Get records for today
            $todaysRecords = $this->activePopulationRecordsQuery()
                ->whereBetween('recorded_at', [
                    $today->copy()->startOfDay(),
                    $today->copy()->endOfDay(),
                ])
                ->get();

            $totalEggs = (int) ($todaysRecords->sum('eggs_hatched') ?? 0);
            $totalMortalities = (int) ($todaysRecords->sum('mortality') ?? 0);

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

    private function activePopulationRecordsQuery(): Builder
    {
        return PopulationRecord::query()
            ->whereHas('pen', function ($query) {
                $query->whereNull('archived_at')
                    ->whereNotNull('current_batch_id')
                    ->whereHas('currentBatch', function ($batchQuery) {
                        $batchQuery->where('status', 'Running');
                    })
                    ->whereHas('house', function ($houseQuery) {
                        $houseQuery->whereNull('archived_at');
                    });
            });
    }
}
