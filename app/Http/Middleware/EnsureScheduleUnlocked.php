<?php

namespace App\Http\Middleware;

use App\Models\SystemConfiguration;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureScheduleUnlocked
{
    /**
     * Memastikan jadwal tidak sedang dikunci oleh Waka Kurikulum.
     * Melindungi route Web dan endpoint API dari modifikasi (POST/PUT/DELETE).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (SystemConfiguration::current()->is_schedule_locked) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Jadwal telah dikunci oleh Waka Kurikulum. Perubahan tidak diizinkan.',
                ], 403);
            }

            return back()->withErrors([
                'locked' => 'Jadwal telah dikunci oleh Waka Kurikulum. Perubahan tidak diizinkan.',
            ]);
        }

        return $next($request);
    }
}
