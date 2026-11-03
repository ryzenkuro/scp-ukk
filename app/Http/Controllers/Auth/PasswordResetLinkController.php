<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class PasswordResetLinkController extends Controller
{
    /**
     * 5 Role pengguna yang diizinkan untuk verifikasi & reset password mandiri.
     */
    protected array $allowedRoles = [
        'guru',
        'wali_kelas',
        'guru_bk',
        'siswa',
        'orang_tua',
    ];

    /**
     * Role administrator yang dilarang reset mandiri (harus lewat server pusat).
     */
    protected array $blockedRoles = [
        'waka_kurikulum',
    ];

    /**
     * Redirect GET /forgot-password ke halaman login dengan parameter modal.
     */
    public function create(): RedirectResponse
    {
        return redirect()->route('login', ['forgot' => 1]);
    }

    /**
     * Verifikasi identitas pengguna (Step 1).
     * Memeriksa username, role, nomor identitas resmi (NISN/NIP), dan tanggal lahir rahasia.
     * Dilengkapi Rate Limiting Anti-Brute Force (Maksimal 3x percobaan per 15 menit).
     */
    public function verify(Request $request): JsonResponse
    {
        // 1. Keamanan Anti-Brute Force: Batasi maksimal 3x percobaan salah dalam 15 menit
        $usernameInput = Str::lower(trim((string) $request->input('username', '')));
        $throttleKey = 'password-reset:' . $usernameInput . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $minutes = max(1, (int) ceil($seconds / 60));

            return response()->json([
                'success' => false,
                'message' => "Terlalu banyak percobaan verifikasi yang gagal. Akses dikunci sementara selama {$minutes} menit demi keamanan akun.",
            ], 429);
        }

        $validated = $request->validate([
            'username'   => ['required', 'string', 'max:50'],
            'role_name'  => ['required', 'string'],
            'nisn_nip'   => ['required', 'string', 'max:30'],
            'birth_date' => ['required', 'date'],
        ], [
            'username.required'   => 'Username akun wajib diisi.',
            'role_name.required'  => 'Peran akun wajib dipilih.',
            'nisn_nip.required'   => 'Nomor identitas resmi (NISN / NIP) wajib diisi.',
            'birth_date.required' => 'Tanggal lahir terdaftar wajib diisi.',
            'birth_date.date'     => 'Format tanggal lahir tidak valid.',
        ]);

        $user = User::with(['role', 'children'])->where('username', $validated['username'])->first();

        if (! $user) {
            RateLimiter::hit($throttleKey, 900);
            $remaining = RateLimiter::remaining($throttleKey, 3);
            return response()->json([
                'success' => false,
                'message' => "Username tidak terdaftar dalam pangkalan data SCP. Sisa percobaan: {$remaining} kali.",
            ], 404);
        }

        $userRole = $user->role?->role_name;

        // Cek jika akun administrator (Waka Kurikulum)
        if (in_array($userRole, $this->blockedRoles, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Administrator (Waka Kurikulum) tidak dapat direset secara mandiri. Silakan hubungi pengelola server pusat.',
            ], 403);
        }

        // Cek jika akun termasuk dalam 5 role yang diizinkan
        if (! in_array($userRole, $this->allowedRoles, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Peran akun ini tidak memenuhi syarat untuk pemulihan kata sandi mandiri.',
            ], 403);
        }

        // Validasi kecocokan role yang dipilih
        if ($userRole !== $validated['role_name']) {
            RateLimiter::hit($throttleKey, 900);
            $remaining = RateLimiter::remaining($throttleKey, 3);
            return response()->json([
                'success' => false,
                'message' => "Peran akun yang Anda pilih tidak sesuai dengan data terdaftar di sistem. Sisa percobaan: {$remaining} kali.",
            ], 422);
        }

        // 2. Evaluasi Data Rahasia Akademik (NISN/NIP & Tanggal Lahir)
        $inputIdentity = trim($validated['nisn_nip']);
        $inputBirthDate = Carbon::parse($validated['birth_date'])->format('Y-m-d');

        $isVerified = false;

        if ($userRole === 'orang_tua') {
            // Untuk Orang Tua: Memverifikasi identitas anak (siswa terdaftar)
            $child = $user->children()->first();

            if ($child) {
                $childIdentity = $child->nisn_nip ?: $child->username;
                $childBirthDate = $child->birth_date ? Carbon::parse($child->birth_date)->format('Y-m-d') : null;

                $matchId = ($inputIdentity === $childIdentity) || ($inputIdentity === $child->username);
                $matchBirth = $childBirthDate && ($inputBirthDate === $childBirthDate);

                if ($matchId && $matchBirth) {
                    $isVerified = true;
                }
            }
        } else {
            // Untuk Siswa, Guru, Wali Kelas, dan Guru BK: Memverifikasi data diri sendiri
            $userIdentity = $user->nisn_nip ?: $user->username;
            $userBirthDate = $user->birth_date ? Carbon::parse($user->birth_date)->format('Y-m-d') : null;

            $matchId = ($inputIdentity === $userIdentity) || ($inputIdentity === $user->username);
            $matchBirth = $userBirthDate && ($inputBirthDate === $userBirthDate);

            if ($matchId && $matchBirth) {
                $isVerified = true;
            }
        }

        if (! $isVerified) {
            RateLimiter::hit($throttleKey, 900);
            $remaining = RateLimiter::remaining($throttleKey, 3);
            return response()->json([
                'success' => false,
                'message' => "Kombinasi Nomor Identitas Resmi (NISN/NIP) atau Tanggal Lahir tidak cocok dengan data pangkalan data sekolah. Sisa percobaan: {$remaining} kali.",
            ], 422);
        }

        // Jika berhasil, bersihkan hitungan rate limiter
        RateLimiter::clear($throttleKey);

        // Generate token verifikasi sementara (berlaku 15 menit)
        $token = Str::random(40);
        session([
            'reset_password_user_id' => $user->id,
            'reset_password_token'   => $token,
            'reset_password_expires' => now()->addMinutes(15),
        ]);
        // Simpan juga pada Cache sistem agar mendukung client stateless (Mobile App / REST API)
        Cache::put("reset_password:{$token}", $user->id, now()->addMinutes(15));

        $roleLabels = [
            'guru'        => 'Guru Pengajar',
            'wali_kelas'  => 'Wali Kelas',
            'guru_bk'     => 'Guru Bimbingan Konseling (BK)',
            'siswa'       => 'Siswa',
            'orang_tua'   => 'Orang Tua / Wali Siswa',
        ];

        return response()->json([
            'success'    => true,
            'message'    => 'Identitas akademik berhasil diverifikasi secara ketat! Silakan buat kata sandi baru Anda.',
            'token'      => $token,
            'username'   => $user->username,
            'fullname'   => $user->fullname,
            'role_label' => $roleLabels[$userRole] ?? ucfirst(str_replace('_', ' ', $userRole)),
        ]);
    }

    /**
     * Simpan kata sandi baru (Step 2).
     */
    public function reset(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username'              => ['required', 'string'],
            'token'                 => ['required', 'string'],
            'password'              => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $sessionToken = session('reset_password_token');
        $sessionUserId = session('reset_password_user_id');
        $expiresAt = session('reset_password_expires');

        $isSessionValid = $sessionToken && $sessionToken === $validated['token'] && $expiresAt && now()->lte($expiresAt);
        $cachedUserId = Cache::get("reset_password:{$validated['token']}");

        if (! $isSessionValid && ! $cachedUserId) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi verifikasi telah kedaluwarsa atau tidak valid. Silakan ulangi verifikasi.',
            ], 403);
        }

        $userId = $sessionUserId ?: $cachedUserId;
        $user = User::with('role')->where('id', $userId)->where('username', $validated['username'])->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Pengguna tidak ditemukan atau data sesi tidak valid.',
            ], 404);
        }

        // Proteksi ganda untuk administrator
        if (in_array($userRole = $user->role?->role_name, $this->blockedRoles, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Administrator (Waka Kurikulum) tidak dapat diubah melalui layanan ini.',
            ], 403);
        }

        $user->update([
            'password'       => Hash::make($validated['password']),
            'remember_token' => Str::random(60),
        ]);

        // Bersihkan sesi dan cache setelah berhasil
        session()->forget([
            'reset_password_user_id',
            'reset_password_token',
            'reset_password_expires',
        ]);
        Cache::forget("reset_password:{$validated['token']}");

        return response()->json([
            'success' => true,
            'message' => 'Kata sandi berhasil diperbarui! Silakan masuk dengan kata sandi baru Anda.',
        ]);
    }
}
