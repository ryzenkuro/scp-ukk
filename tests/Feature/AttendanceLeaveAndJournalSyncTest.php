<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\HomeroomAssignment;
use App\Models\Journal;
use App\Models\LeaveRequest;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Siswa;
use App\Models\User;
use App\Services\AttendanceSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttendanceLeaveAndJournalSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_homeroom_teacher_has_maximum_one_binaan_class(): void
    {
        $teacher = User::where('role_id', 2)->first();
        $class1 = SchoolClass::first();
        $class2 = SchoolClass::skip(1)->first();

        // 1. Assign class1
        HomeroomAssignment::create([
            'user_id' => $teacher->id,
            'school_class_id' => $class1->id,
            'class_name' => $class1->name,
            'academic_year' => '2026/2027',
        ]);

        $this->assertEquals(1, HomeroomAssignment::where('user_id', $teacher->id)->count());

        // 2. Waka assigns class2 through assignHomeroom
        $wakaUser = User::where('username', 'waka')->first();
        $this->assertNotNull($wakaUser);
        $response = $this->actingAs($wakaUser)->post('/waka/siswa/assign-homeroom', [
            'class_id' => $class2->id,
            'teacher_id' => $teacher->id,
        ]);
        $response->assertRedirect();

        // Must still have exactly 1 homeroom assignment (class2 replaces class1)
        $this->assertEquals(1, HomeroomAssignment::where('user_id', $teacher->id)->count());
        $this->assertEquals($class2->name, HomeroomAssignment::where('user_id', $teacher->id)->value('class_name'));
    }

    public function test_leave_approval_creates_attendance_and_rejection_marks_as_alpa(): void
    {
        $student = Siswa::first();
        $wali = User::where('role_id', 2)->first();

        // Buat pengajuan izin dari Senin 2026-11-09 s.d. Rabu 2026-11-11 (3 hari aktif)
        $leave = LeaveRequest::create([
            'student_id' => $student->id,
            'type' => 'sakit',
            'start_date' => '2026-11-09',
            'end_date' => '2026-11-11',
            'reason' => 'Sakit demam flu berat',
            'status' => 'pending',
        ]);

        $this->assertEquals(0, Attendance::where('student_id', $student->id)->count());

        // Setujui izin melalui service
        $leave->update(['status' => 'approved', 'verified_by' => $wali->id, 'verified_at' => now()]);
        AttendanceSyncService::syncLeaveRequest($leave);

        // Harus membuat 3 baris presensi sakit untuk 09, 10, dan 11 Nov 2026
        $this->assertEquals(3, Attendance::where('student_id', $student->id)->where('status', 'sakit')->count());
        $this->assertDatabaseHas('attendances', [
            'student_id' => $student->id,
            'date' => '2026-11-09',
            'status' => 'sakit',
            'journal_id' => null,
        ]);

        // Jika izin kemudian dibatalkan/ditolak, harus terupdate menjadi alpa
        $leave->update(['status' => 'rejected', 'rejection_reason' => 'Surat dokter palsu']);
        AttendanceSyncService::syncLeaveRequest($leave);

        // Baris presensi harus berubah menjadi alpa
        $this->assertEquals(3, Attendance::where('student_id', $student->id)->where('status', 'alpa')->count());
        $this->assertDatabaseHas('attendances', [
            'student_id' => $student->id,
            'date' => '2026-11-09',
            'status' => 'alpa',
            'notes' => 'Izin ditolak: Surat dokter palsu',
        ]);

        // Jika status izin direset menjadi pending, presensi unattached harus dibersihkan
        $leave->update(['status' => 'pending']);
        AttendanceSyncService::syncLeaveRequest($leave);
        $this->assertEquals(0, Attendance::where('student_id', $student->id)->whereNull('journal_id')->count());
    }

    public function test_rejected_leave_updates_rekap_presensi_as_alpa(): void
    {
        $class = SchoolClass::first();
        $wali = User::where('role_id', 2)->first();
        $this->assertNotNull($wali);

        $assignment = HomeroomAssignment::firstOrCreate(
            ['user_id' => $wali->id],
            [
                'school_class_id' => $class->id,
                'class_name' => $class->name,
                'academic_year' => '2026/2027',
            ]
        );

        $student = Siswa::where('kelas', $assignment->class_name)->first();
        $this->assertNotNull($student);

        // Ajukan izin 1 hari pada hari Senin
        $leave = LeaveRequest::create([
            'student_id' => $student->id,
            'type' => 'izin',
            'start_date' => '2026-11-09',
            'end_date' => '2026-11-09',
            'reason' => 'Acara keluarga',
            'status' => 'pending',
        ]);

        // Wali kelas tolak izin
        $verifyRes = $this->actingAs($wali)->post("/wali-kelas/izin/{$leave->id}/verify", [
            'status' => 'rejected',
            'rejection_reason' => 'Alasan tidak jelas dan tanpa izin orang tua',
        ]);
        $verifyRes->assertRedirect();

        // Cek tabel attendances
        $this->assertDatabaseHas('attendances', [
            'student_id' => $student->id,
            'date' => '2026-11-09',
            'status' => 'alpa',
        ]);

        // Cek Rekap Presensi Wali Kelas
        $rekapWaliRes = $this->actingAs($wali)->get("/wali-kelas/presensi?kelas={$assignment->class_name}");
        $rekapWaliRes->assertStatus(200);
        $rekapWaliRes->assertInertia(fn ($page) => $page
            ->component('WaliKelas/Presensi')
            ->where('summary.total_alpa', fn ($val) => $val >= 1)
        );

        // Cek Rekap Presensi BK
        $bkRole = Role::where('name', 'guru_bk')->first();
        $bkUser = User::firstOrCreate(
            ['username' => 'bk_test'],
            [
                'role_id' => $bkRole ? $bkRole->id : 4,
                'fullname' => 'Guru BK Test',
                'email' => 'bk_test@smkn1bantul.sch.id',
                'password' => bcrypt('password'),
                'is_active' => true,
            ]
        );

        $rekapBkRes = $this->actingAs($bkUser)->get("/bk/presensi?kelas={$assignment->class_name}");
        $rekapBkRes->assertStatus(200);
        $rekapBkRes->assertInertia(fn ($page) => $page
            ->component('Kesiswaan/Presensi')
            ->where('summary.total_alpa', fn ($val) => $val >= 1)
        );

        // Cek Rekap Presensi Waka
        $wakaUser = User::where('username', 'waka')->first();
        $this->assertNotNull($wakaUser);
        $rekapWakaRes = $this->actingAs($wakaUser)->get("/waka/laporan/presensi?kelas={$assignment->class_name}");
        $rekapWakaRes->assertStatus(200);
        $rekapWakaRes->assertInertia(fn ($page) => $page
            ->component('Waka/Laporan/Presensi')
            ->where('summary.total_alpa', fn ($val) => $val >= 1)
        );
    }

    public function test_guru_journal_create_loads_approved_leaves_and_store_merges_unattached(): void
    {
        $guru = User::where('username', 'guru1')->first();
        $schedule = Schedule::where('teacher_id', $guru->id)->first();
        $student = Siswa::where('kelas', $schedule->class_name)->first();

        // 1. Buat izin yang disetujui untuk tanggal 2026-11-09
        $leave = LeaveRequest::create([
            'student_id' => $student->id,
            'type' => 'izin',
            'start_date' => '2026-11-09',
            'end_date' => '2026-11-09',
            'reason' => 'Keperluan keluarga penting',
            'status' => 'approved',
        ]);
        AttendanceSyncService::syncLeaveRequest($leave);

        // Verifikasi unattached attendance ada
        $this->assertDatabaseHas('attendances', [
            'student_id' => $student->id,
            'date' => '2026-11-09',
            'status' => 'izin',
            'journal_id' => null,
        ]);

        // 2. Akses halaman create jurnal
        $response = $this->actingAs($guru)->get("/guru/journal/create?schedule_id={$schedule->id}");
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Guru/Journal/Create')
            ->has('approvedLeaves')
        );

        // 3. Simpan jurnal untuk tanggal tersebut
        Storage::fake('public');
        $photo = UploadedFile::fake()->image('kbm.jpg');

        $storeResponse = $this->actingAs($guru)->post('/guru/journal', [
            'schedule_id' => $schedule->id,
            'date' => '2026-11-09',
            'topic' => 'KBM Integrasi Presensi',
            'notes' => 'Catatan KBM',
            'documentation' => $photo,
            'attendances' => [
                [
                    'student_id' => $student->id,
                    'status' => 'izin',
                    'notes' => 'Izin terverifikasi: Keperluan keluarga penting',
                ],
            ],
        ]);
        $storeResponse->assertRedirect('/guru/journal');

        // Pastikan tidak ada duplikasi baris attendance untuk siswa ini pada tanggal ini
        $this->assertEquals(1, Attendance::where('student_id', $student->id)->where('date', '2026-11-09')->count());

        // Baris attendance sekarang terikat ke journal_id
        $journal = Journal::where('schedule_id', $schedule->id)->latest('id')->first();
        $this->assertNotNull($journal);
        $this->assertDatabaseHas('attendances', [
            'journal_id' => $journal->id,
            'student_id' => $student->id,
            'date' => '2026-11-09',
            'status' => 'izin',
        ]);
    }
}
