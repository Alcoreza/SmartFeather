<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SensorAlertService
{
    private int $cooldownMinutes = 15;

    public function __construct(
        private FirebaseCloudMessagingService $firebase
    ) {
    }

    public function checkAndSendAlerts(): array
    {
        $latestReadings = DB::table('sensor_readings as sr')
            ->joinSub(
                DB::table('sensor_readings')
                    ->selectRaw('sensorid, max(reading_id) as latest_reading_id')
                    ->groupBy('sensorid'),
                'latest',
                function ($join) {
                    $join->on('sr.sensorid', '=', 'latest.sensorid')
                        ->on('sr.reading_id', '=', 'latest.latest_reading_id');
                }
            )
            ->join('sensors as s', 'sr.sensorid', '=', 's.sensorid')
            ->leftJoin('sensorconfigurations as sc', 's.sensorid', '=', 'sc.sensors_sensorid')
            ->leftJoin('house as h', 's.house_houseid', '=', 'h.id')
            ->leftJoin('pen as p', 's.pen_penid', '=', 'p.id')
            ->where('s.status', 'Active')
            ->whereIn('s.sensortype', [
                'Temperature Sensor',
                'Ammonia Sensor',
                'Feed Sensor',
                'Water Sensor',
            ])
            ->select($this->readingSelectColumns())
            ->get();

        $checked = 0;
        $sent = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($latestReadings as $reading) {
            $result = $this->processReading($reading);
            $checked += $result['checked'];
            $sent += $result['sent'];
            $skipped += $result['skipped'];
            $failed += $result['failed'];
        }

        return [
            'checked' => $checked,
            'sent' => $sent,
            'skipped' => $skipped,
            'failed' => $failed,
        ];
    }

    public function processReadingId(int $readingId): array
    {
        $reading = DB::table('sensor_readings as sr')
            ->join('sensors as s', 'sr.sensorid', '=', 's.sensorid')
            ->leftJoin('sensorconfigurations as sc', 's.sensorid', '=', 'sc.sensors_sensorid')
            ->leftJoin('house as h', 's.house_houseid', '=', 'h.id')
            ->leftJoin('pen as p', 's.pen_penid', '=', 'p.id')
            ->where('sr.reading_id', $readingId)
            ->where('s.status', 'Active')
            ->whereIn('s.sensortype', [
                'Temperature Sensor',
                'Ammonia Sensor',
                'Feed Sensor',
                'Water Sensor',
            ])
            ->select($this->readingSelectColumns())
            ->first();

        if (!$reading) {
            return [
                'checked' => 0,
                'sent' => 0,
                'skipped' => 1,
                'failed' => 0,
                'reason' => 'Reading not found, inactive sensor, or unsupported sensor type.',
            ];
        }

        return $this->processReading($reading);
    }

    private function processReading($reading): array
    {
        $alert = $this->resolveAlert($reading);

        if (!$alert) {
            return [
                'checked' => 1,
                'sent' => 0,
                'skipped' => 1,
                'failed' => 0,
                'reason' => 'Reading is within threshold.',
            ];
        }

        if ($this->recentAlertExists((int) $reading->sensorid, $alert['alert_type'])) {
            return [
                'checked' => 1,
                'sent' => 0,
                'skipped' => 1,
                'failed' => 0,
                'reason' => 'Cooldown active.',
            ];
        }

        $tokens = $this->flockmanTokens();

        if ($tokens->isEmpty()) {
            Log::warning('No active FCM tokens found for sensor alert.');

            return [
                'checked' => 1,
                'sent' => 0,
                'skipped' => 1,
                'failed' => 0,
                'reason' => 'No active flockman tokens.',
            ];
        }

        $sent = 0;
        $failed = 0;

        foreach ($tokens as $tokenRow) {
            $success = $this->firebase->sendToToken(
                token: $tokenRow->fcm_token,
                title: $alert['title'],
                body: $alert['message'],
                data: [
                    'type' => 'sensor_alert',
                    'sensor_id' => (string) $reading->sensorid,
                    'sensor_type' => (string) $reading->sensortype,
                    'reading_id' => (string) $reading->reading_id,
                    'alert_type' => $alert['alert_type'],
                    'house_id' => (string) ($reading->house_houseid ?? ''),
                    'pen_id' => (string) ($reading->pen_penid ?? ''),
                ]
            );

            if ($success) {
                $this->logAlert($reading, $alert, (int) $tokenRow->employee_id);
                $sent++;
            } else {
                $failed++;
            }
        }

        return [
            'checked' => 1,
            'sent' => $sent,
            'skipped' => 0,
            'failed' => $failed,
            'reason' => 'Alert processed.',
        ];
    }

    private function readingSelectColumns(): array
    {
        return [
            'sr.reading_id',
            'sr.sensorid',
            'sr.value',
            'sr.recorded_at',
            's.sensorname',
            's.sensortype',
            's.house_houseid',
            's.pen_penid',
            'sc.lowestthreshold',
            'sc.highestthreshold',
            'h.house_number',
            'p.pen_name',
        ];
    }

    private function resolveAlert($reading): ?array
    {
        $value = (float) $reading->value;
        $lowest = $reading->lowestthreshold;
        $highest = $reading->highestthreshold;

        if ($lowest !== null && $value < (float) $lowest) {
            return $this->buildAlert($reading, 'below_threshold', (float) $lowest);
        }

        if ($highest !== null && $value > (float) $highest) {
            return $this->buildAlert($reading, 'above_threshold', (float) $highest);
        }

        return null;
    }

    private function buildAlert($reading, string $alertType, float $thresholdValue): array
    {
        $sensorLabel = $this->sensorLabel((string) $reading->sensortype);
        $directionLabel = $alertType === 'above_threshold' ? 'High' : 'Low';
        $valueWithUnit = $this->formatValue((string) $reading->sensortype, $reading->value);
        $location = $this->formatLocation($reading->house_number, $reading->pen_name);

        return [
            'alert_type' => $alertType,
            'threshold_value' => $thresholdValue,
            'title' => "{$directionLabel} {$sensorLabel} Alert",
            'message' => "{$sensorLabel} is {$valueWithUnit} at {$location}.",
        ];
    }

    private function flockmanTokens()
    {
        return DB::table('mobile_device_tokens as mdt')
            ->join('user as u', 'mdt.employee_id', '=', 'u.EmployeeId')
            ->where('u.Role', 'Flockman')
            ->where('mdt.is_active', DB::raw('true'))
            ->whereNotNull('mdt.fcm_token')
            ->where('mdt.fcm_token', '!=', '')
            ->where('mdt.fcm_token', '!=', 'test-token-123')
            ->get([
                'mdt.employee_id',
                'mdt.fcm_token',
            ]);
    }

    private function recentAlertExists(int $sensorId, string $alertType): bool
    {
        return DB::table('sensor_alert_logs')
            ->where('sensor_id', $sensorId)
            ->where('alert_type', $alertType)
            ->where('created_at', '>=', Carbon::now()->subMinutes($this->cooldownMinutes))
            ->exists();
    }

    private function logAlert($reading, array $alert, int $employeeId): void
    {
        DB::table('sensor_alert_logs')->insert([
            'sensor_id' => $reading->sensorid,
            'reading_id' => $reading->reading_id,
            'alert_type' => $alert['alert_type'],
            'sensor_type' => $reading->sensortype,
            'value' => $reading->value,
            'threshold_value' => $alert['threshold_value'],
            'house_id' => $reading->house_houseid,
            'pen_id' => $reading->pen_penid,
            'title' => $alert['title'],
            'message' => $alert['message'],
            'sent_to_employee_id' => $employeeId,
            'sent_at' => now(),
            'created_at' => now(),
        ]);
    }

    private function sensorLabel(string $sensorType): string
    {
        return match ($sensorType) {
            'Temperature Sensor' => 'Temperature',
            'Ammonia Sensor' => 'Ammonia',
            'Feed Sensor' => 'Feed Level',
            'Water Sensor' => 'Water Level',
            default => str_replace(' Sensor', '', $sensorType),
        };
    }

    private function formatValue(string $sensorType, $value): string
    {
        return match ($sensorType) {
            'Temperature Sensor' => number_format((float) $value, 1) . ' C',
            'Ammonia Sensor' => number_format((float) $value, 1) . ' ppm',
            'Feed Sensor' => number_format((float) $value, 1) . ' mm',
            'Water Sensor' => number_format((float) $value, 1) . ' level',
            default => (string) $value,
        };
    }

    private function formatLocation($houseNumber, $penName): string
    {
        $house = $houseNumber ?: 'Unknown House';
        $pen = $penName ?: 'Unknown Pen';

        return "{$house}, {$pen}";
    }
}