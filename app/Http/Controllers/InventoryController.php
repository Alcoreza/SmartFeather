<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function managerIndex()
    {
        $inventoryItems = [
            [
                'id' => 1,
                'category' => 'feed',
                'item_name' => 'Feed Stock',
                'initial_stock' => 50,
                'remaining_stock' => 42,
                'unit' => 'kg',
                'purchase_date' => '2026-01-25',
            ],
            [
                'id' => 2,
                'category' => 'vitamin',
                'item_name' => 'Vitamin E',
                'initial_stock' => 5000,
                'remaining_stock' => 2500,
                'unit' => 'mL',
                'purchase_date' => '2026-01-25',
            ],
            [
                'id' => 3,
                'category' => 'vitamin',
                'item_name' => 'Vitamin D3',
                'initial_stock' => 5000,
                'remaining_stock' => 150,
                'unit' => 'mL',
                'purchase_date' => '2026-01-25',
            ],
            [
                'id' => 4,
                'category' => 'vitamin',
                'item_name' => 'Vitamin B-Complex',
                'initial_stock' => 5000,
                'remaining_stock' => 3700,
                'unit' => 'mL',
                'purchase_date' => '2026-01-25',
            ],
        ];

        foreach ($inventoryItems as &$item) {
            $item['percentage'] = $item['initial_stock'] > 0
                ? round(($item['remaining_stock'] / $item['initial_stock']) * 100)
                : 0;

            if ($item['percentage'] >= 70) {
                $item['status'] = 'High';
                $item['status_class'] = 'high';
            } elseif ($item['percentage'] >= 30) {
                $item['status'] = 'Moderate';
                $item['status_class'] = 'moderate';
            } else {
                $item['status'] = 'Critical';
                $item['status_class'] = 'critical';
            }
        }

        unset($item);

        $feedItems = array_values(array_filter($inventoryItems, fn ($item) => $item['category'] === 'feed'));
        $vitaminItems = array_values(array_filter($inventoryItems, fn ($item) => $item['category'] === 'vitamin'));

        return view('manager.inventory', compact('feedItems', 'vitaminItems'));
    }
}