<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleLoginRedirectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['id' => 1], ['name' => 'waka', 'label' => 'Waka Kurikulum / Administrator']);
        Role::firstOrCreate(['id' => 2], ['name' => 'guru', 'label' => 'Tenaga Pendidik / Guru']);
        Role::firstOrCreate(['id' => 3], ['name' => 'siswa', 'label' => 'Siswa / Murid']);
        Role::firstOrCreate(['id' => 4], ['name' => 'kesiswaan', 'label' => 'Tim Kesiswaan']);
        Role::firstOrCreate(['id' => 5], ['name' => 'wali_kelas', 'label' => 'Wali Kelas']);
        Role::firstOrCreate(['id' => 6], ['name' => 'orang_tua', 'label' => 'Orang Tua Murid']);
    }

    public function test_kesiswaan_user_redirects_directly_to_kesiswaan_dashboard_on_first_login(): void
    {
        $user = User::factory()->create([
            'role_id' => 4,
            'username' => 'gatau',
            'password' => bcrypt('gatau12345'),
            'must_change_credentials' => false,
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'username' => 'gatau',
            'password' => 'gatau12345',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('kesiswaan.dashboard', absolute: false));

        // Pastikan tidak menghasilkan 403 saat membuka dashboard kesiswaan
        $followResponse = $this->actingAs($user)->get(route('kesiswaan.dashboard'));
        $followResponse->assertStatus(200);
    }

    public function test_kesiswaan_ignores_unauthorized_waka_intended_url_and_avoids_403(): void
    {
        $user = User::factory()->create([
            'role_id' => 4,
            'username' => 'petugas_kesiswaan',
            'password' => bcrypt('password123'),
            'must_change_credentials' => false,
            'is_active' => true,
        ]);

        // Simulasikan session guest yang sebelumnya sempat mengakses halaman Waka
        $response = $this->withSession(['url.intended' => '/waka/dashboard'])
            ->post('/login', [
                'username' => 'petugas_kesiswaan',
                'password' => 'password123',
            ]);

        $this->assertAuthenticatedAs($user);
        // Harus dialihkan ke kesiswaan.dashboard, bukan ke waka.dashboard
        $response->assertRedirect(route('kesiswaan.dashboard', absolute: false));
    }

    public function test_waka_user_redirects_to_waka_dashboard(): void
    {
        $user = User::factory()->create([
            'role_id' => 1,
            'username' => 'admin',
            'password' => bcrypt('password123'),
            'must_change_credentials' => false,
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'username' => 'admin',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('waka.dashboard', absolute: false));
    }

    public function test_wali_kelas_user_redirects_to_wali_kelas_dashboard(): void
    {
        $user = User::factory()->create([
            'role_id' => 5,
            'username' => 'walikelas1',
            'password' => bcrypt('password123'),
            'must_change_credentials' => false,
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'username' => 'walikelas1',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('wali-kelas.dashboard', absolute: false));
    }

    public function test_guru_user_redirects_to_guru_dashboard(): void
    {
        $user = User::factory()->create([
            'role_id' => 2,
            'username' => 'guru_matpel',
            'password' => bcrypt('password123'),
            'must_change_credentials' => false,
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'username' => 'guru_matpel',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('guru.dashboard', absolute: false));
    }
}

