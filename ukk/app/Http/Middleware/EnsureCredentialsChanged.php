<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCredentialsChanged
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->must_change_credentials) {
            if (! $request->routeIs('auth.activate') && 
                ! $request->routeIs('auth.activate.store') && 
                ! $request->routeIs('logout')) {
                return redirect()->route('auth.activate');
            }
        }

        return $next($request);
    }
}

