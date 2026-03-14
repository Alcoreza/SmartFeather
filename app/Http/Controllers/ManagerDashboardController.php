<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class ManagerDashboardController extends Controller
{
    public function index(): View
    {
        $overviewCards = [
            ['icon' => '🐔', 'value' => 5462, 'label' => 'Total Birds', 'accent' => 'red'],
            ['icon' => '🥚', 'value' => 367, 'label' => 'Total Eggs', 'accent' => 'orange'],
            ['icon' => '📉', 'value' => 25, 'label' => 'Mortalities', 'accent' => 'gray'],
        ];

        $monitoringGraph = [
            'label' => 'Temperature',
            'values' => [22, 24, 23, 27, 28, 26, 29],
            'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
        ];

        return view('manager.dashboard', compact('overviewCards', 'monitoringGraph'));
    }
}
