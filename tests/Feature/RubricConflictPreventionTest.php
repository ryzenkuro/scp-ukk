<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\SystemConfiguration;
use App\Models\User;
use Tests\TestCase;

class RubricConflictPreventionTest extends TestCase
{
    public function test_rubric_conflict_prevention_logic(): void
    {
        $guru1 = User::where('username', 'guru1')->first();
        $guru2 = User::where('username', 'guru2')->first();
        $class1 = SchoolClass::where('class_name', 'XII RPL 1')->first();
        $class2 = SchoolClass::where('class_name', 'XII RPL 2')->first();
        $room = Room::first();

        // Pastikan konfigurasi jadwal tidak dikunci
        SystemConfiguration::current()->update(['is_schedule_locked' => false]);

        // Bersihkan jadwal uji coba lama jika ada
        Schedule::whereIn('subject', ['Mapel Uji 1', 'Mapel Uji 2', 'Mapel Uji Putaran 2'])->delete();

        // 1. Jadwal Awal: Guru 1 menjadwalkan Mapel di Lab RPL 1, Senin, 08:00 - 10:00, Putaran 1
        $schedule1 = Schedule::create([
            'teacher_id' => $guru1->id,
            'class_id' => $class1->id,
            'room_id' => $room->id,
            'cycle_type' => 'Putaran 1',
            'day_of_week' => 'Senin',
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'subject' => 'Mapel Uji 1',
        ]);

        // 2. Guru 2 mencoba menjadwalkan di ruangan yang sama (Lab RPL 1) pada jam bertabrakan (09:00 - 11:00), Putaran 1
        // Wajib DITOLAK karena bentrok ruangan!
        $responseConflictRoom = $this->actingAs($guru2)->post(route('guru.schedules.store'), [
            'class_id' => $class2->id,
            'room_id' => $room->id,
            'cycle_type' => 'Putaran 1',
            'day_of_week' => 'Senin',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'subject' => 'Mapel Uji 2',
        ]);

        $responseConflictRoom->assertSessionHasErrors(['conflict']);
        $this->assertDatabaseMissing('schedules', [
            'subject' => 'Mapel Uji 2',
        ]);

        // 3. Guru 2 mencoba menjadwalkan di ruangan yang sama pada jam bertabrakan, TETAPI di Putaran 2
        // Wajib BERHASIL karena beda siklus putaran!
        $responseSuccessCycle2 = $this->actingAs($guru2)->post(route('guru.schedules.store'), [
            'class_id' => $class2->id,
            'room_id' => $room->id,
            'cycle_type' => 'Putaran 2',
            'day_of_week' => 'Senin',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'subject' => 'Mapel Uji Putaran 2',
        ]);

        $responseSuccessCycle2->assertSessionHasNoErrors();
        $this->assertDatabaseHas('schedules', [
            'subject' => 'Mapel Uji Putaran 2',
            'cycle_type' => 'Putaran 2',
        ]);

        // Bersihkan jadwal uji coba
        Schedule::whereIn('subject', ['Mapel Uji 1', 'Mapel Uji 2', 'Mapel Uji Putaran 2'])->delete();
    }
}
