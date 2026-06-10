<?php

namespace App\Http\Controllers;

use App\Services\DailyFarmOverviewService;
use Illuminate\View\View;

class ManagerDashboardController extends Controller
{
    public function index(): View
    {
        try {
            $dailyFarmOverviewService = new DailyFarmOverviewService();
            $dailyOverview = $dailyFarmOverviewService->getDailyOverview();
        } catch (\Exception $e) {
            // Log error and use default values
            \Log::error('Error in ManagerDashboardController: ' . $e->getMessage());
            $dailyOverview = [
                'totalChickens' => 0,
                'totalEggs' => 0,
                'totalMortalities' => 0,
            ];
        }

        $overviewCards = [
            ['icon' => '🐔', 'value' => $dailyOverview['totalChickens'], 'label' => 'Total Chickens', 'accent' => 'red'],
            ['icon' => '🥚', 'value' => $dailyOverview['totalEggs'], 'label' => 'Total Eggs', 'accent' => 'orange'],
            ['icon' => '📉', 'value' => $dailyOverview['totalMortalities'], 'label' => 'Mortalities', 'accent' => 'gray'],
        ];

        $monitoringGraph = [
            'label' => 'Temperature',
            'values' => [22, 24, 23, 27, 28, 26, 29],
            'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
        ];

        return view('manager.dashboard', compact('overviewCards', 'monitoringGraph'));
    }
}
