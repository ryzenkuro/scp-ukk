<?php

namespace Tests\Feature;

use App\Models\Journal;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\SystemConfiguration;
use App\Models\User;
use Tests\TestCase;

class OverdueJournalAlertTest extends TestCase
{
    public function test_overdue_journal_alert_on_teacher_and_waka_dashboards(): void
    {
        $guru1 = User::where('username', 'guru1')->first();
        $waka = User::where('username', 'waka')->first();
        $class1 = SchoolClass::first();
        $room = Room::first();

        $config = SystemConfiguration::current();
        $config->update(['active_cycle' => 'Putaran 1']);

        $currentDayName = \App\Support\IndonesianDay::now();

        // Bersihkan data test lama jika ada
        $oldTestSchedules = Schedule::where('subject', 'Sistem Komputer Terapan')->get();
        foreach ($oldTestSchedules as $old) {
            Journal::where('schedule_id', $old->id)->delete();
            $old->delete();
        }

        // Tentukan jam berakhir yang sudah lewat >30 menit dari sekarang
        $overdueEndTime = now()->subMinutes(45)->format('H:i:s');
        $overdueStartTime = now()->subMinutes(120)->format('H:i:s');
        $today = now()->toDateString();

        // 1. Buat jadwal yang jam selesainya telah lewat >30 menit dan belum ada jurnal
        $schedule = Schedule::create([
            'teacher_id' => $guru1->id,
            'class_id' => $class1->id,
            'room_id' => $room->id,
            'cycle_type' => 'Putaran 1',
            'day_of_week' => $currentDayName,
            'start_time' => $overdueStartTime,
            'end_time' => $overdueEndTime,
            'subject' => 'Sistem Komputer Terapan',
        ]);

        // 2. Guru membuka dashboard -> Wajib Muncul Data Peringatan Keterlambatan
        $responseGuru = $this->actingAs($guru1)->get(route('guru.dashboard'));
        $responseGuru->assertStatus(200);
        $responseGuru->assertSee('&quot;overdueSchedules&quot;', false);
        $responseGuru->assertSee('Sistem Komputer Terapan');

        // 3. Waka Kurikulum membuka dashboard -> Terlihat di Rekap Keterlambatan
        $responseWaka = $this->actingAs($waka)->get(route('waka.dashboard'));
        $responseWaka->assertStatus(200);
        $responseWaka->assertSee('&quot;overdueSchedules&quot;', false);
        $responseWaka->assertSee('Sistem Komputer Terapan');

        // 4. Guru mengisi jurnal untuk jadwal tersebut
        Journal::create([
            'schedule_id' => $schedule->id,
            'date' => $today,
            'topic_description' => 'Materi telah selesai disampaikan tepat waktu.',
            'photo_path' => 'journals_photos/test.jpg',
        ]);

        // 5. Guru memuat ulang dashboard -> Peringatan untuk jadwal ini sudah tidak ada lagi
        $responseGuruAfter = $this->actingAs($guru1)->get(route('guru.dashboard'));
        $responseGuruAfter->assertStatus(200);
        
        // Verifikasi bahwa jadwal 'Sistem Komputer Terapan' sekarang memiliki entri journal
        $responseGuruAfter->assertSee('Sistem Komputer Terapan');
        $responseGuruAfter->assertSee('&quot;journals&quot;:[{&quot;id&quot;:', false);

        // Bersihkan entri dummy setelah pengujian
        Journal::where('schedule_id', $schedule->id)->delete();
        $schedule->delete();
    }
}
