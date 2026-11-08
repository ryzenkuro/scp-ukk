<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\Room;
use App\Models\SchoolClass;
use App\Models\Siswa;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class PackageDExcelImportExportTest extends TestCase
{
    use RefreshDatabase;

    private User $waka;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->waka = User::where('role_id', 1)->first();
        $this->waka->update(['must_change_credentials' => false]);
    }

    public function test_waka_can_download_student_template_and_example(): void
    {
        $resTemplate = $this->actingAs($this->waka)->get('/waka/siswa/template');
        $resTemplate->assertOk();
        $this->assertStringContainsString('template_import_siswa.xlsx', (string) $resTemplate->headers->get('content-disposition'));

        $resExample = $this->actingAs($this->waka)->get('/waka/siswa/contoh');
        $resExample->assertOk();
        $this->assertStringContainsString('contoh_import_siswa_5_data.xlsx', (string) $resExample->headers->get('content-disposition'));
    }

    public function test_waka_can_import_students_from_excel(): void
    {
        $students = [
            ['Siswa Test 1', 'IMP001', 'X RPL 1', 'RPL', '081200000011', '', 'Jl. Merdeka 1'],
            ['Siswa Test 2', 'IMP002', 'X TKJ 1', 'TKJ', '081200000012', '', 'Jl. Merdeka 2'],
            ['Siswa Test 3', 'IMP003', 'XI DKV 1', 'DKV', '081200000013', '', 'Jl. Merdeka 3'],
        ];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(['Nama', 'NIS', 'Kelas', 'Jurusan', 'No HP', 'Email', 'Alamat'], null, 'A1');
        $sheet->fromArray($students, null, 'A2');

        $path = tempnam(sys_get_temp_dir(), 'siswa_excel_');
        (new Xlsx($spreadsheet))->save($path);
        $file = new UploadedFile($path, 'siswa.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->actingAs($this->waka)->post('/waka/siswa/import', [
            'file' => $file,
        ]);

        @unlink($path);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        foreach ($students as [$name, $nis, $kelas, $jurusan]) {
            $this->assertDatabaseHas('siswas', [
                'name' => $name,
                'nis' => $nis,
                'kelas' => $kelas,
                'jurusan' => $jurusan,
            ]);
            $this->assertDatabaseHas('users', [
                'username' => $nis,
                'role_id' => 3,
            ]);
        }
    }

    public function test_invalid_excel_rows_prevent_student_import(): void
    {
        $invalidStudents = [
            ['Siswa Valid', 'IMP991', 'X RPL 1', 'RPL', '081200000091', '', 'Jl. Merdeka 1'],
            ['Siswa Invalid Jurusan', 'IMP992', 'X ABC 1', 'UNKNOWN', '081200000092', '', 'Jl. Merdeka 2'],
        ];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(['Nama', 'NIS', 'Kelas', 'Jurusan', 'No HP', 'Email', 'Alamat'], null, 'A1');
        $sheet->fromArray($invalidStudents, null, 'A2');

        $path = tempnam(sys_get_temp_dir(), 'siswa_inv_');
        (new Xlsx($spreadsheet))->save($path);
        $file = new UploadedFile($path, 'siswa_inv.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->actingAs($this->waka)
            ->from('/waka/siswa')
            ->post('/waka/siswa/import', [
                'file' => $file,
            ]);

        @unlink($path);

        $response->assertRedirect('/waka/siswa');
        $response->assertSessionHasErrors('file');

        $this->assertDatabaseMissing('siswas', ['nis' => 'IMP991']);
        $this->assertDatabaseMissing('siswas', ['nis' => 'IMP992']);
    }

    public function test_waka_can_delete_students_by_class_with_force(): void
    {
        $class = 'X BATCH DELETE';
        SchoolClass::firstOrCreate(['name' => $class]);

        $s1 = Siswa::create(['name' => 'S1', 'nis' => 'DEL01', 'kelas' => $class, 'jurusan' => 'RPL', 'angkatan' => 2026, 'gender' => 'L', 'status' => 'aktif']);
        $s2 = Siswa::create(['name' => 'S2', 'nis' => 'DEL02', 'kelas' => $class, 'jurusan' => 'RPL', 'angkatan' => 2026, 'gender' => 'L', 'status' => 'aktif']);

        User::create(['role_id' => 3, 'username' => 'DEL01', 'password' => Hash::make('password'), 'student_id' => $s1->id]);
        User::create(['role_id' => 3, 'username' => 'DEL02', 'password' => Hash::make('password'), 'student_id' => $s2->id]);

        $response = $this->actingAs($this->waka)->delete('/waka/siswa/hapus-per-kelas', [
            'kelas' => $class,
            'force' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('siswas', ['kelas' => $class]);
        $this->assertDatabaseMissing('users', ['username' => 'DEL01']);
        $this->assertDatabaseMissing('users', ['username' => 'DEL02']);
    }

    public function test_waka_can_download_teacher_template_and_example(): void
    {
        $resTemplate = $this->actingAs($this->waka)->get('/waka/guru/template');
        $resTemplate->assertOk();
        $this->assertStringContainsString('template_import_guru.xlsx', (string) $resTemplate->headers->get('content-disposition'));

        $resExample = $this->actingAs($this->waka)->get('/waka/guru/contoh');
        $resExample->assertOk();
        $this->assertStringContainsString('contoh_import_guru_5_data.xlsx', (string) $resExample->headers->get('content-disposition'));
    }

    public function test_waka_can_import_teachers_from_excel_and_assign_homeroom(): void
    {
        $class = SchoolClass::firstOrCreate(['name' => 'X RPL 1']);

        $teachers = [
            ['Guru Import Satu', 'guru.imp1', '198501012010011010', '081233330001', 'guru.imp1@sekolah.sch.id', 'Pass12345', 'X RPL 1'],
            ['Guru Import Dua', 'guru.imp2', '198602022010022020', '081233330002', 'guru.imp2@sekolah.sch.id', 'Pass12345', ''],
        ];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(['Nama Lengkap', 'Username', 'NIP', 'Nomor HP', 'Email', 'Password', 'Wali Kelas'], null, 'A1');
        $sheet->fromArray($teachers, null, 'A2');

        $path = tempnam(sys_get_temp_dir(), 'guru_excel_');
        (new Xlsx($spreadsheet))->save($path);
        $file = new UploadedFile($path, 'guru.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->actingAs($this->waka)->post('/waka/guru/import', [
            'file' => $file,
        ]);

        @unlink($path);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'fullname' => 'Guru Import Satu',
            'username' => 'guru.imp1',
            'nip' => '198501012010011010',
        ]);

        $teacher1 = User::where('username', 'guru.imp1')->first();
        $this->assertDatabaseHas('user_school_classes', [
            'user_id' => $teacher1->id,
            'school_class_id' => $class->id,
        ]);
    }

    public function test_waka_can_download_schedule_template_and_example(): void
    {
        $resTemplate = $this->actingAs($this->waka)->get('/waka/jadwal/template');
        $resTemplate->assertOk();
        $this->assertStringContainsString('template_import_jadwal.xlsx', (string) $resTemplate->headers->get('content-disposition'));

        $resExample = $this->actingAs($this->waka)->get('/waka/jadwal/contoh');
        $resExample->assertOk();
        $this->assertStringContainsString('contoh_import_jadwal_5_data.xlsx', (string) $resExample->headers->get('content-disposition'));
    }

    public function test_waka_can_import_schedules_from_excel(): void
    {
        $guru = User::where('role_id', 2)->first();
        $slot1 = TimeSlot::firstOrCreate(['order' => 1], ['name' => 'Jam ke-1', 'start_time' => '07:00:00', 'end_time' => '07:45:00', 'is_break' => false]);
        $slot2 = TimeSlot::firstOrCreate(['order' => 2], ['name' => 'Jam ke-2', 'start_time' => '07:45:00', 'end_time' => '08:30:00', 'is_break' => false]);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            'Hari', 'Guru', 'Mata Pelajaran', 'Kelas', 'Ruangan', 'Sesi Mulai', 'Sesi Selesai', 'Putaran', 'Jenis'
        ], null, 'A1');
        $sheet->fromArray([
            ['Senin', $guru->fullname, 'Pemrograman Web Lanjut', 'XII RPL 1', 'Lab 1', 'Jam ke-1', 'Jam ke-2', '1', 'Kejuruan'],
        ], null, 'A2');

        $path = tempnam(sys_get_temp_dir(), 'jadwal_excel_');
        (new Xlsx($spreadsheet))->save($path);
        $file = new UploadedFile($path, 'jadwal.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->actingAs($this->waka)->post('/waka/jadwal/import', [
            'file' => $file,
            'cycle_number' => '1',
        ]);

        @unlink($path);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('schedules', [
            'teacher_id' => $guru->id,
            'subject' => 'Pemrograman Web Lanjut',
            'class_name' => 'XII RPL 1',
            'cycle_number' => 1,
        ]);
    }

    public function test_alumni_export_endpoint_is_removed(): void
    {
        $response = $this->actingAs($this->waka)->get('/waka/alumni/export');
        $response->assertNotFound();
    }
}

