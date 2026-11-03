<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Tests\TestCase;

class SanctumAuthApiTest extends TestCase
{
    public function test_api_login_returns_sanctum_bearer_token(): void
    {
        $response = $this->postJson(route('api.login'), [
            'username' => 'guru1',
            'password' => 'password',
            'device_name' => 'postman-test',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'token',
                'token_type',
                'user' => [
                    'id',
                    'username',
                    'fullname',
                    'role',
                    'class_id',
                ],
            ]);

        $token = $response->json('token');
        $this->assertNotEmpty($token);

        // 2. Akses endpoint privat /api/me dengan Bearer Token
        $responseMe = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson(route('api.me'));

        $responseMe->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'user' => [
                    'username' => 'guru1',
                ],
            ]);

        // 3. Logout dan cabut token
        $responseLogout = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson(route('api.logout'));

        $responseLogout->assertStatus(200)
            ->assertJson(['status' => 'success']);

        // 4. Token yang sudah dicabut tidak bisa digunakan lagi
        $responseRevoked = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson(route('api.me'));

        $responseRevoked->assertStatus(401);
    }

    public function test_api_login_fails_with_invalid_credentials(): void
    {
        $response = $this->postJson(route('api.login'), [
            'username' => 'guru1',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'status' => 'error',
                'message' => 'Username atau kata sandi tidak sesuai.',
            ]);
    }

    public function test_api_forgot_password_verify_and_reset_flow(): void
    {
        // 1. Verifikasi data akademik via API (Siswa1)
        $verifyRes = $this->postJson(route('api.forgot-password.verify'), [
            'username'   => 'siswa1',
            'role_name'  => 'siswa',
            'nisn_nip'   => '0061234567',
            'birth_date' => '2008-05-14',
        ]);

        $verifyRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'username' => 'siswa1',
            ]);

        $token = $verifyRes->json('token');
        $this->assertNotEmpty($token);

        // 2. Reset kata sandi via API dengan token dari Cache
        $resetRes = $this->postJson(route('api.forgot-password.reset'), [
            'username'              => 'siswa1',
            'token'                 => $token,
            'password'              => 'NewSiswaPass123!',
            'password_confirmation' => 'NewSiswaPass123!',
        ]);

        $resetRes->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // Kembalikan ke password default 'password'
        $user = User::where('username', 'siswa1')->first();
        $user->update(['password' => \Illuminate\Support\Facades\Hash::make('password')]);
    }

    public function test_public_self_registration_is_disabled_and_redirects_to_login(): void
    {
        $response = $this->get('/register');
        $response->assertRedirect(route('login'));
    }
}
