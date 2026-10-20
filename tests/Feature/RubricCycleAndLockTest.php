<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\SchoolClass;
use App\Models\SystemConfiguration;
use App\Models\User;
use Tests\TestCase;

class RubricCycleAndLockTest extends TestCase
{
    public function test_cycle_toggle_and_schedule_lock_protections(): void
    {
        $waka = User::where('username', 'waka')->first();
        $guru1 = User::where('username', 'guru1')->first();
        $class1 = SchoolClass::first();
        $room = Room::first();

        $config = SystemConfiguration::current();

        // 1. Uji Toggle Siklus Putaran (Putaran 1 -> Putaran 2)
        $config->update(['active_cycle' => 'Putaran 1', 'is_schedule_locked' => false]);

        $responseToggle = $this->actingAs($waka)->patch(route('waka.config.toggle-cycle'));
        $responseToggle->assertRedirect();
        $this->assertEquals('Putaran 2', SystemConfiguration::current()->active_cycle);

        // Kembalikan ke Putaran 1
        $this->actingAs($waka)->patch(route('waka.config.toggle-cycle'));
        $this->assertEquals('Putaran 1', SystemConfiguration::current()->active_cycle);

        // 2. Uji Kunci Jadwal (Lock Schedule)
        $responseLock = $this->actingAs($waka)->patch(route('waka.config.toggle-lock'));
        $responseLock->assertRedirect();
        $this->assertTrue(SystemConfiguration::current()->is_schedule_locked);

        // 3. Guru mencoba menambah jadwal via Web saat terkunci -> Ditolak dengan error
        $responseWebLocked = $this->actingAs($guru1)->post(route('guru.schedules.store'), [
            'class_id' => $class1->id,
            'room_id' => $room->id,
            'cycle_type' => 'Putaran 1',
            'day_of_week' => 'Rabu',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'subject' => 'Mapel Saat Terkunci',
        ]);
        $responseWebLocked->assertSessionHasErrors(['locked']);

        // 4. Guru mencoba menambah jadwal via API/Postman saat terkunci -> Ditolak 403 Forbidden
        $responseApiLocked = $this->actingAs($guru1)->postJson(route('api.schedules.store'), [
            'class_id' => $class1->id,
            'room_id' => $room->id,
            'cycle_type' => 'Putaran 1',
            'day_of_week' => 'Rabu',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'subject' => 'Mapel API Terkunci',
        ]);
        $responseApiLocked->assertStatus(403);
        $responseApiLocked->assertJson(['status' => 'error']);

        // 5. Buka Kunci Jadwal Kembali
        $responseUnlock = $this->actingAs($waka)->patch(route('waka.config.toggle-lock'));
        $responseUnlock->assertRedirect();
        $this->assertFalse(SystemConfiguration::current()->is_schedule_locked);
    }
}
