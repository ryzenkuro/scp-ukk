<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Login via REST API dan dapatkan Sanctum Bearer Token
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'device_name' => 'nullable|string|max:100',
        ]);

        $user = User::with('role')->where('username', $validated['username'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Username atau password tidak valid.',
            ], 401);
        }

        // Buat token Sanctum
        $token = $user->createToken($validated['device_name'] ?? 'postman-client')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil.',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'fullname' => $user->fullname,
                'role' => $user->role?->role_name,
                'class_id' => $user->class_id,
            ],
        ]);
    }

    /**
     * Profil pengguna terautentikasi (Bearer Token)
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['role', 'schoolClass']);

        return response()->json([
            'status' => 'success',
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'fullname' => $user->fullname,
                'role' => $user->role?->role_name,
                'class_id' => $user->class_id,
                'class_name' => $user->schoolClass?->class_name,
            ],
        ]);
    }

    /**
     * Logout dan hapus token yang sedang aktif
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        auth()->guard('sanctum')->forgetUser();

        return response()->json([
            'status' => 'success',
            'message' => 'Logout berhasil. Token telah dicabut.',
        ]);
    }
}
