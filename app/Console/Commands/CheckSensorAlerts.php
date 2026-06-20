<?php

namespace App\Console\Commands;

use App\Services\SensorAlertService;
use Illuminate\Console\Command;

class CheckSensorAlerts extends Command
{
    protected $signature = 'sensor-alerts:check';

    protected $description = 'Check latest sensor readings and send push notifications for threshold alerts.';

    public function handle(SensorAlertService $sensorAlertService): int
    {
        $result = $sensorAlertService->checkAndSendAlerts();

        $this->info(
            'Sensor alerts checked: ' .
            'checked=' . ($result['checked'] ?? 0) .
            ', sent=' . ($result['sent'] ?? 0) .
            ', skipped=' . ($result['skipped'] ?? 0) .
            ', failed=' . ($result['failed'] ?? 0)
        );

        return self::SUCCESS;
    }
}