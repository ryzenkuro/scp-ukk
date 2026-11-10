<?php

namespace Tests\Feature;

use App\Mail\PasswordResetOtpMail;
use App\Models\PasswordResetOtp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ForgotPasswordOtpFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_activation_form_updates_email_and_kredensial(): void
    {
        $user = User::where('role_id', 3)->first();
        $user->update([
            'must_change_credentials' => true,
            'email' => null,
        ]);

        $response = $this->actingAs($user)->post('/auth/activate', [
            'new_username' => 'siswa_aktif_email',
            'email' => 'siswa.baru@smkn1bantul.sch.id',
            'new_password' => 'PasswordBaru123',
            'new_password_confirmation' => 'PasswordBaru123',
        ]);

        $response->assertRedirect(route('dashboard'));

        $user->refresh();
        $this->assertEquals('siswa_aktif_email', $user->username);
        $this->assertEquals('siswa.baru@smkn1bantul.sch.id', $user->email);
        $this->assertFalse($user->must_change_credentials);
    }

    public function test_forgot_password_rejects_unregistered_email(): void
    {
        $response = $this->post('/forgot-password/send-otp', [
            'email' => 'tidak.terdaftar@gmail.com',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_forgot_password_generates_otp_and_sends_email(): void
    {
        Mail::fake();

        $user = User::create([
            'username' => 'guru_lupa_sandi',
            'fullname' => 'Guru Lupa Sandi',
            'email' => 'guru.lupa@smkn1bantul.sch.id',
            'role_id' => 2,
            'password' => Hash::make('password_lama'),
            'must_change_credentials' => false,
            'is_active' => true,
        ]);

        $response = $this->post('/forgot-password/send-otp', [
            'email' => 'guru.lupa@smkn1bantul.sch.id',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        // Pastikan tabel password_reset_otps mencatat data
        $this->assertDatabaseHas('password_reset_otps', [
            'user_id' => $user->id,
            'email' => 'guru.lupa@smkn1bantul.sch.id',
        ]);

        // Pastikan Mail::send terpanggil
        Mail::assertSent(PasswordResetOtpMail::class, function ($mail) {
            return $mail->hasTo('guru.lupa@smkn1bantul.sch.id') && strlen($mail->otpCode) === 6;
        });
    }

    public function test_forgot_password_rejects_incorrect_otp(): void
    {
        $user = User::create([
            'username' => 'user_otp_salah',
            'email' => 'otp.salah@smkn1bantul.sch.id',
            'role_id' => 2,
            'password' => Hash::make('password123'),
        ]);

        PasswordResetOtp::create([
            'user_id' => $user->id,
            'email' => 'otp.salah@smkn1bantul.sch.id',
            'code_hash' => Hash::make('123456'),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
        ]);

        $response = $this->post('/forgot-password/verify-otp', [
            'email' => 'otp.salah@smkn1bantul.sch.id',
            'otp' => '999999', // OTP salah
        ]);

        $response->assertSessionHasErrors('otp');
    }

    public function test_forgot_password_resets_password_and_redirects_to_login(): void
    {
        $user = User::create([
            'username' => 'user_berhasil_reset',
            'email' => 'reset.sukses@smkn1bantul.sch.id',
            'role_id' => 2,
            'password' => Hash::make('password_lama_123'),
            'must_change_credentials' => true,
        ]);

        $rawOtp = '654321';

        PasswordResetOtp::create([
            'user_id' => $user->id,
            'email' => 'reset.sukses@smkn1bantul.sch.id',
            'code_hash' => Hash::make($rawOtp),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
        ]);

        // Verifikasi OTP sukses
        $verifyResponse = $this->post('/forgot-password/verify-otp', [
            'email' => 'reset.sukses@smkn1bantul.sch.id',
            'otp' => $rawOtp,
        ]);
        $verifyResponse->assertRedirect();
        $verifyResponse->assertSessionHasNoErrors();

        // Submit Kata Sandi Baru
        $resetResponse = $this->post('/forgot-password/reset', [
            'email' => 'reset.sukses@smkn1bantul.sch.id',
            'otp' => $rawOtp,
            'password' => 'SandiBaruBaru999',
            'password_confirmation' => 'SandiBaruBaru999',
        ]);

        $resetResponse->assertRedirect(route('login'));
        $resetResponse->assertSessionHas('success');

        // Data OTP terhapus
        $this->assertDatabaseMissing('password_reset_otps', [
            'user_id' => $user->id,
        ]);

        // Pastikan password baru tersimpan dan must_change_credentials = false
        $user->refresh();
        $this->assertTrue(Hash::check('SandiBaruBaru999', $user->password));
        $this->assertFalse($user->must_change_credentials);

        // Login dengan password baru harus berhasil
        $loginResponse = $this->post('/login', [
            'username' => 'user_berhasil_reset',
            'password' => 'SandiBaruBaru999',
        ]);
        $loginResponse->assertRedirect('/guru/dashboard');
    }
}
