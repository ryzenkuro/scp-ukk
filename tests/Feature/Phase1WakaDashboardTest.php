<?php

namespace Tests\Feature;

use App\Models\SystemConfiguration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase1WakaDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_waka_can_toggle_cycle(): void
    {
        $waka = User::where('username', 'admin')->first();
        $config = SystemConfiguration::current();
        $this->assertEquals(1, $config->active_cycle);

        $response = $this->actingAs($waka)->post('/waka/config/toggle-cycle');
        $response->assertSessionHas('success');

        $config->refresh();
        $this->assertEquals(2, $config->active_cycle);

        // Toggle back to cycle 1
        $this->actingAs($waka)->post('/waka/config/toggle-cycle');
        $config->refresh();
        $this->assertEquals(1, $config->active_cycle);
    }

    public function test_waka_can_toggle_lock(): void
    {
        $waka = User::where('username', 'admin')->first();
        $config = SystemConfiguration::current();
        $this->assertFalse($config->is_scheduled_locked);

        $response = $this->actingAs($waka)->post('/waka/config/toggle-lock');
        $response->assertSessionHas('success');

        $config->refresh();
        $this->assertTrue($config->is_scheduled_locked);

        // Unlock
        $this->actingAs($waka)->post('/waka/config/toggle-lock');
        $config->refresh();
        $this->assertFalse($config->is_scheduled_locked);
    }

    public function test_non_waka_cannot_access_waka_dashboard(): void
    {
        $guru = User::where('username', 'guru1')->first();

        $response = $this->actingAs($guru)->get('/waka/dashboard');
        $response->assertStatus(403);
    }

    public function test_waka_can_advance_academic_year_to_next_period(): void
    {
        $waka = User::where('username', 'admin')->first();
        $config = SystemConfiguration::current();
        $config->update(['academic_year' => '2026/2027']);

        $response = $this->actingAs($waka)->post('/waka/config/advance-academic-year');
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $config->refresh();
        $this->assertEquals('2027/2028', $config->academic_year);

        // Advance again
        $response = $this->actingAs($waka)->post('/waka/config/advance-academic-year');
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $config->refresh();
        $this->assertEquals('2028/2029', $config->academic_year);
    }

    public function test_academic_year_update_needed_logic_and_dashboard_props(): void
    {
        $waka = User::where('username', 'admin')->first();
        $config = SystemConfiguration::current();

        // 1. Saat tahun DB sama dengan tahun berjalan (2026/2027) -> needs_year_update = false (abu-abu/disabled)
        $config->update(['academic_year' => '2026/2027']);
        $this->assertFalse($config->isAcademicYearUpdateNeeded());

        $response = $this->actingAs($waka)->get('/waka/dashboard');
        $response->assertOk();
        $response->assertInertia(fn ($page) => 
            $page->component('Waka/Dashboard')
                ->where('config.academic_year', '2026/2027')
                ->where('config.needs_year_update', false)
        );

        // 2. Saat tahun DB tertinggal (2025/2026) -> needs_year_update = true (merah/aktif)
        $config->update(['academic_year' => '2025/2026']);
        $this->assertTrue($config->isAcademicYearUpdateNeeded());

        $response = $this->actingAs($waka)->get('/waka/dashboard');
        $response->assertOk();
        $response->assertInertia(fn ($page) => 
            $page->component('Waka/Dashboard')
                ->where('config.academic_year', '2025/2026')
                ->where('config.needs_year_update', true)
        );
    }
}

