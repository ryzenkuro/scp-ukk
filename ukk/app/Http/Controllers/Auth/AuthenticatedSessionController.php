<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();

        // Jika user masih harus aktivasi kredensial awal
        if ($user->must_change_credentials) {
            return redirect()->route('auth.activate');
        }

        // Redireksi langsung ke dashboard berdasarkan role
        $roleName = $user->role?->name ?? 'waka';
        $defaultRoute = match ($roleName) {
            'waka' => route('waka.dashboard', absolute: false),
            'kesiswaan' => route('kesiswaan.dashboard', absolute: false),
            'wali_kelas' => route('wali-kelas.dashboard', absolute: false),
            'guru' => $user->isWaliKelas()
                ? route('wali-kelas.dashboard', absolute: false)
                : route('guru.dashboard', absolute: false),
            'siswa', 'orang_tua' => route('siswa.dashboard', absolute: false),
            default => route('dashboard', absolute: false),
        };

        // Jika terdapat intended URL di session, validasi apakah aman untuk role pengguna ini
        $intendedUrl = $request->session()->get('url.intended');
        if ($intendedUrl) {
            $isAuthorizedIntended = match ($roleName) {
                'waka' => true,
                'kesiswaan' => str_contains($intendedUrl, '/kesiswaan') || str_contains($intendedUrl, '/guru') || str_contains($intendedUrl, '/profile'),
                'wali_kelas' => str_contains($intendedUrl, '/wali-kelas') || str_contains($intendedUrl, '/guru') || str_contains($intendedUrl, '/profile'),
                'guru' => str_contains($intendedUrl, '/guru') || str_contains($intendedUrl, '/profile'),
                'siswa' => str_contains($intendedUrl, '/siswa') || str_contains($intendedUrl, '/profile'),
                'orang_tua' => str_contains($intendedUrl, '/siswa') || str_contains($intendedUrl, '/profile'),
                default => false,
            };

            if ($isAuthorizedIntended) {
                return redirect()->intended($defaultRoute);
            }

            // Hapus url.intended yang tidak cocok agar tidak memicu 403
            $request->session()->forget('url.intended');
        }

        return redirect()->to($defaultRoute);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
