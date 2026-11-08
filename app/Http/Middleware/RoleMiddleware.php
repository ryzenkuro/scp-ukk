<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $userRole = $user->role?->name ?? '';

        // Flatten roles if passed with commas like 'waka,guru'
        $allowedRoles = [];
        foreach ($roles as $r) {
            foreach (explode(',', $r) as $subRole) {
                $allowedRoles[] = trim($subRole);
            }
        }

        $hasAccess = in_array($userRole, $allowedRoles, true)
            || (in_array('wali_kelas', $allowedRoles, true) && $user->isWaliKelas())
            || (in_array('kesiswaan', $allowedRoles, true) && $user->isKesiswaan());

        if (! $hasAccess) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk membuka halaman ini.');
        }

        return $next($request);
    }
}

