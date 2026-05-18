<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MobileDashboardController extends Controller
{
    public function show(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|integer|exists:user,EmployeeId',
        ]);

        $latestPopulationDate = DB::table('population_record')
            ->selectRaw('DATE(recorded_at) as record_date')
            ->orderByRaw('DATE(recorded_at) desc')
            ->value('record_date');

        $dailyEggs = 0;
        $dailyMortalities = 0;
        $overviewDateLabel = Carbon::now()->format('n/j/y');

        if (!empty($latestPopulationDate)) {
            $dailyTotals = DB::table('population_record')
                ->selectRaw('COALESCE(SUM(eggs_hatched), 0) as total_eggs')
                ->selectRaw('COALESCE(SUM(mortality), 0) as total_mortalities')
                ->whereDate('recorded_at', $latestPopulationDate)
                ->first();

            $dailyEggs = (int) ($dailyTotals->total_eggs ?? 0);
            $dailyMortalities = (int) ($dailyTotals->total_mortalities ?? 0);
            $overviewDateLabel = Carbon::parse($latestPopulationDate)->format('n/j/y');
        }

        $totalBirds = (int) DB::table('pen')
            ->whereNotNull('current_batch_id')
            ->sum('population');

        $pendingTasks = (int) DB::table('tasks')
            ->where('user_employeeid', $validated['employee_id'])
            ->where('status', 'Pending')
            ->count();

        return response()->json([
            'success' => true,
            'welcome_text' => 'Welcome!',
            'overview_date_label' => "for {$overviewDateLabel}",
            'stats' => [
                [
                    'title' => 'Total Birds',
                    'value' => (string) $totalBirds,
                    'icon_key' => 'birds',
                    'bg_color' => '#FDFDFD',
                    'icon_bg' => '#D92C2C',
                ],
                [
                    'title' => 'Total Eggs',
                    'value' => (string) $dailyEggs,
                    'icon_key' => 'eggs',
                    'bg_color' => '#FDFDFD',
                    'icon_bg' => '#FFA96E',
                ],
                [
                    'title' => 'Mortalities',
                    'value' => (string) $dailyMortalities,
                    'icon_key' => 'mortalities',
                    'bg_color' => '#FDFDFD',
                    'icon_bg' => '#808080',
                ],
            ],
            'gauges' => [
                [
                    'label' => 'Temperature',
                    'value' => 11,
                    'unit' => 'deg',
                    'min' => 0,
                    'max' => 40,
                    'color' => '#6ABF4B',
                ],
                [
                    'label' => 'Ammonia',
                    'value' => 15,
                    'unit' => 'ppm',
                    'min' => 0,
                    'max' => 40,
                    'color' => '#F4B43A',
                ],
            ],
            'resources' => [
                [
                    'label' => 'Feed',
                    'value' => 90,
                    'unit' => '%',
                    'max' => 100,
                    'color' => '#C88A3D',
                ],
                [
                    'label' => 'Water',
                    'value' => 40,
                    'unit' => '%',
                    'max' => 100,
                    'color' => '#6CDDE5',
                ],
            ],
            'pending_tasks' => (string) $pendingTasks,
            'quick_access' => [
                [
                    'title' => 'Population',
                    'icon_key' => 'population',
                    'tint' => '#D92C2C',
                    'action_key' => 'population',
                ],
                [
                    'title' => 'Feeds Refill',
                    'icon_key' => 'feeds',
                    'tint' => '#CC8A2D',
                    'action_key' => 'feeds_refill',
                ],
                [
                    'title' => 'Biosecurity',
                    'icon_key' => 'biosecurity',
                    'tint' => '#2F8F45',
                    'action_key' => 'biosecurity',
                ],
            ],
        ]);
    }
}
