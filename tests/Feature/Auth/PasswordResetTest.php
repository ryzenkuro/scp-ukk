<?php

namespace Tests\Feature\Auth;

use App\Mail\PasswordResetOtpMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_otp_can_be_requested(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $response = $this->post('/forgot-password/send-otp', ['email' => $user->email]);

        $response->assertSessionHasNoErrors();
        Mail::assertSent(PasswordResetOtpMail::class);
    }
}
