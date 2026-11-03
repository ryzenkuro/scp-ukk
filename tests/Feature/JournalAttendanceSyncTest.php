<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Journal;
use App\Models\LeaveRequest;
use App\Models\Role;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\SystemConfiguration;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class JournalAttendanceSyncTest extends TestCase
{
    public function test_journal_submission_and_attendance_auto_sync(): void
    {
        Storage::fake('public');

        $guru1 = User::where('username', 'guru1')->first();
        $siswa1 = User::where('username', 'siswa1')->first();
        $siswa2 = User::where('username', 'siswa2')->first();
        $class1 = SchoolClass::where('class_name', 'XII RPL 1')->first();
        $room = Room::first();

        // Pastikan konfigurasi aktif pada Putaran 1
        $config = SystemConfiguration::current();
        $config->update(['active_cycle' => 'Putaran 1']);

        // Buat jadwal untuk guru1 di kelas 1 jika belum ada
        $schedule = Schedule::firstOrCreate([
            'teacher_id' => $guru1->id,
            'class_id' => $class1->id,
            'room_id' => $room->id,
            'cycle_type' => 'Putaran 1',
            'day_of_week' => 'Senin',
            'start_time' => '07:00:00',
            'end_time' => '09:15:00',
        ], [
            'subject' => 'Pemrograman Web Lanjut',
        ]);

        $today = now()->toDateString();

        // Bersihkan entri journal lama untuk schedule ini hari ini jika ada dari run sebelumnya
        $oldJournal = Journal::where('schedule_id', $schedule->id)->where('date', $today)->first();
        if ($oldJournal) {
            Attendance::where('journal_id', $oldJournal->id)->delete();
            $oldJournal->delete();
        }

        // 1. Buat izin resmi yang sudah Approved untuk siswa1 hari ini
        LeaveRequest::where('student_id', $siswa1->id)->where('start_date', '<=', $today)->where('end_date', '>=', $today)->delete();
        
        $leaveApproved = LeaveRequest::create([
            'student_id' => $siswa1->id,
            'applicant_id' => $siswa1->id,
            'start_date' => $today,
            'end_date' => $today,
            'reason' => 'Sakit demam dan pilek',
            'document_path' => 'leave_documents/surat_dokter.png',
            'status_approval' => 'Approved',
        ]);

        // 2. Akses halaman index jurnal & form create
        $responseIndex = $this->actingAs($guru1)->get(route('guru.journals.index'));
        $responseIndex->assertStatus(200);

        $responseCreate = $this->actingAs($guru1)->get(route('guru.journals.create', $schedule->id));
        $responseCreate->assertStatus(200);
        $responseCreate->assertSee('&quot;approvedLeaves&quot;', false);

        // 3. Simpan Jurnal dengan input attendances (Guru mencoba memilih Hadir untuk siswa1)
        $fakePhoto = UploadedFile::fake()->image('kegiatan_kelas.jpg', 600, 400);

        $responseStore = $this->actingAs($guru1)->post(route('guru.journals.store', $schedule->id), [
            'topic_description' => 'Mempelajari dasar MVC dan integrasi API pada Laravel 11.',
            'photo' => $fakePhoto,
            'attendances' => [
                $siswa1->id => 'Hadir', // Akan di-override menjadi Sakit oleh Auto-Sync Logic!
                $siswa2->id => 'Hadir',
            ],
        ]);

        $responseStore->assertRedirect();

        // 4. Verifikasi data Journal tersimpan
        $this->assertDatabaseHas('journals', [
            'schedule_id' => $schedule->id,
            'date' => $today,
            'topic_description' => 'Mempelajari dasar MVC dan integrasi API pada Laravel 11.',
        ]);

        $journal = Journal::where('schedule_id', $schedule->id)->where('date', $today)->first();
        $this->assertNotNull($journal);
        Storage::disk('public')->assertExists($journal->photo_path);

        // 5. Verifikasi Auto-Sync Presensi:
        // - Siswa 1 harus berstatus 'Sakit' (auto-sync dari leave Approved 'Sakit demam')
        // - Siswa 2 harus berstatus 'Hadir'
        $this->assertDatabaseHas('attendances', [
            'journal_id' => $journal->id,
            'student_id' => $siswa1->id,
            'status' => 'Sakit',
            'date' => $today,
        ]);

        $this->assertDatabaseHas('attendances', [
            'journal_id' => $journal->id,
            'student_id' => $siswa2->id,
            'status' => 'Hadir',
            'date' => $today,
        ]);

        // 6. Guru mengakses halaman detail jurnal
        $responseShow = $this->actingAs($guru1)->get(route('guru.journals.show', $journal->id));
        $responseShow->assertStatus(200);
        $responseShow->assertSee('Mempelajari dasar MVC');
        $responseShow->assertSee('Sakit');
        $responseShow->assertSee('Hadir');

        // 7. Guru lain tidak boleh melihat jurnal milik guru1 (403 Forbidden)
        $guru2 = User::where('username', 'guru2')->first();
        $responseUnauthorized = $this->actingAs($guru2)->get(route('guru.journals.show', $journal->id));
        $responseUnauthorized->assertStatus(403);
    }

    /**
     * Test Arah 2: Retroactive Sync Presensi (BR-004)
     * Ketika guru sudah mengisi jurnal dan siswa tercatat 'Alpha',
     * lalu surat izin disetujui kemudian oleh Wali Kelas/BK,
     * status presensi otomatis terkoreksi menjadi Sakit/Izin.
     */
    public function test_retroactive_attendance_sync_when_leave_is_approved_after_journal(): void
    {
        Storage::fake('public');

        $guru1 = User::where('username', 'guru1')->first();
        $siswa1 = User::where('username', 'siswa1')->first();
        $walikelas = User::where('username', 'walikelas')->first();
        $class1 = SchoolClass::where('class_name', 'XII RPL 1')->first();
        $room = Room::first();

        $schedule = Schedule::firstOrCreate([
            'teacher_id' => $guru1->id,
            'class_id' => $class1->id,
            'room_id' => $room->id,
            'cycle_type' => 'Putaran 1',
            'day_of_week' => 'Senin',
            'start_time' => '10:00:00',
            'end_time' => '12:15:00',
        ], [
            'subject' => 'Basis Data Lanjut',
        ]);

        $targetDate = now()->addDays(5)->toDateString();

        // 1. Bersihkan data pengujian tanggal target
        LeaveRequest::where('student_id', $siswa1->id)->where('start_date', '<=', $targetDate)->where('end_date', '>=', $targetDate)->delete();
        $oldJournal = Journal::where('schedule_id', $schedule->id)->where('date', $targetDate)->first();
        if ($oldJournal) {
            Attendance::where('journal_id', $oldJournal->id)->delete();
            $oldJournal->delete();
        }

        // 2. Guru mencatat jurnal dan siswa1 tercatat Alpha (karena belum ada izin disetujui)
        $journal = Journal::create([
            'schedule_id' => $schedule->id,
            'date' => $targetDate,
            'topic_description' => 'Normalisasi Basis Data Bentuk Ketiga (3NF)',
            'photo_path' => 'journals_photos/sample.jpg',
        ]);

        $attendance = Attendance::create([
            'journal_id' => $journal->id,
            'student_id' => $siswa1->id,
            'status' => 'Alpha',
            'date' => $targetDate,
        ]);

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'student_id' => $siswa1->id,
            'status' => 'Alpha',
            'date' => $targetDate,
        ]);

        // 3. Siswa mengajukan izin Sakit pada targetDate
        $leave = LeaveRequest::create([
            'student_id' => $siswa1->id,
            'applicant_id' => $siswa1->id,
            'leave_type' => 'Sakit',
            'start_date' => $targetDate,
            'end_date' => $targetDate,
            'reason' => 'Sakit tifus rawat inap',
            'document_path' => 'leave_documents/surat_rs.pdf',
            'status_approval' => 'Pending',
        ]);

        // 4. Wali kelas menyetujui izin siswa1
        $responseApprove = $this->actingAs($walikelas)->patch(route('kesiswaan.leave-requests.verify', $leave->id), [
            'status' => 'Approved',
        ]);

        $responseApprove->assertRedirect();
        $this->assertDatabaseHas('leave_requests', [
            'id' => $leave->id,
            'status_approval' => 'Approved',
            'verified_by' => $walikelas->id,
        ]);

        // 5. Verifikasi bahwa status presensi yang tadinya Alpha telah otomatis terkoreksi menjadi 'Sakit' (Retroactive Sync BR-004)
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'student_id' => $siswa1->id,
            'status' => 'Sakit',
            'date' => $targetDate,
        ]);
    }
}
