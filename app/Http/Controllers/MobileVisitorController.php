<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MobileVisitorController extends Controller
{
    public function createVisitorPhotoUploadUrl(Request $request)
    {
        $employeeId = (int) $request->attributes->get('mobile_employee_id');

        $validated = $request->validate([
            'mime_type' => 'required|string|in:image/jpeg,image/png,image/webp',
        ]);

        $bucket = config('services.supabase.visitor_photos_bucket');
        $baseUrl = rtrim(config('services.supabase.url'), '/');
        $serviceRoleKey = config('services.supabase.service_role_key');

        if (blank($bucket) || blank($baseUrl) || blank($serviceRoleKey)) {
            return response()->json([
                'message' => 'Supabase storage is not configured correctly.',
            ], 500);
        }

        $extension = match ($validated['mime_type']) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };

        $path = sprintf(
            'employee-%d/visitor-%s.%s',
            $employeeId,
            Str::uuid()->toString(),
            $extension
        );

        $endpoint = sprintf(
            '%s/storage/v1/object/upload/sign/%s/%s',
            $baseUrl,
            $bucket,
            $path
        );

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $serviceRoleKey,
            'apikey' => $serviceRoleKey,
            'Content-Type' => 'application/json',
        ])->post($endpoint, [
                    'expiresIn' => 600,
                ]);

        if (!$response->successful()) {
            return response()->json([
                'message' => 'Failed to create signed upload URL.',
                'details' => $response->body(),
            ], 500);
        }

        $payload = $response->json();
        $token = $payload['token'] ?? null;

        if (blank($token)) {
            return response()->json([
                'message' => 'Supabase did not return an upload token.',
                'details' => $payload,
            ], 500);
        }

        return response()->json([
            'success' => true,
            'bucket' => $bucket,
            'path' => $path,
            'token' => $token,
            'public_url' => $this->buildVisitorPhotoUrl($path),
        ]);
    }

    public function timeIn(Request $request)
    {
        $employeeId = (int) $request->attributes->get('mobile_employee_id');

        $validated = $request->validate([
            'date' => 'required|date',
            'time_in' => 'required|date_format:H:i',
            'name' => 'required|string|max:100',
            'purpose' => 'required|string|max:255',
            'foot_bath' => 'required|boolean',
            'sanitation' => 'required|boolean',
            'ppe' => 'required|boolean',
            'photo_path' => 'nullable|string|max:500',
        ]);

        $employee = Employee::find($employeeId);
        $monitoredBy = $this->resolveMonitoredBy($employee, $employeeId);

        $id = DB::table('visitor_logs')->insertGetId([
            'employee_id' => $employeeId,
            'date' => $validated['date'],
            'time_in' => $validated['time_in'],
            'time_out' => null,
            'name' => $validated['name'],
            'purpose' => $validated['purpose'],
            'foot_bath' => $validated['foot_bath'] ? 'Yes' : 'No',
            'sanitation' => $validated['sanitation'] ? 'Yes' : 'No',
            'ppe' => $validated['ppe'] ? 'Yes' : 'No',
            'photo_url' => $validated['photo_path'] ?? null,
            'monitored_by' => $monitoredBy,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Visitor timed in successfully.',
            'visitor_log_id' => $id,
        ]);
    }

    public function getOpenVisitors(Request $request)
    {
        $visitors = DB::table('visitor_logs')
            ->whereNull('time_out')
            ->orderByDesc('date')
            ->orderByDesc('time_in')
            ->orderByDesc('id')
            ->get()
            ->map(function ($visitor) {
                return [
                    'id' => (int) $visitor->id,
                    'date' => $visitor->date,
                    'time_in' => $visitor->time_in ? substr((string) $visitor->time_in, 0, 5) : '',
                    'name' => $visitor->name,
                    'purpose' => $visitor->purpose,
                    'foot_bath' => strtoupper((string) $visitor->foot_bath) === 'YES',
                    'sanitation' => strtoupper((string) $visitor->sanitation) === 'YES',
                    'ppe' => strtoupper((string) $visitor->ppe) === 'YES',
                    'photo_url' => $this->buildVisitorPhotoUrl($visitor->photo_url),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'visitors' => $visitors,
        ]);
    }

    public function timeOut(Request $request)
    {
        $validated = $request->validate([
            'visitor_log_id' => 'required|integer|exists:visitor_logs,id',
            'time_out' => 'required|date_format:H:i',
        ]);

        $visitor = DB::table('visitor_logs')
            ->where('id', $validated['visitor_log_id'])
            ->first();

        if (!$visitor) {
            return response()->json([
                'message' => 'Visitor log not found.',
            ], 404);
        }

        if (!empty($visitor->time_out)) {
            return response()->json([
                'message' => 'This visitor has already been timed out.',
            ], 422);
        }

        DB::table('visitor_logs')
            ->where('id', $validated['visitor_log_id'])
            ->update([
                'time_out' => $validated['time_out'],
                'updated_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Visitor timed out successfully.',
        ]);
    }

    private function resolveMonitoredBy(?Employee $employee, int $employeeId): string
    {
        if ($employee) {
            return trim(sprintf(
                '%s %s %s %s',
                $employee->FirstName ?? '',
                $employee->MiddleName ?? '',
                $employee->LastName ?? '',
                $employee->Suffix ?? '',
            ));
        }

        return (string) $employeeId;
    }

    private function buildVisitorPhotoUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        $baseUrl = rtrim(config('services.supabase.url'), '/');
        $bucket = config('services.supabase.visitor_photos_bucket');

        return sprintf(
            '%s/storage/v1/object/public/%s/%s',
            $baseUrl,
            $bucket,
            ltrim($path, '/')
        );
    }
}