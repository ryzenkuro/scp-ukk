<?php

namespace Tests\Feature;

use App\Models\HomeroomAssignment;
use App\Models\LeaveRequest;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageAKesiswaanWaliKelasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_kesiswaan_can_view_dashboard_with_stats(): void
    {
        $kesiswaanUser = User::create([
            'role_id' => 4, // kesiswaan
            'username' => 'kesiswaan_demo',
            'fullname' => 'Staf Kesiswaan',
            'email' => 'kesiswaan@smkn1bantul.sch.id',
            'password' => bcrypt('password'),
            'must_change_credentials' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs($kesiswaanUser)->get('/kesiswaan/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Kesiswaan/Dashboard')
            ->has('stats')
            ->has('config')
        );
    }

    public function test_kesiswaan_can_view_rekap_presensi_rombel(): void
    {
        $kesiswaanUser = User::create([
            'role_id' => 4,
            'username' => 'kesiswaan_presensi_tester',
            'fullname' => 'Petugas Kesiswaan Presensi',
            'email' => 'kesiswaan_presensi@smkn1bantul.sch.id',
            'password' => bcrypt('password'),
            'must_change_credentials' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs($kesiswaanUser)->get('/kesiswaan/presensi');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Kesiswaan/Presensi')
            ->has('kelasList')
            ->has('rekapData')
            ->has('summary')
            ->has('filters')
        );
    }

    public function test_kesiswaan_can_view_rekap_izin_rombel(): void
    {
        $kesiswaanUser = User::create([
            'role_id' => 4,
            'username' => 'kesiswaan_izin_tester',
            'fullname' => 'Petugas Kesiswaan Izin',
            'email' => 'kesiswaan_izin@smkn1bantul.sch.id',
            'password' => bcrypt('password'),
            'must_change_credentials' => false,
            'is_active' => true,
        ]);

        $student = Siswa::first();
        LeaveRequest::create([
            'student_id' => $student->id,
            'type' => 'izin',
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-10',
            'reason' => 'Keperluan keluarga penting',
            'status' => 'pending',
        ]);

        // Cek halaman index rekap izin kesiswaan
        $indexResponse = $this->actingAs($kesiswaanUser)->get('/kesiswaan/izin');
        $indexResponse->assertStatus(200);
        $indexResponse->assertInertia(fn ($page) => $page
            ->component('Kesiswaan/Izin')
            ->has('leaves')
            ->has('stats')
            ->has('kelasList')
        );

        // Verifikasi izin siswa dihapus dari kesiswaan (hanya wewenang Wali Kelas)
        $verifyResponse = $this->actingAs($kesiswaanUser)->post("/kesiswaan/izin/1/verify", [
            'status' => 'approved',
        ]);
        $this->assertTrue(in_array($verifyResponse->status(), [404, 405]));
    }

    public function test_kesiswaan_can_access_jurnal_kbm(): void
    {
        $kesiswaanUser = User::create([
            'role_id' => 4,
            'username' => 'kesiswaan_guru_tester',
            'fullname' => 'Petugas Kesiswaan Guru',
            'email' => 'kesiswaan_guru@smkn1bantul.sch.id',
            'password' => bcrypt('password'),
            'must_change_credentials' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs($kesiswaanUser)->get('/guru/journal');
        $response->assertStatus(200);
    }

    public function test_unauthorized_user_cannot_access_kesiswaan_portal(): void
    {
        $studentUser = User::where('role_id', 3)->first();
        $studentUser->update(['must_change_credentials' => false]);

        $response = $this->actingAs($studentUser)->get('/kesiswaan/dashboard');
        $response->assertStatus(403);
    }

    public function test_homeroom_teacher_can_access_wali_kelas_portal(): void
    {
        $teacher = User::where('role_id', 2)->first();
        $teacher->update(['must_change_credentials' => false]);

        $schoolClass = SchoolClass::firstOrCreate(['name' => 'XII RPL 1'], ['grade' => 'XII', 'jurusan' => 'RPL']);

        HomeroomAssignment::create([
            'user_id' => $teacher->id,
            'school_class_id' => $schoolClass->id,
            'class_name' => $schoolClass->name,
            'academic_year' => '2026/2027',
        ]);

        // Dashboard Wali Kelas
        $dashResponse = $this->actingAs($teacher)->get('/wali-kelas/dashboard');
        $dashResponse->assertStatus(200);
        $dashResponse->assertInertia(fn ($page) => $page
            ->component('WaliKelas/Dashboard')
            ->has('classNames')
            ->has('stats')
        );

        // Siswa Rombel Binaan (Read-only)
        $siswaResponse = $this->actingAs($teacher)->get('/wali-kelas/siswa');
        $siswaResponse->assertStatus(200);
        $siswaResponse->assertInertia(fn ($page) => $page
            ->component('WaliKelas/Siswa')
            ->has('students')
        );

        // Izin Rombel
        $izinResponse = $this->actingAs($teacher)->get('/wali-kelas/izin');
        $izinResponse->assertStatus(200);
        $izinResponse->assertInertia(fn ($page) => $page
            ->component('WaliKelas/Izin')
            ->has('leaves')
        );

        // Presensi Rombel
        $presensiResponse = $this->actingAs($teacher)->get('/wali-kelas/presensi');
        $presensiResponse->assertStatus(200);
        $presensiResponse->assertInertia(fn ($page) => $page
            ->component('WaliKelas/Presensi')
            ->has('rekapData')
        );
    }

    public function test_wali_kelas_can_only_verify_leaves_of_their_homeroom_class(): void
    {
        $teacher = User::where('role_id', 2)->first();
        $teacher->update(['must_change_credentials' => false]);

        $classRpl = SchoolClass::firstOrCreate(['name' => 'XII RPL 1'], ['grade' => 'XII', 'jurusan' => 'RPL']);

        HomeroomAssignment::create([
            'user_id' => $teacher->id,
            'school_class_id' => $classRpl->id,
            'class_name' => 'XII RPL 1',
            'academic_year' => '2026/2027',
        ]);

        $studentOwnClass = Siswa::where('kelas', 'XII RPL 1')->first();
        if (!$studentOwnClass) {
            $studentOwnClass = Siswa::create([
                'nis' => 'TEST9901',
                'name' => 'Siswa RPL 1',
                'kelas' => 'XII RPL 1',
                'jurusan' => 'RPL',
                'angkatan' => 2024,
                'gender' => 'L',
                'status' => 'aktif',
            ]);
        }

        $studentOtherClass = Siswa::create([
            'nis' => 'TEST9902',
            'name' => 'Siswa TKJ Luar',
            'kelas' => 'X TKJ 2',
            'jurusan' => 'TKJ',
            'angkatan' => 2026,
            'gender' => 'P',
            'status' => 'aktif',
        ]);

        $leaveOwn = LeaveRequest::create([
            'student_id' => $studentOwnClass->id,
            'type' => 'sakit',
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-10',
            'reason' => 'Demam flu',
            'status' => 'pending',
        ]);

        $leaveOther = LeaveRequest::create([
            'student_id' => $studentOtherClass->id,
            'type' => 'sakit',
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-10',
            'reason' => 'Sakit kepala',
            'status' => 'pending',
        ]);

        // Verifikasi izin siswa kelas sendiri -> Sukses
        $approveOwnResponse = $this->actingAs($teacher)->post("/wali-kelas/izin/{$leaveOwn->id}/verify", [
            'status' => 'approved',
        ]);
        $approveOwnResponse->assertRedirect();
        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveOwn->id,
            'status' => 'approved',
            'verified_by' => $teacher->id,
        ]);

        // Verifikasi izin siswa kelas lain -> 403 Forbidden
        $approveOtherResponse = $this->actingAs($teacher)->post("/wali-kelas/izin/{$leaveOther->id}/verify", [
            'status' => 'approved',
        ]);
        $approveOtherResponse->assertStatus(403);
    }

    public function test_global_dashboard_redirects_roles_correctly(): void
    {
        $kesiswaanUser = User::create([
            'role_id' => 4,
            'username' => 'kesiswaan_redirect',
            'fullname' => 'Petugas Kesiswaan Redirect',
            'email' => 'kesiswaan_redirect@smkn1bantul.sch.id',
            'password' => bcrypt('password'),
            'must_change_credentials' => false,
            'is_active' => true,
        ]);

        $kesiswaanRedirect = $this->actingAs($kesiswaanUser)->get('/dashboard');
        $kesiswaanRedirect->assertRedirect(route('kesiswaan.dashboard'));

        $wakaUser = User::where('role_id', 1)->first();
        $wakaUser->update(['must_change_credentials' => false]);
        $wakaRedirect = $this->actingAs($wakaUser)->get('/dashboard');
        $wakaRedirect->assertRedirect(route('waka.dashboard'));

        $siswaUser = User::where('role_id', 3)->first();
        $siswaUser->update(['must_change_credentials' => false]);
        $siswaRedirect = $this->actingAs($siswaUser)->get('/dashboard');
        $siswaRedirect->assertRedirect(route('siswa.dashboard'));
    }
}
