<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AccountActivationController extends Controller
{
    /**
     * Tampilkan formulir aktivasi & pengaturan kredensial mandiri
     */
    public function show(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        // Jika akun sudah tidak perlu aktivasi, arahkan ke dashboard
        if (! $user->must_change_credentials) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Auth/ActivateAccount', [
            'initialEmail' => $user->email ?? '',
        ]);
    }

    /**
     * Simpan kredensial baru buatan user sendiri
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->validate([
            'new_username' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'alpha_dash',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'email' => [
                $request->has('email') ? 'required' : 'nullable',
                'string',
                'email',
                'max:100',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'new_username.unique' => 'Username tersebut sudah digunakan oleh akun lain. Silakan pilih username lain.',
            'new_username.alpha_dash' => 'Username hanya boleh berisi huruf, angka, tanda strip (-), atau garis bawah (_).',
            'email.required' => 'Email aktif wajib diisi untuk keperluan pemulihan akun.',
            'email.email' => 'Format alamat email tidak valid.',
            'email.unique' => 'Email tersebut sudah terdaftar pada akun lain.',
            'new_password.min' => 'Kata sandi baru minimal 8 karakter.',
            'new_password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        $user->username = $request->input('new_username');
        if ($request->filled('email')) {
            $user->email = strtolower($request->input('email'));
        }
        $user->password = Hash::make($request->input('new_password'));
        $user->must_change_credentials = false;
        $user->save();

        return redirect()->route('dashboard')->with('success', 'Selamat! Akun Anda berhasil diaktivasi dengan kredensial baru.');
    }
}
