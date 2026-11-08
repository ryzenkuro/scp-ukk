<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('username', $request->username)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Username atau password salah',
            ], 401);
        }

        if (! $user->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun tidak aktif. Hubungi administrator.',
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        $isWali = $user->isWaliKelas();
        $waliClasses = $user->getWaliClasses();

        $student = in_array((int) $user->role_id, [3, 6], true) && $user->student_id
            ? DB::table('siswas')->where('id', $user->student_id)->first()
            : null;

        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'fullname' => $user->fullname,
                    'role_id' => $user->role_id,
                    'student_id' => $user->student_id,
                    'class_name' => $student->kelas ?? null,
                    'nis' => $student->nis ?? null,
                    'student' => $student ? [
                        'id' => $student->id,
                        'name' => $student->name,
                        'kelas' => $student->kelas,
                        'nis' => $student->nis,
                        'jurusan' => $student->jurusan,
                    ] : null,
                    'is_wali_kelas' => $isWali,
                    'wali_classes' => $waliClasses,
                ],
                'token_type' => 'Bearer',
                'access_token' => $token,
            ],
        ], 200);
    }

    public function logout(Request $request)
    {
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Logout berhasil',
        ], 200);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        $data = $user ? $user->toArray() : [];
        if ($user) {
            $data['is_wali_kelas'] = $user->isWaliKelas();
            $data['wali_classes'] = $user->getWaliClasses();
        }

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ], 200);
    }

    public function profile(Request $request)
    {
        $user = $request->user();
        $roleNameMap = [
            1 => 'Waka Kurikulum',
            2 => 'Guru Pengajar',
            3 => 'Siswa',
            4 => 'Guru BK',
            5 => 'Wali Kelas',
            6 => 'Orang Tua',
        ];
        $student = in_array((int) $user->role_id, [3, 6], true) && $user->student_id
            ? DB::table('siswas')->where('id', $user->student_id)->first()
            : null;
        $subjects = in_array((int) $user->role_id, [1, 2, 4, 5], true)
            ? DB::table('schedules')->where('teacher_id', $user->id)->whereNotNull('subject')->distinct()->orderBy('subject')->pluck('subject')
            : collect();

        $isWali = $user->isWaliKelas();
        $waliClasses = $user->getWaliClasses();
        $roleName = $roleNameMap[(int) $user->role_id] ?? 'Pengguna';
        if ($isWali && (int) $user->role_id === 2) {
            $roleName = 'Guru (Wali Kelas ' . implode(', ', $waliClasses) . ')';
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $user->id,
                'role_id' => $user->role_id,
                'role_name' => $roleName,
                'is_wali_kelas' => $isWali,
                'wali_classes' => $waliClasses,
                'fullname' => $user->fullname,
                'username' => $user->username,
                'email' => $user->email,
                'phone' => $student->phone ?? $user->phone,
                'is_active' => (bool) $user->is_active,
                'must_change_credentials' => (bool) $user->must_change_credentials,
                'student_id' => $user->student_id,
                'class_id' => $user->class_id,
                'class_name' => $student->kelas ?? null,
                'nip' => in_array((int) $user->role_id, [1, 2, 4, 5]) ? $user->nip : null,
                'nis' => $student->nis ?? null,
                'student' => $student ? [
                    'id' => $student->id,
                    'name' => $student->name,
                    'kelas' => $student->kelas,
                    'nis' => $student->nis,
                    'jurusan' => $student->jurusan,
                ] : null,
                'subjects' => $subjects,
                'created_at' => $user->created_at,
            ],
        ], 200);
    }

    public function forceChangePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $user->password = Hash::make($request->password);
        $user->must_change_credentials = false;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Password berhasil diperbarui',
        ], 200);
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $search = trim($request->username);
        $user = User::where('username', $search)
            ->orWhere('email', strtolower($search))
            ->first();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun tidak ditemukan.',
            ], 404);
        }

        if ((int) $user->role_id === 3 && empty($user->email)) {
            return response()->json([
                'status' => 'info_siswa',
                'message' => 'Akun Siswa (' . ($user->fullname ?: $user->username) . ') belum memiliki email pemulihan. Silakan temui Wali Kelas atau Waka Kurikulum untuk mereset sandi Anda secara langsung.',
            ], 200);
        }

        if (empty($user->email)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun belum memiliki email pemulihan yang terdaftar. Hubungi administrator.',
            ], 422);
        }

        DB::table('password_reset_otps')->where('user_id', $user->getKey())->delete();

        $code = (string) random_int(0, 999999);
        $code = str_pad($code, 6, '0', STR_PAD_LEFT);
        $challengeId = DB::table('password_reset_otps')->insertGetId([
            'user_id' => $user->getKey(),
            'email' => strtolower($user->email),
            'code_hash' => Hash::make($code),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
        ]);

        $emailParts = explode('@', $user->email);
        $maskedEmail = substr($emailParts[0], 0, 2) . '***@' . ($emailParts[1] ?? '');

        return response()->json([
            'status' => 'success',
            'message' => 'Kode OTP 6 digit telah dikirim ke email pemulihan (' . $maskedEmail . ').',
            'otp_challenge_id' => $challengeId,
            'demo_otp' => app()->environment('local') || config('app.debug') ? $code : null,
        ], 200);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp_challenge_id' => 'required|integer',
            'otp' => 'required|digits:6',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $challengeId = $request->otp_challenge_id;
        $challenge = DB::table('password_reset_otps')->where('id', $challengeId)->first();

        if (! $challenge || (int) $challenge->attempts >= 5 || now()->greaterThan($challenge->expires_at)) {
            if ($challenge) {
                DB::table('password_reset_otps')->where('id', $challenge->id)->delete();
            }
            return response()->json([
                'status' => 'error',
                'message' => 'Kode OTP tidak valid atau telah kedaluwarsa. Silakan ajukan ulang.',
            ], 422);
        }

        DB::table('password_reset_otps')->where('id', $challenge->id)->increment('attempts');

        if (! Hash::check($request->otp, $challenge->code_hash)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kode OTP salah.',
            ], 422);
        }

        $user = User::whereKey($challenge->user_id)->first();
        if (! $user) {
            DB::table('password_reset_otps')->where('id', $challenge->id)->delete();
            return response()->json([
                'status' => 'error',
                'message' => 'Akun tidak ditemukan.',
            ], 404);
        }

        $user->password = Hash::make($request->password);
        $user->must_change_credentials = false;
        $user->save();

        DB::table('password_reset_otps')->where('id', $challenge->id)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Password berhasil direset. Silakan login kembali dengan sandi baru.',
        ], 200);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $rules = [
            'fullname' => 'required|string|max:100',
            'username' => 'required|string|max:50|unique:users,username,'.$user->id,
            'phone' => 'nullable|string|max:20',
        ];
        if ((int) $user->role_id === 2) {
            $rules['nip'] = ['nullable', 'string', 'max:30', Rule::unique('users', 'nip')->ignore($user->id)];
        }
        if ((int) $user->role_id === 3 && $user->student_id) {
            $rules['nis'] = ['nullable', 'string', 'max:30', Rule::unique('siswas', 'nis')->ignore($user->student_id)];
        }

        if ($request->filled('new_password')) {
            $rules['current_password'] = 'required|string';
            $rules['new_password'] = 'required|string|min:6|confirmed';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        if ($request->filled('new_password')) {
            if (! Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Password saat ini tidak sesuai',
                ], 422);
            }
        }

        DB::transaction(function () use ($validated, $request, $user) {
            $user->fullname = $validated['fullname'];
            $user->username = $validated['username'];
            if (array_key_exists('phone', $validated)) {
                $user->phone = $validated['phone'];
            }
            if ((int) $user->role_id === 2) {
                if (array_key_exists('nip', $validated)) {
                    $user->nip = $validated['nip'];
                }
            }
            if ($request->filled('new_password')) {
                $user->password = Hash::make($request->new_password);
            }
            $user->save();

            if ((int) $user->role_id === 3 && $user->student_id && array_key_exists('nis', $validated)) {
                DB::table('siswas')->where('id', $user->student_id)->update([
                    'nis' => $validated['nis'],
                    'phone' => $validated['phone'] ?? $user->phone,
                    'updated_at' => now(),
                ]);
            } elseif ((int) $user->role_id === 3 && $user->student_id && array_key_exists('phone', $validated)) {
                DB::table('siswas')->where('id', $user->student_id)->update([
                    'phone' => $validated['phone'],
                    'updated_at' => now(),
                ]);
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Profil berhasil diperbarui',
            'data' => [
                'id' => $user->id,
                'fullname' => $user->fullname,
                'username' => $user->username,
                'role_id' => $user->role_id,
                'phone' => (int) $user->role_id === 3 && $user->student_id
                    ? DB::table('siswas')->where('id', $user->student_id)->value('phone')
                    : $user->phone,
                'nip' => (int) $user->role_id === 2 ? $user->nip : null,
                'nis' => (int) $user->role_id === 3 && $user->student_id
                    ? DB::table('siswas')->where('id', $user->student_id)->value('nis')
                    : null,
            ],
        ], 200);
    }
}

