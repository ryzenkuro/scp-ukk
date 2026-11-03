<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Journal;
use App\Models\LeaveRequest;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\User;
use App\Services\AttendanceService;
use Tests\TestCase;

class AttendanceServiceTest extends TestCase
{
    public function test_record_attendance_locks_approved_leave_and_defaults_others(): void
    {
        $guru = User::where('username', 'guru1')->first();
        $siswa1 = User::where('username', 'siswa1')->first();
        $siswa2 = User::where('username', 'siswa2')->first();
        $class = SchoolClass::where('class_name', 'XII RPL 1')->first();
        $room = Room::first();

        $schedule = Schedule::firstOrCreate([
            'teacher_id' => $guru->id,
            'class_id' => $class->id,
            'room_id' => $room->id,
            'cycle_type' => 'Putaran 1',
            'day_of_week' => 'Senin',
            'start_time' => '07:00:00',
            'end_time' => '09:00:00',
        ], ['subject' => 'Pemrograman']);

        $testDate = '2026-11-01';

        // Bersihkan data lama
        LeaveRequest::where('student_id', $siswa1->id)->where('start_date', '<=', $testDate)->where('end_date', '>=', $testDate)->delete();
        $oldJournal = Journal::where('schedule_id', $schedule->id)->where('date', $testDate)->first();
        if ($oldJournal) {
            Attendance::where('journal_id', $oldJournal->id)->delete();
            $oldJournal->delete();
        }

        // Buat izin Approved untuk siswa1
        LeaveRequest::create([
            'student_id' => $siswa1->id,
            'applicant_id' => $siswa1->id,
            'leave_type' => 'Sakit',
            'start_date' => $testDate,
            'end_date' => $testDate,
            'reason' => 'Demam tinggi',
            'document_path' => 'dummy.pdf',
            'status_approval' => 'Approved',
        ]);

        $journal = Journal::create([
            'schedule_id' => $schedule->id,
            'date' => $testDate,
            'topic_description' => 'Service Layer Architecture',
            'photo_path' => 'dummy.jpg',
        ]);

        $service = new AttendanceService();
        // Guru mencoba submit siswa1 sebagai 'Hadir' dan siswa2 sebagai 'Alpha'
        $service->recordAttendance($journal, [
            $siswa1->id => 'Hadir',
            $siswa2->id => 'Alpha',
        ], $testDate);

        // Siswa 1 harus terkunci 'Sakit', Siswa 2 tetap 'Alpha'
        $att1 = Attendance::where('journal_id', $journal->id)->where('student_id', $siswa1->id)->first();
        $att2 = Attendance::where('journal_id', $journal->id)->where('student_id', $siswa2->id)->first();

        $this->assertEquals('Sakit', $att1->status);
        $this->assertEquals('Alpha', $att2->status);
    }

    public function test_sync_retroactive_approved_leave_multi_day(): void
    {
        $guru = User::where('username', 'guru1')->first();
        $siswa1 = User::where('username', 'siswa1')->first();
        $class = SchoolClass::where('class_name', 'XII RPL 1')->first();
        $room = Room::first();

        $schedule = Schedule::firstOrCreate([
            'teacher_id' => $guru->id,
            'class_id' => $class->id,
            'room_id' => $room->id,
            'cycle_type' => 'Putaran 1',
            'day_of_week' => 'Senin',
            'start_time' => '07:00:00',
            'end_time' => '09:00:00',
        ], ['subject' => 'Pemrograman']);

        $dateDay1 = '2026-11-10';
        $dateDay2 = '2026-11-11';

        // Bersihkan
        LeaveRequest::where('student_id', $siswa1->id)->whereBetween('start_date', [$dateDay1, $dateDay2])->delete();

        // Buat 2 jurnal pada dua hari berturut-turut di mana siswa1 tercatat Alpha
        $j1 = Journal::firstOrCreate(['schedule_id' => $schedule->id, 'date' => $dateDay1], [
            'topic_description' => 'Hari 1',
            'photo_path' => 'dummy1.jpg',
        ]);
        $j2 = Journal::firstOrCreate(['schedule_id' => $schedule->id, 'date' => $dateDay2], [
            'topic_description' => 'Hari 2',
            'photo_path' => 'dummy2.jpg',
        ]);

        $att1 = Attendance::updateOrCreate(
            ['journal_id' => $j1->id, 'student_id' => $siswa1->id],
            ['status' => 'Alpha', 'date' => $dateDay1]
        );
        $att2 = Attendance::updateOrCreate(
            ['journal_id' => $j2->id, 'student_id' => $siswa1->id],
            ['status' => 'Alpha', 'date' => $dateDay2]
        );

        $this->assertEquals('Alpha', $att1->fresh()->status);
        $this->assertEquals('Alpha', $att2->fresh()->status);

        // Buat surat izin rentang 2 hari bertipe 'Izin'
        $leave = LeaveRequest::create([
            'student_id' => $siswa1->id,
            'applicant_id' => $siswa1->id,
            'leave_type' => 'Izin',
            'start_date' => $dateDay1,
            'end_date' => $dateDay2,
            'reason' => 'Urusan keluarga mendesak',
            'document_path' => 'dummy.pdf',
            'status_approval' => 'Approved',
        ]);

        $service = new AttendanceService();
        $updatedCount = $service->syncRetroactiveApprovedLeave($leave);

        $this->assertEquals(2, $updatedCount);
        $this->assertEquals('Izin', $att1->fresh()->status);
        $this->assertEquals('Izin', $att2->fresh()->status);
    }
}
