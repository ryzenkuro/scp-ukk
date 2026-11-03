<?php

namespace Tests\Feature;

use App\Models\Journal;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\SystemConfiguration;
use App\Models\User;
use App\Services\JournalReminderService;
use Tests\TestCase;

class JournalReminderServiceTest extends TestCase
{
    public function test_journal_reminder_service_detects_overdue_and_respects_tolerances(): void
    {
        $guru1 = User::where('username', 'guru1')->first();
        $guru2 = User::where('username', 'guru2')->first();
        $class1 = SchoolClass::first();
        $room = Room::first();

        $config = SystemConfiguration::current();
        $config->update(['active_cycle' => 'Putaran 1']);

        $currentDayName = \App\Support\IndonesianDay::now();
        $today = now()->toDateString();

        // 1. Bersihkan jadwal testing sebelumnya
        $old = Schedule::whereIn('subject', ['Mapel Overdue Test', 'Mapel Fresh Test'])->get();
        foreach ($old as $s) {
            Journal::where('schedule_id', $s->id)->delete();
            $s->delete();
        }

        // 2. Jadwal 1: Selesai 10 menit lalu (< 30 menit toleransi) -> TIDAK OVERDUE
        $freshSchedule = Schedule::create([
            'teacher_id' => $guru1->id,
            'class_id' => $class1->id,
            'room_id' => $room->id,
            'cycle_type' => 'Putaran 1',
            'day_of_week' => $currentDayName,
            'start_time' => now()->subMinutes(60)->format('H:i:s'),
            'end_time' => now()->subMinutes(10)->format('H:i:s'),
            'subject' => 'Mapel Fresh Test',
        ]);

        // 3. Jadwal 2: Selesai 40 menit lalu (> 30 menit) belum ada jurnal -> OVERDUE
        $overdueSchedule = Schedule::create([
            'teacher_id' => $guru1->id,
            'class_id' => $class1->id,
            'room_id' => $room->id,
            'cycle_type' => 'Putaran 1',
            'day_of_week' => $currentDayName,
            'start_time' => now()->subMinutes(100)->format('H:i:s'),
            'end_time' => now()->subMinutes(40)->format('H:i:s'),
            'subject' => 'Mapel Overdue Test',
        ]);

        // Uji query service untuk guru1
        $overdueGuru1 = JournalReminderService::getOverdueSchedules($guru1->id);
        $this->assertTrue($overdueGuru1->contains('id', $overdueSchedule->id));
        $this->assertFalse($overdueGuru1->contains('id', $freshSchedule->id));

        // Uji query service untuk guru2 (tidak boleh melihat overdue milik guru1)
        $overdueGuru2 = JournalReminderService::getOverdueSchedules($guru2->id);
        $this->assertFalse($overdueGuru2->contains('id', $overdueSchedule->id));

        // Uji query service tanpa teacherId (untuk Waka/Admin) -> melihat overdueSchedule
        $overdueAll = JournalReminderService::getOverdueSchedules();
        $this->assertTrue($overdueAll->contains('id', $overdueSchedule->id));

        // Begitu jurnal dibuat, jadwal langsung keluar dari daftar overdue (KF68)
        Journal::create([
            'schedule_id' => $overdueSchedule->id,
            'date' => $today,
            'topic_description' => 'Materi telah dicatat.',
            'photo_path' => 'dummy.jpg',
        ]);

        $overdueAfter = JournalReminderService::getOverdueSchedules($guru1->id);
        $this->assertFalse($overdueAfter->contains('id', $overdueSchedule->id));

        // Bersihkan
        Journal::whereIn('schedule_id', [$freshSchedule->id, $overdueSchedule->id])->delete();
        $freshSchedule->delete();
        $overdueSchedule->delete();
    }
}
