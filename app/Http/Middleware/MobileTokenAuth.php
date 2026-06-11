<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MobileTokenAuth
{
    public function handle(Request $request, Closure $next)
    {
        $authorization = (string) $request->header('Authorization');

        if (!str_starts_with($authorization, 'Bearer ')) {
            return response()->json([
                'message' => 'Missing mobile authorization token.',
            ], 401);
        }

        $plainToken = trim(substr($authorization, 7));

        if ($plainToken === '') {
            return response()->json([
                'message' => 'Missing mobile authorization token.',
            ], 401);
        }

        $tokenHash = hash('sha256', $plainToken);

        $token = DB::table('mobile_api_tokens as mat')
            ->join('user as u', 'mat.employee_id', '=', 'u.EmployeeId')
            ->where('mat.token_hash', $tokenHash)
            ->whereNull('mat.revoked_at')
            ->where(function ($query) {
                $query->whereNull('mat.expires_at')
                    ->orWhere('mat.expires_at', '>', now());
            })
            ->first([
                'mat.id',
                'mat.employee_id',
                'u.Role',
            ]);

        if (!$token) {
            return response()->json([
                'message' => 'Invalid or expired mobile token.',
            ], 401);
        }

        if (strtolower((string) $token->Role) !== 'flockman') {
            return response()->json([
                'message' => 'Only Flockman accounts can use the mobile app.',
            ], 403);
        }

        DB::table('mobile_api_tokens')
            ->where('id', $token->id)
            ->update([
                'last_used_at' => now(),
                'updated_at' => now(),
            ]);

        $request->attributes->set('mobile_employee_id', (int) $token->employee_id);
        $request->attributes->set('mobile_token_id', (int) $token->id);

        return $next($request);
    }
}