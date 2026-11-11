<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetOtpMail;
use App\Models\PasswordResetOtp;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetOtpController extends Controller
{
    /**
     * Tampilkan halaman alur Lupa Sandi
     */
    public function create(Request $request): Response
    {
        // Jika akses baru dari Login (?reset=1) atau bukan redirect hasil submit (tidak ada flash status/error),
        // selalu bersihkan sisa session pemulihan sandi agar mulai dari Langkah 1
        if ($request->has('reset') || (! session()->has('status') && ! session()->has('error'))) {
            session()->forget([
                'password_reset_email',
                'password_reset_step',
                'password_reset_verified_code',
                'demo_otp',
            ]);
        }

        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
            'error' => session('error'),
            'demoOtp' => session('demo_otp'),
            'activeEmail' => session('password_reset_email', ''),
            'currentStep' => session('password_reset_step', 1),
        ]);
    }

    /**
     * Langkah 1: Validasi email akun terdaftar & kirimkan 6 digit OTP
     */
    public function sendOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
        ]);

        $email = strtolower(trim($request->input('email')));
        $user = User::where('email', $email)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => 'Alamat email ini tidak terdaftar di sistem. Pastikan menggunakan email yang sesuai dengan data akun Anda.',
            ]);
        }

        // Hapus kode OTP lama milik user ini
        PasswordResetOtp::where('user_id', $user->id)->delete();

        // Generate 6 digit angka acak
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $otp = PasswordResetOtp::create([
            'user_id' => $user->id,
            'email' => $email,
            'code_hash' => Hash::make($code),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
        ]);

        // Kirim email
        try {
            Mail::to($user->email)->send(new PasswordResetOtpMail($code, $user));
        } catch (\Throwable $e) {
            Log::warning('Pengiriman email OTP gagal: '.$e->getMessage());
        }

        // Masking email untuk privasi: ab***@gmail.com
        $parts = explode('@', $email);
        $namePart = $parts[0];
        $domainPart = $parts[1] ?? '';
        $maskedName = strlen($namePart) > 2 ? substr($namePart, 0, 2).str_repeat('*', min(4, strlen($namePart) - 2)) : $namePart.'**';
        $maskedEmail = "{$maskedName}@{$domainPart}";

        session([
            'password_reset_email' => $email,
            'password_reset_step' => 2,
            'demo_otp' => app()->environment('local') || config('app.debug') ? $code : null,
        ]);

        return back()->with('status', "Kode verifikasi OTP 6 digit telah dikirim ke {$maskedEmail}. Silakan periksa kotak masuk atau spam email Anda.");
    }

    /**
     * Langkah 2: Verifikasi kecocokan 6 digit kode OTP
     */
    public function verifyOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:6'],
        ], [
            'otp.required' => 'Kode OTP wajib diisi.',
            'otp.digits' => 'Kode OTP harus berupa 6 digit angka.',
        ]);

        $email = strtolower(trim($request->input('email')));
        $otpCode = $request->input('otp');

        $otp = PasswordResetOtp::where('email', $email)->orderByDesc('id')->first();

        if (! $otp || $otp->isExpired() || $otp->attempts >= 5) {
            if ($otp) {
                $otp->delete();
            }
            throw ValidationException::withMessages([
                'otp' => 'Kode OTP tidak valid atau telah kedaluwarsa (batas waktu 10 menit). Silakan ajukan kirim ulang kode.',
            ]);
        }

        $otp->increment('attempts');

        if (! Hash::check($otpCode, $otp->code_hash)) {
            throw ValidationException::withMessages([
                'otp' => 'Kode OTP yang Anda masukkan salah. Silakan periksa kembali email Anda.',
            ]);
        }

        session([
            'password_reset_step' => 3,
            'password_reset_verified_code' => $otpCode,
        ]);

        return back()->with('status', 'Kode OTP berhasil diverifikasi. Silakan masukkan kata sandi baru Anda.');
    }

    /**
     * Langkah 3: Simpan kata sandi baru & redirect ke form login
     */
    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.min' => 'Kata sandi baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
        ]);

        $email = strtolower(trim($request->input('email')));
        $otpCode = $request->input('otp');

        $otp = PasswordResetOtp::where('email', $email)->orderByDesc('id')->first();

        if (! $otp || ! Hash::check($otpCode, $otp->code_hash) || $otp->isExpired()) {
            throw ValidationException::withMessages([
                'password' => 'Sesi verifikasi OTP telah kedaluwarsa atau tidak valid. Silakan ulangi langkah pemulihan sandi.',
            ]);
        }

        $user = User::findOrFail($otp->user_id);
        $user->password = Hash::make($request->input('password'));
        $user->must_change_credentials = false;
        $user->save();

        // Bersihkan data OTP dan session
        $otp->delete();
        session()->forget([
            'password_reset_email',
            'password_reset_step',
            'password_reset_verified_code',
            'demo_otp',
        ]);

        return redirect()->route('login')->with('success', 'Kata sandi Anda berhasil diperbarui! Silakan masuk menggunakan kata sandi baru Anda.');
    }
}
