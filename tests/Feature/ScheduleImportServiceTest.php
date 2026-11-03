<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\User;
use App\Services\ScheduleImportService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ScheduleImportServiceTest extends TestCase
{
    private Role $roleWaka;
    private Role $roleGuru;
    private Role $roleSiswa;
    private User $wakaUser;
    private User $siswaUser;
    private SchoolClass $class;
    private Room $room;
    private string $suffix;

    protected function setUp(): void
    {
        parent::setUp();

        $this->roleWaka = Role::firstOrCreate(['role_name' => 'waka_kurikulum']);
        $this->roleGuru = Role::firstOrCreate(['role_name' => 'guru']);
        $this->roleSiswa = Role::firstOrCreate(['role_name' => 'siswa']);

        $this->suffix = uniqid();

        $this->wakaUser = User::create([
            'username' => 'waka_imp_' . $this->suffix,
            'fullname' => 'Waka Import Penguji ' . $this->suffix,
            'password' => Hash::make('password'),
            'role_id' => $this->roleWaka->id,
        ]);

        $this->siswaUser = User::create([
            'username' => 'siswa_imp_' . $this->suffix,
            'fullname' => 'Siswa Penguji ' . $this->suffix,
            'password' => Hash::make('password'),
            'role_id' => $this->roleSiswa->id,
        ]);

        $this->class = SchoolClass::create(['class_name' => 'KLS_IMP_' . $this->suffix]);
        $this->room = Room::create(['room_name' => 'RM_IMP_' . $this->suffix]);
    }

    protected function tearDown(): void
    {
        Schedule::where('class_id', $this->class->id)->orWhere('room_id', $this->room->id)->delete();
        User::where('username', 'like', "%{$this->suffix}%")->delete();
        $this->room->delete();
        $this->class->delete();

        parent::tearDown();
    }

    public function test_service_imports_csv_and_auto_creates_teacher_account(): void
    {
        $importer = new ScheduleImportService();

        $newTeacherNip = '1988' . rand(100000, 999999);
        $newTeacherName = 'Guru Baru Auto ' . $this->suffix;

        $csvContent = "Nama Guru,NIP,Mata Pelajaran,Nama Kelas,Nama Ruang,Hari,Jam Mulai,Jam Selesai,Siklus\n"
                    . "{$newTeacherName},{$newTeacherNip},Cloud Computing,{$this->class->class_name},{$this->room->room_name},Senin,07:00,09:30,Putaran 1\n";

        $file = UploadedFile::fake()->createWithContent('jadwal_test.csv', $csvContent);

        $result = $importer->importCsv($file);

        $this->assertEquals(1, $result['success_count']);
        $this->assertEmpty($result['errors']);

        // 1. Akun Guru baru otomatis terdaftar (KF35)
        $teacher = User::where('username', $newTeacherNip)->first();
        $this->assertNotNull($teacher);
        $this->assertEquals($newTeacherName, $teacher->fullname);
        $this->assertEquals($this->roleGuru->id, $teacher->role_id);

        // 2. Jadwal tersimpan
        $schedule = Schedule::where('subject', 'Cloud Computing')
            ->where('class_id', $this->class->id)
            ->first();
        $this->assertNotNull($schedule);
        $this->assertEquals('Senin', $schedule->day_of_week);
        $this->assertEquals('Putaran 1', $schedule->cycle_type);
    }

    public function test_service_detects_3d_schedule_conflict_and_skips_row(): void
    {
        $importer = new ScheduleImportService();

        $teacher1Nip = '1981' . rand(100000, 999999);
        $teacher2Nip = '1982' . rand(100000, 999999);

        // Baris 1: Guru 1 di ruangan $this->room jam 07:00 - 09:30
        // Baris 2: Guru 2 di ruangan yang SAMA ($this->room) jam 08:00 - 10:00 (Bentrok Ruangan BR-001)
        $csvContent = "Nama Guru,NIP,Mata Pelajaran,Nama Kelas,Nama Ruang,Hari,Jam Mulai,Jam Selesai,Siklus\n"
                    . "Guru Satu {$this->suffix},{$teacher1Nip},Mapel A,{$this->class->class_name},{$this->room->room_name},Senin,07:00:00,09:30:00,Putaran 1\n"
                    . "Guru Dua {$this->suffix},{$teacher2Nip},Mapel B,{$this->class->class_name},{$this->room->room_name},Senin,08:00:00,10:00:00,Putaran 1\n";

        $file = UploadedFile::fake()->createWithContent('bentrok.csv', $csvContent);

        $result = $importer->importCsv($file);

        // Baris 1 sukses, Baris 2 ditolak karena bentrok
        $this->assertEquals(1, $result['success_count']);
        $this->assertCount(1, $result['errors']);
        $this->assertStringContainsString('Jadwal bentrok', $result['errors'][0]);
    }

    public function test_service_reports_error_if_class_or_room_does_not_exist(): void
    {
        $importer = new ScheduleImportService();

        $csvContent = "Nama Guru,NIP,Mata Pelajaran,Nama Kelas,Nama Ruang,Hari,Jam Mulai,Jam Selesai,Siklus\n"
                    . "Guru Hantu,99999999,Mapel Gaib,KELAS_TIDAK_ADA,RUANG_TIDAK_ADA,Selasa,07:00:00,09:00:00,Putaran 1\n";

        $file = UploadedFile::fake()->createWithContent('tidak_ada.csv', $csvContent);

        $result = $importer->importCsv($file);

        $this->assertEquals(0, $result['success_count']);
        $this->assertCount(1, $result['errors']);
        $this->assertStringContainsString('tidak ditemukan di sistem', $result['errors'][0]);
    }

    public function test_waka_can_download_template_and_import_csv_via_web(): void
    {
        // 1. Download template
        $response = $this->actingAs($this->wakaUser)->get(route('waka.schedules.template'));
        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'attachment; filename="template_import_jadwal.csv"');

        // 2. Akses ditolak untuk role siswa (403)
        $response = $this->actingAs($this->siswaUser)->get(route('waka.schedules.template'));
        $response->assertStatus(403);

        // 3. Waka mengimpor jadwal via form POST
        $teacherNip = '1989' . rand(100000, 999999);
        $csvContent = "Nama Guru,NIP,Mata Pelajaran,Nama Kelas,Nama Ruang,Hari,Jam Mulai,Jam Selesai,Siklus\n"
                    . "Guru Web {$this->suffix},{$teacherNip},Kecerdasan Buatan,{$this->class->class_name},{$this->room->room_name},Rabu,08:00,10:30,Putaran 1\n";

        $file = UploadedFile::fake()->createWithContent('import_web.csv', $csvContent);

        $response = $this->actingAs($this->wakaUser)->post(route('waka.schedules.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('schedules', [
            'subject' => 'Kecerdasan Buatan',
            'class_id' => $this->class->id,
            'room_id' => $this->room->id,
            'day_of_week' => 'Rabu',
        ]);
    }
}
