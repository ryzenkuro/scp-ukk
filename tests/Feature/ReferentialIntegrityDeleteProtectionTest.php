<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Journal;
use App\Models\LeaveRequest;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Siswa;
use App\Models\SystemConfiguration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ReferentialIntegrityDeleteProtectionTest extends TestCase
{
    use RefreshDatabase;

    private User $waka;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->waka = User::where('role_id', 1)->first() ?? User::create([
            'username' => 'waka_test_integritas',
            'fullname' => 'Waka Kurikulum Test',
            'role_id' => 1,
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);
    }

    public function test_cannot_delete_teacher_with_active_schedules(): void
    {
        $teacher = User::create([
            'username' => 'guru_has_schedule_'.uniqid(),
            'fullname' => 'Guru Pengajar Aktif',
            'role_id' => 2,
            'password' => Hash::make('password123'),
        ]);

        $class = SchoolClass::firstOrCreate(['name' => 'XII RPL 2']);
        $room = Room::firstOrCreate(['name' => 'LAB 19']);

        Schedule::create([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'room_id' => $room->id,
            'subject' => 'Pemrograman Web',
            'day_of_week' => 'Senin',
            'start_time' => '07:15',
            'end_time' => '09:30',
            'class_name' => 'XII RPL 2',
            'cycle_number' => 1,
        ]);

        $response = $this->actingAs($this->waka)->delete("/waka/guru/{$teacher->id}");

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $teacher->id]);
    }

    public function test_cannot_delete_teacher_with_journals(): void
    {
        $teacher = User::create([
            'username' => 'guru_has_journal_'.uniqid(),
            'fullname' => 'Guru Punya Jurnal',
            'role_id' => 2,
            'password' => Hash::make('password123'),
        ]);

        $class = SchoolClass::firstOrCreate(['name' => 'XII RPL 2']);
        $room = Room::firstOrCreate(['name' => 'LAB 19']);

        $schedule = Schedule::create([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'room_id' => $room->id,
            'subject' => 'Pemrograman Mobile',
            'day_of_week' => 'Selasa',
            'start_time' => '07:15',
            'end_time' => '09:30',
            'class_name' => 'XII RPL 2',
            'cycle_number' => 1,
        ]);

        Journal::create([
            'schedule_id' => $schedule->id,
            'teacher_id' => $teacher->id,
            'date' => now()->toDateString(),
            'topic' => 'React Native Navigation',
        ]);

        $response = $this->actingAs($this->waka)->delete("/waka/guru/{$teacher->id}");

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $teacher->id]);
    }

    public function test_can_delete_teacher_without_dependencies(): void
    {
        $teacher = User::create([
            'username' => 'guru_clean_'.uniqid(),
            'fullname' => 'Guru Tanpa Relasi',
            'role_id' => 2,
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($this->waka)->delete("/waka/guru/{$teacher->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('users', ['id' => $teacher->id]);
    }

    public function test_cannot_delete_student_with_attendance_records(): void
    {
        $siswa = Siswa::create([
            'nis' => '99991',
            'name' => 'Siswa Punya Presensi',
            'kelas' => 'XII RPL 2',
            'gender' => 'L',
            'angkatan' => 2024,
            'status' => 'aktif',
        ]);

        Attendance::create([
            'student_id' => $siswa->id,
            'date' => now()->toDateString(),
            'status' => 'hadir',
        ]);

        $response = $this->actingAs($this->waka)->delete("/waka/siswa/{$siswa->id}");

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('siswas', ['id' => $siswa->id]);
    }

    public function test_cannot_delete_student_with_leave_requests(): void
    {
        $siswa = Siswa::create([
            'nis' => '99992',
            'name' => 'Siswa Punya Izin',
            'kelas' => 'XII RPL 2',
            'gender' => 'P',
            'angkatan' => 2024,
            'status' => 'aktif',
        ]);

        LeaveRequest::create([
            'student_id' => $siswa->id,
            'type' => 'izin',
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'reason' => 'Keperluan keluarga',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->waka)->delete("/waka/siswa/{$siswa->id}");

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('siswas', ['id' => $siswa->id]);
    }

    public function test_can_delete_student_without_dependencies(): void
    {
        $siswa = Siswa::create([
            'nis' => '99993',
            'name' => 'Siswa Baru Belum Ada Presensi',
            'kelas' => 'XII RPL 2',
            'gender' => 'L',
            'angkatan' => 2024,
            'status' => 'aktif',
        ]);

        $response = $this->actingAs($this->waka)->delete("/waka/siswa/{$siswa->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('siswas', ['id' => $siswa->id]);
    }

    public function test_cannot_delete_schedule_with_journals(): void
    {
        $teacher = User::create([
            'username' => 'guru_sched_test_'.uniqid(),
            'fullname' => 'Guru Jadwal Test',
            'role_id' => 2,
            'password' => Hash::make('password123'),
        ]);

        $class = SchoolClass::firstOrCreate(['name' => 'XII RPL 2']);
        $room = Room::firstOrCreate(['name' => 'LAB 19']);

        $config = SystemConfiguration::current();
        $config->update(['is_scheduled_locked' => false]);

        $schedule = Schedule::create([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'room_id' => $room->id,
            'subject' => 'Basis Data Lanjut',
            'day_of_week' => 'Rabu',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'class_name' => 'XII RPL 2',
            'cycle_number' => 1,
        ]);

        Journal::create([
            'schedule_id' => $schedule->id,
            'teacher_id' => $teacher->id,
            'date' => now()->toDateString(),
            'topic' => 'Stored Procedures',
        ]);

        $response = $this->actingAs($this->waka)->delete("/waka/jadwal/{$schedule->id}");

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('schedules', ['id' => $schedule->id]);
    }

    public function test_cannot_delete_teacher_account_in_akun_role_when_dependencies_exist(): void
    {
        $teacher = User::create([
            'username' => 'guru_akun_test_'.uniqid(),
            'fullname' => 'Guru Akun Test',
            'role_id' => 2,
            'password' => Hash::make('password123'),
        ]);

        $class = SchoolClass::firstOrCreate(['name' => 'XII RPL 2']);
        $room = Room::firstOrCreate(['name' => 'LAB 19']);

        Schedule::create([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'room_id' => $room->id,
            'subject' => 'Pemrograman Dasar',
            'day_of_week' => 'Kamis',
            'start_time' => '07:15',
            'end_time' => '09:30',
            'class_name' => 'XII RPL 2',
            'cycle_number' => 1,
        ]);

        $response = $this->actingAs($this->waka)->delete("/waka/akun/{$teacher->id}");

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $teacher->id]);
    }
}
