<?php

namespace Tests\Feature;

use App\Models\TimeSlot;
use App\Models\User;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Room;
use App\Models\SystemConfiguration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase2TimeSlotAndDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_waka_can_view_jam_pelajaran_index(): void
    {
        $waka = User::where('role_id', 1)->first();

        $response = $this->actingAs($waka)->get('/waka/jam-pelajaran');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Waka/JamPelajaran/Index')
            ->has('timeSlots')
        );
    }

    public function test_waka_can_create_new_time_slot(): void
    {
        $waka = User::where('role_id', 1)->first();

        $response = $this->actingAs($waka)->post('/waka/jam-pelajaran', [
            'name' => 'Jam Ke-9',
            'start_time' => '14:30',
            'end_time' => '15:15',
            'is_break' => false,
            'order' => 9,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('time_slots', [
            'name' => 'Jam Ke-9',
            'start_time' => '14:30',
            'end_time' => '15:15',
            'is_break' => 0,
            'order' => 9,
        ]);
    }

    public function test_waka_can_update_time_slot(): void
    {
        $waka = User::where('role_id', 1)->first();
        $slot = TimeSlot::where('order', 1)->first();

        $response = $this->actingAs($waka)->put("/waka/jam-pelajaran/{$slot->id}", [
            'name' => 'Jam Pertama (Revisi)',
            'start_time' => '07:15',
            'end_time' => '08:00',
            'is_break' => false,
            'order' => 1,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('time_slots', [
            'id' => $slot->id,
            'name' => 'Jam Pertama (Revisi)',
            'start_time' => '07:15',
        ]);
    }

    public function test_waka_can_delete_time_slot(): void
    {
        $waka = User::where('role_id', 1)->first();
        $slot = TimeSlot::create([
            'name' => 'Jam Tambahan',
            'start_time' => '16:00',
            'end_time' => '16:45',
            'is_break' => false,
            'order' => 10,
        ]);

        $response = $this->actingAs($waka)->delete("/waka/jam-pelajaran/{$slot->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('time_slots', [
            'id' => $slot->id,
        ]);
    }

    public function test_waka_dashboard_renders_operational_data(): void
    {
        $waka = User::where('role_id', 1)->first();

        $response = $this->actingAs($waka)->get('/waka/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Waka/Dashboard')
            ->has('stats')
            ->has('config')
            ->has('todaySchedules')
            ->has('attendanceSummary')
            ->has('currentDay')
        );
    }
}

