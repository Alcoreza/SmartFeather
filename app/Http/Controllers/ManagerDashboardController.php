<?php

namespace App\Http\Controllers;

use App\Services\DailyFarmOverviewService;
use Illuminate\View\View;

class ManagerDashboardController extends Controller
{
    public function index(): View
    {
        return view('manager.dashboard', [
            'overviewCards' => $this->overviewCards(),
            'monitoringGraph' => $this->monitoringGraph(),
        ]);
    }

    public function adminIndex(): View
    {
        return view('admin.dashboard', [
            'overviewCards' => $this->overviewCards(),
            'monitoringGraph' => $this->monitoringGraph(),
        ]);
    }

    private function overviewCards(): array
    {
        try {
            $dailyFarmOverviewService = new DailyFarmOverviewService();
            $dailyOverview = $dailyFarmOverviewService->getDailyOverview();
        } catch (\Exception $e) {
            \Log::error('Error in ManagerDashboardController: ' . $e->getMessage());
            $dailyOverview = [
                'totalChickens' => 0,
                'totalEggs' => 0,
                'totalMortalities' => 0,
            ];
        }

        return [
            ['value' => $dailyOverview['totalChickens'], 'label' => 'Total Chickens', 'accent' => 'red'],
            ['value' => $dailyOverview['totalEggs'], 'label' => 'Total Eggs', 'accent' => 'orange'],
            ['value' => $dailyOverview['totalMortalities'], 'label' => 'Mortalities', 'accent' => 'gray'],
        ];
    }

    private function monitoringGraph(): array
    {
        return [
            'label' => 'Temperature',
            'values' => [22, 24, 23, 27, 28, 26, 29],
            'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
        ];
    }
}
