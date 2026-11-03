<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Journal;
use App\Models\Role;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AttendanceReportTest extends TestCase
{
    private Role $roleGuru;
    private Role $roleWaliKelas;
    private Role $roleWaka;
    private Role $roleSiswa;
    private SchoolClass $class;
    private User $teacher;
    private User $waliKelas;
    private User $waka;
    private User $student1;
    private User $student2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->roleGuru = Role::firstOrCreate(['role_name' => 'guru']);
        $this->roleWaliKelas = Role::firstOrCreate(['role_name' => 'wali_kelas']);
        $this->roleWaka = Role::firstOrCreate(['role_name' => 'waka_kurikulum']);
        $this->roleSiswa = Role::firstOrCreate(['role_name' => 'siswa']);

        $suffix = uniqid();
        $this->class = SchoolClass::create(['class_name' => 'XII RPL TEST ' . $suffix]);

        $this->teacher = User::create([
            'username' => 'guru_rep_' . $suffix,
            'fullname' => 'Guru Penguji Laporan ' . $suffix,
            'password' => Hash::make('password'),
            'role_id' => $this->roleGuru->id,
        ]);

        $this->waliKelas = User::create([
            'username' => 'wali_rep_' . $suffix,
            'fullname' => 'Wali Kelas ' . $this->class->class_name,
            'password' => Hash::make('password'),
            'role_id' => $this->roleWaliKelas->id,
            'class_id' => $this->class->id,
        ]);

        $this->waka = User::whereHas('role', fn ($q) => $q->where('role_name', 'waka_kurikulum'))->first()
            ?? User::create([
                'username' => 'waka_rep_' . $suffix,
                'fullname' => 'Wakil Kepala Kurikulum M.Pd',
                'password' => Hash::make('password'),
                'role_id' => $this->roleWaka->id,
            ]);

        $this->student1 = User::create([
            'username' => 'siswa1_rep_' . $suffix,
            'fullname' => 'Siswa Satu ' . $suffix,
            'password' => Hash::make('password'),
            'role_id' => $this->roleSiswa->id,
            'class_id' => $this->class->id,
        ]);

        $this->student2 = User::create([
            'username' => 'siswa2_rep_' . $suffix,
            'fullname' => 'Siswa Dua ' . $suffix,
            'password' => Hash::make('password'),
            'role_id' => $this->roleSiswa->id,
            'class_id' => $this->class->id,
        ]);
    }

    protected function tearDown(): void
    {
        Attendance::whereIn('student_id', [$this->student1->id, $this->student2->id])->delete();
        $this->student1->delete();
        $this->student2->delete();
        $this->waliKelas->delete();
        $this->teacher->delete();
        $this->class->delete();

        parent::tearDown();
    }

    public function test_guest_and_unauthorized_roles_are_restricted_from_reports(): void
    {
        // 1. Guest redirected to login
        $response = $this->get(route('reports.attendance.index'));
        $response->assertRedirect(route('login'));

        // 2. Siswa forbidden (403)
        $response = $this->actingAs($this->student1)->get(route('reports.attendance.index'));
        $response->assertStatus(403);

        $response = $this->actingAs($this->student1)->get(route('reports.attendance.print', ['class_id' => $this->class->id]));
        $response->assertStatus(403);
    }

    public function test_authorized_users_can_view_attendance_recap_and_percentages_are_accurate(): void
    {
        // Sediakan 4 rekaman absensi untuk student1:
        // 2 Hadir, 1 Sakit, 1 Alpha => Total = 4, % = 50.0%
        $room = Room::firstOrCreate(['room_name' => 'Lab Test Report']);
        $schedule = Schedule::firstOrCreate([
            'teacher_id' => $this->teacher->id,
            'class_id' => $this->class->id,
            'room_id' => $room->id,
            'subject' => 'Pemrograman Web',
            'day_of_week' => 'Senin',
            'start_time' => '07:00:00',
            'end_time' => '09:00:00',
            'cycle_type' => 'Putaran 1',
        ]);

        $journal = Journal::create([
            'schedule_id' => $schedule->id,
            'date' => now()->toDateString(),
            'topic_description' => 'Materi Rekap Presensi',
            'photo_path' => 'photos/test.jpg',
        ]);

        Attendance::create([
            'journal_id' => $journal->id,
            'student_id' => $this->student1->id,
            'status' => 'Hadir',
            'date' => now()->toDateString(),
        ]);
        Attendance::create([
            'journal_id' => $journal->id,
            'student_id' => $this->student1->id,
            'status' => 'Hadir',
            'date' => now()->subDay()->toDateString(),
        ]);
        Attendance::create([
            'journal_id' => $journal->id,
            'student_id' => $this->student1->id,
            'status' => 'Sakit',
            'date' => now()->subDays(2)->toDateString(),
        ]);
        Attendance::create([
            'journal_id' => $journal->id,
            'student_id' => $this->student1->id,
            'status' => 'Alpha',
            'date' => now()->subDays(3)->toDateString(),
        ]);

        // student2 tidak memiliki kehadiran => Total = 0, % = 0.0%

        $startDate = now()->subDays(5)->toDateString();
        $endDate = now()->toDateString();

        $response = $this->actingAs($this->waliKelas)->get(route('reports.attendance.index', [
            'class_id' => $this->class->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]));

        $response->assertStatus(200);
        $response->assertSee($this->student1->fullname);
        $response->assertSee($this->student2->fullname);
        $response->assertSee('&quot;percentage&quot;:50', false); // Persentase student1: (2/4)*100% = 50%
        $response->assertSee('&quot;percentage&quot;:0', false);  // Persentase student2: 0%

        // Bersihkan data jurnal & jadwal test
        Attendance::where('journal_id', $journal->id)->delete();
        $journal->delete();
        $schedule->delete();
    }

    public function test_print_page_renders_official_a4_layout_with_kop_and_signatures(): void
    {
        $startDate = now()->startOfMonth()->toDateString();
        $endDate = now()->endOfMonth()->toDateString();

        $response = $this->actingAs($this->waliKelas)->get(route('reports.attendance.print', [
            'class_id' => $this->class->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]));

        $response->assertStatus(200);
        $response->assertSee('SMK NEGERI 1 BANTUL');
        $response->assertSee('LAPORAN REKAPITULASI PRESENSI KEHADIRAN SISWA');
        $response->assertSee($this->class->class_name);
        $response->assertSee('Wali Kelas ' . $this->class->class_name);
        $response->assertSee('Waka Urusan Kurikulum');
        $response->assertSee($this->waliKelas->fullname);
        $response->assertSee($this->waka->fullname);
    }

    public function test_wali_kelas_is_strictly_isolated_to_own_class(): void
    {
        $otherClass = SchoolClass::create(['class_name' => 'XII RPL LAIN ' . uniqid()]);

        // 1. Wali Kelas mencoba print kelas lain -> Diblokir 403
        $response = $this->actingAs($this->waliKelas)->get(route('reports.attendance.print', [
            'class_id' => $otherClass->id,
        ]));
        $response->assertStatus(403);

        // 2. Wali Kelas membuka index dengan query class_id kelas lain -> Dipaksa ke kelas sendiri
        $response = $this->actingAs($this->waliKelas)->get(route('reports.attendance.index', [
            'class_id' => $otherClass->id,
        ]));
        $response->assertStatus(200);
        $response->assertSee($this->class->class_name);

        $otherClass->delete();
    }

    public function test_guru_bk_can_view_and_print_all_classes(): void
    {
        $roleBk = Role::firstOrCreate(['role_name' => 'guru_bk']);
        $guruBk = User::create([
            'username' => 'bk_rep_' . uniqid(),
            'fullname' => 'Guru BK Konseling',
            'password' => Hash::make('password'),
            'role_id' => $roleBk->id,
        ]);

        $response = $this->actingAs($guruBk)->get(route('reports.attendance.index', [
            'class_id' => $this->class->id,
        ]));
        $response->assertStatus(200);
        $response->assertSee($this->class->class_name);

        $response = $this->actingAs($guruBk)->get(route('reports.attendance.print', [
            'class_id' => $this->class->id,
        ]));
        $response->assertStatus(200);

        $guruBk->delete();
    }
}
