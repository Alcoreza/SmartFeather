<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TaskOverdueAlertService
{
    private int $cooldownMinutes = 1440;

    public function __construct(
        private FirebaseCloudMessagingService $firebase
    ) {
    }

    public function checkAndSendAlerts(): array
    {
        $overdueTasks = DB::table('tasks as t')
            ->leftJoin('house as h', 't.house_houseid', '=', 'h.id')
            ->leftJoin('pen as p', function ($join) {
                $join->on('t.house_houseid', '=', 'p.house_id')
                    ->on('t.pennumber', '=', 'p.id');
            })
            ->where('t.status', 'Pending')
            ->whereNotNull('t.finishby')
            ->where('t.finishby', '<', now())
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('task_alert_logs as tal')
                    ->whereColumn('tal.task_id', 't.taskid');
            })
            ->select([
                't.taskid',
                't.tasktype',
                't.detailedtask',
                't.finishby',
                't.prioritylevel',
                't.user_employeeid',
                't.house_houseid',
                't.pennumber',
                'h.house_number',
                'p.pen_name',
            ])
            ->orderBy('t.finishby')
            ->get();

        $checked = 0;
        $sent = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($overdueTasks as $task) {
            $result = $this->processTask($task);
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

    private function processTask($task): array
    {
        $tokens = $this->workerTokens((int) $task->user_employeeid);

        if ($tokens->isEmpty()) {
            Log::warning('No active FCM tokens found for overdue task alert.', [
                'task_id' => $task->taskid,
                'employee_id' => $task->user_employeeid,
            ]);

            return [
                'checked' => 1,
                'sent' => 0,
                'skipped' => 1,
                'failed' => 0,
                'reason' => 'No active worker tokens.',
            ];
        }

        $alert = $this->buildAlert($task);
        $sent = 0;
        $failed = 0;

        foreach ($tokens as $tokenRow) {
            $success = $this->firebase->sendToToken(
                token: $tokenRow->fcm_token,
                title: $alert['title'],
                body: $alert['message'],
                data: [
                    'type' => 'task_overdue',
                    'task_id' => (string) $task->taskid,
                    'task_type' => (string) $task->tasktype,
                    'house_id' => (string) ($task->house_houseid ?? ''),
                    'pen_id' => (string) ($task->pennumber ?? ''),
                    'finishby' => (string) $task->finishby,
                    'priority' => (string) $task->prioritylevel,
                ],
                channelId: config('services.task_alerts.channel_id', 'task_alerts')
            );

            if ($success) {
                $sent++;
            } else {
                $failed++;
            }
        }

        if ($sent > 0) {
            $this->logAlert($task, $alert, (int) $task->user_employeeid);
        }

        return [
            'checked' => 1,
            'sent' => $sent,
            'skipped' => 0,
            'failed' => $failed,
            'reason' => 'Overdue task processed.',
        ];
    }

    private function buildAlert($task): array
    {
        $taskLabel = $this->taskLabel((string) $task->tasktype);
        $location = $this->formatLocation($task->house_number, $task->pen_name);
        $finishBy = Carbon::parse($task->finishby)->format('M j, g:i A');

        return [
            'title' => 'Task Overdue',
            'message' => "{$taskLabel} at {$location} was due by {$finishBy} and is still pending. Please review it when available.",
        ];
    }

    private function workerTokens(int $employeeId)
    {
        return DB::table('mobile_device_tokens as mdt')
            ->join('user as u', 'mdt.employee_id', '=', 'u.EmployeeId')
            ->where('u.EmployeeId', $employeeId)
            ->where('mdt.is_active', DB::raw('true'))
            ->whereNotNull('mdt.fcm_token')
            ->where('mdt.fcm_token', '!=', '')
            ->where('mdt.fcm_token', '!=', 'test-token-123')
            ->get([
                'mdt.employee_id',
                'mdt.fcm_token',
            ]);
    }

    private function logAlert($task, array $alert, int $employeeId): void
    {
        DB::table('task_alert_logs')->insert([
            'task_id' => $task->taskid,
            'employee_id' => $task->user_employeeid,
            'house_id' => $task->house_houseid,
            'pen_id' => $task->pennumber,
            'title' => $alert['title'],
            'message' => $alert['message'],
            'sent_to_employee_id' => $employeeId,
            'sent_at' => now(),
            'created_at' => now(),
        ]);
    }

    private function taskLabel(string $taskType): string
    {
        $normalized = trim($taskType);

        return $normalized !== '' ? $normalized : 'Assigned task';
    }

    private function formatLocation($houseNumber, $penName): string
    {
        $house = $houseNumber ?: 'Unknown House';
        $pen = $penName ?: 'Unknown Pen';

        return "{$house}, {$pen}";
    }
}