<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\SystemConfiguration;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ScheduleConflictTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_anti_bentrok_dan_lock_schedule(): void
    {
        $guruRole = Role::where('role_name', 'guru')->first();
        $guru1 = User::where('username', 'guru1')->first();
        $guru2 = User::where('username', 'guru2')->first();
        $class1 = SchoolClass::first();
        $class2 = SchoolClass::skip(1)->first();
        $room1 = Room::first();
        $room2 = Room::skip(1)->first();

        // 1. Bersihkan schedule untuk pengujian
        Schedule::query()->delete();

        // Pastikan lock false
        SystemConfiguration::current()->update(['is_schedule_locked' => false]);

        // 2. Guru 1 membuat jadwal di Room 1, Class 1 pada Senin 08:00 - 10:00 Putaran 1
        $response = $this->actingAs($guru1)->post(route('guru.schedules.store'), [
            'class_id' => $class1->id,
            'room_id' => $room1->id,
            'subject' => 'Basis Data',
            'day_of_week' => 'Senin',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'cycle_type' => 'Putaran 1',
        ]);
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('schedules', ['subject' => 'Basis Data']);

        // 3. Guru 2 coba buat jadwal di RUANG YANG SAMA pada jam beririsan (09:00 - 11:00) di Putaran 1 -> WAJIB BENTROK
        $conflictRoomResponse = $this->actingAs($guru2)->post(route('guru.schedules.store'), [
            'class_id' => $class2->id,
            'room_id' => $room1->id,
            'subject' => 'Pemrograman Berorientasi Objek',
            'day_of_week' => 'Senin',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'cycle_type' => 'Putaran 1',
        ]);
        $conflictRoomResponse->assertSessionHasErrors('conflict');

        // 4. Guru 2 coba buat jadwal di KELAS YANG SAMA pada jam beririsan (09:00 - 11:00) di Putaran 1 -> WAJIB BENTROK
        $conflictClassResponse = $this->actingAs($guru2)->post(route('guru.schedules.store'), [
            'class_id' => $class1->id,
            'room_id' => $room2->id,
            'subject' => 'Pemrograman Berorientasi Objek',
            'day_of_week' => 'Senin',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'cycle_type' => 'Putaran 1',
        ]);
        $conflictClassResponse->assertSessionHasErrors('conflict');

        // 5. Guru 2 input pada jam & ruang yang sama tetapi di PUTARAN 2 -> WAJIB BERHASIL
        $validDiffCycleResponse = $this->actingAs($guru2)->post(route('guru.schedules.store'), [
            'class_id' => $class2->id,
            'room_id' => $room1->id,
            'subject' => 'Desain Grafis',
            'day_of_week' => 'Senin',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'cycle_type' => 'Putaran 2',
        ]);
        $validDiffCycleResponse->assertSessionHas('success');

        // 6. Uji Lock Schedule: Jika dikunci, input baru ditolak
        SystemConfiguration::current()->update(['is_schedule_locked' => true]);

        $lockedResponse = $this->actingAs($guru1)->post(route('guru.schedules.store'), [
            'class_id' => $class2->id,
            'room_id' => $room2->id,
            'subject' => 'Jaringan Dasar',
            'day_of_week' => 'Selasa',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'cycle_type' => 'Putaran 1',
        ]);
        $lockedResponse->assertSessionHasErrors('locked');

        // Kembalikan lock ke false
        SystemConfiguration::current()->update(['is_schedule_locked' => false]);
    }
}
