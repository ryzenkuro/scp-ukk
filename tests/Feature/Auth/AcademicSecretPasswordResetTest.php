<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AcademicSecretPasswordResetTest extends TestCase
{
    protected function tearDown(): void
    {
        // Clear rate limiter keys used in testing
        RateLimiter::clear('password-reset:siswa1|127.0.0.1');
        RateLimiter::clear('password-reset:nonexistent|127.0.0.1');
        RateLimiter::clear('password-reset:guru1|127.0.0.1');
        RateLimiter::clear('password-reset:ortu1|127.0.0.1');
        RateLimiter::clear('password-reset:waka|127.0.0.1');

        parent::tearDown();
    }

    public function test_forgot_password_route_redirects_to_login_with_query(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertRedirect('/login?forgot=1');
    }

    public function test_student_can_verify_identity_with_valid_nisn_and_birthdate(): void
    {
        RateLimiter::clear('password-reset:siswa1|127.0.0.1');

        $response = $this->postJson('/forgot-password/verify', [
            'username'   => 'siswa1',
            'role_name'  => 'siswa',
            'nisn_nip'   => '0061234567',
            'birth_date' => '2008-05-14',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success'  => true,
                'username' => 'siswa1',
            ])
            ->assertJsonStructure(['token', 'role_label']);

        $this->assertEquals('siswa1', session('reset_password_user_id') ? User::find(session('reset_password_user_id'))->username : null);
    }

    public function test_verification_fails_with_wrong_academic_secret(): void
    {
        RateLimiter::clear('password-reset:siswa1|127.0.0.1');

        $response = $this->postJson('/forgot-password/verify', [
            'username'   => 'siswa1',
            'role_name'  => 'siswa',
            'nisn_nip'   => '0061234567',
            'birth_date' => '1999-01-01', // salah
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_waka_kurikulum_administrator_is_blocked_from_self_reset(): void
    {
        $response = $this->postJson('/forgot-password/verify', [
            'username'   => 'waka',
            'role_name'  => 'waka_kurikulum',
            'nisn_nip'   => '197501012000031001',
            'birth_date' => '1975-01-01',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Akun Administrator (Waka Kurikulum) tidak dapat direset secara mandiri. Silakan hubungi pengelola server pusat.',
            ]);
    }

    public function test_rate_limiter_locks_account_after_3_consecutive_failed_attempts(): void
    {
        RateLimiter::clear('password-reset:siswa1|127.0.0.1');

        // Attempt 1 (gagal)
        $r1 = $this->postJson('/forgot-password/verify', [
            'username'   => 'siswa1',
            'role_name'  => 'siswa',
            'nisn_nip'   => '0000000000',
            'birth_date' => '2000-01-01',
        ]);
        $r1->assertStatus(422);

        // Attempt 2 (gagal)
        $r2 = $this->postJson('/forgot-password/verify', [
            'username'   => 'siswa1',
            'role_name'  => 'siswa',
            'nisn_nip'   => '0000000000',
            'birth_date' => '2000-01-01',
        ]);
        $r2->assertStatus(422);

        // Attempt 3 (gagal)
        $r3 = $this->postJson('/forgot-password/verify', [
            'username'   => 'siswa1',
            'role_name'  => 'siswa',
            'nisn_nip'   => '0000000000',
            'birth_date' => '2000-01-01',
        ]);
        $r3->assertStatus(422);

        // Attempt 4 (Terblokir Rate Limiter -> 429 Too Many Requests)
        $r4 = $this->postJson('/forgot-password/verify', [
            'username'   => 'siswa1',
            'role_name'  => 'siswa',
            'nisn_nip'   => '0061234567',
            'birth_date' => '2008-05-14',
        ]);
        $r4->assertStatus(429)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_password_can_be_reset_after_successful_verification(): void
    {
        RateLimiter::clear('password-reset:siswa1|127.0.0.1');

        // 1. Verifikasi
        $verifyRes = $this->postJson('/forgot-password/verify', [
            'username'   => 'siswa1',
            'role_name'  => 'siswa',
            'nisn_nip'   => '0061234567',
            'birth_date' => '2008-05-14',
        ]);

        $token = $verifyRes->json('token');
        $this->assertNotEmpty($token);

        // 2. Reset kata sandi
        $newPass = 'PasswordBaru123!';
        $resetRes = $this->postJson('/forgot-password/reset', [
            'username'              => 'siswa1',
            'token'                 => $token,
            'password'              => $newPass,
            'password_confirmation' => $newPass,
        ]);

        $resetRes->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // Pastikan password baru tersimpan dan dapat digunakan
        $user = User::where('username', 'siswa1')->first();
        $this->assertTrue(Hash::check($newPass, $user->password));

        // Kembalikan ke password default 'password' agar demo akun tetap konsisten
        $user->update(['password' => Hash::make('password')]);
    }
}

