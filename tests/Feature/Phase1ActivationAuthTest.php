<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase1ActivationAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Test 1: Waka Admin login dengan smkn1bantul2026 langsung masuk ke Waka Dashboard
     */
    public function test_admin_waka_can_login_directly_to_dashboard(): void
    {
        $response = $this->post('/login', [
            'username' => 'waka',
            'password' => 'smkn1bantul2026',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/waka/dashboard');

        // Pastikan halaman Waka Dashboard dapat dibuka
        $dashResponse = $this->actingAs(User::where('username', 'waka')->first())
            ->get('/waka/dashboard');
        $dashResponse->assertStatus(200);
    }

    /**
     * Test 2: Siswa login pertama kali dengan NIS diarahkan ke halaman Aktivasi Akun
     */
    public function test_student_first_time_login_is_redirected_to_activation(): void
    {
        $response = $this->post('/login', [
            'username' => '202611001',
            'password' => 'smkn1bantul2026',
        ]);

        $this->assertAuthenticated();
        
        $user = User::where('username', '202611001')->first();
        $this->assertTrue($user->must_change_credentials);

        // Jika mencoba mengakses dashboard, dicegat middleware dan dilempar ke /auth/activate
        $dashResponse = $this->actingAs($user)->get('/dashboard');
        $dashResponse->assertRedirect(route('auth.activate'));
    }

    /**
     * Test 3: Siswa berhasil melakukan aktivasi (membuat username & password kustom)
     */
    public function test_student_can_set_custom_username_and_password(): void
    {
        $user = User::where('username', '202611001')->first();

        $response = $this->actingAs($user)->post('/auth/activate', [
            'new_username' => 'muhyi_rpl',
            'new_password' => 'muhyi12345',
            'new_password_confirmation' => 'muhyi12345',
        ]);

        $response->assertRedirect(route('dashboard'));

        // Cek database
        $user->refresh();
        $this->assertEquals('muhyi_rpl', $user->username);
        $this->assertFalse($user->must_change_credentials);

        // Coba login ulang dengan kredensial baru
        $this->post('/logout');

        $loginNew = $this->post('/login', [
            'username' => 'muhyi_rpl',
            'password' => 'muhyi12345',
        ]);

        $this->assertAuthenticated();
        $loginNew->assertRedirect('/siswa/dashboard');
    }

    /**
     * Test 4: Akun nonaktif tidak diizinkan masuk
     */
    public function test_inactive_account_cannot_login(): void
    {
        $user = User::where('username', '202611002')->first();
        $user->update(['is_active' => false]);

        $response = $this->post('/login', [
            'username' => '202611002',
            'password' => 'smkn1bantul2026',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('username');
    }

    /**
     * Test 5: Guru login berhasil masuk ke Dashboard Guru
     */
    public function test_guru_can_login_to_guru_dashboard(): void
    {
        $response = $this->post('/login', [
            'username' => 'guru1',
            'password' => 'smkn1bantul2026',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/guru/dashboard');
    }
}

