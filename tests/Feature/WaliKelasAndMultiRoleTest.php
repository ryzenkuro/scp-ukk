<?php

namespace Tests\Feature;

use App\Models\HomeroomAssignment;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WaliKelasAndMultiRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_wali_kelas_can_view_dashboard_and_assigned_class(): void
    {
        $waliRole = Role::firstOrCreate(['name' => 'wali_kelas']);
        $class = SchoolClass::firstOrCreate(['name' => 'XI RPL 1', 'grade' => 'XI', 'jurusan' => 'RPL']);

        $rosyida = User::factory()->create([
            'fullname' => 'ROSYIDA RAMADHANI, S.Pd',
            'username' => 'guru.90',
            'role_id' => $waliRole->id,
            'class_id' => $class->id,
            'must_change_credentials' => false,
        ]);

        HomeroomAssignment::create([
            'user_id' => $rosyida->id,
            'school_class_id' => $class->id,
            'class_name' => $class->name,
            'academic_year' => '2026/2027',
        ]);

        $response = $this->actingAs($rosyida)->get(route('wali-kelas.dashboard'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('WaliKelas/Dashboard')
            ->has('classNames')
            ->has('stats')
        );
    }

    public function test_wali_kelas_can_view_siswa_binaan(): void
    {
        $waliRole = Role::firstOrCreate(['name' => 'wali_kelas']);
        $class = SchoolClass::firstOrCreate(['name' => 'XI RPL 1', 'grade' => 'XI', 'jurusan' => 'RPL']);

        $rosyida = User::factory()->create([
            'fullname' => 'ROSYIDA RAMADHANI, S.Pd',
            'username' => 'guru.90',
            'role_id' => $waliRole->id,
            'class_id' => $class->id,
            'must_change_credentials' => false,
        ]);

        HomeroomAssignment::create([
            'user_id' => $rosyida->id,
            'school_class_id' => $class->id,
            'class_name' => $class->name,
            'academic_year' => '2026/2027',
        ]);

        $response = $this->actingAs($rosyida)->get(route('wali-kelas.siswa'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('WaliKelas/Siswa')
            ->has('students')
        );
    }

    public function test_guru_portal_shows_teaching_schedules(): void
    {
        $guruRole = Role::firstOrCreate(['name' => 'guru']);
        $teacher = User::factory()->create([
            'fullname' => 'ROSYIDA RAMADHANI, S.Pd',
            'username' => 'guru.90',
            'role_id' => $guruRole->id,
            'must_change_credentials' => false,
        ]);

        $response = $this->actingAs($teacher)->get(route('guru.dashboard'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Guru/Dashboard')
            ->has('stats')
            ->has('todaySchedules')
        );
    }

    public function test_kesiswaan_guru_bk_dashboard_works_without_sql_error(): void
    {
        $bkRole = Role::firstOrCreate(['name' => 'guru_bk']);
        $guruBk = User::factory()->create([
            'fullname' => 'GURU BK SMKN 1 BANTUL',
            'username' => 'guru.bk',
            'role_id' => $bkRole->id,
            'must_change_credentials' => false,
        ]);

        $response = $this->actingAs($guruBk)->get(route('kesiswaan.dashboard'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Kesiswaan/Dashboard')
            ->has('stats')
            ->has('config')
        );
    }

    public function test_waka_can_assign_homeroom_teacher(): void
    {
        $wakaRole = Role::firstOrCreate(['name' => 'waka']);
        $waka = User::factory()->create([
            'fullname' => 'Waka Kurikulum',
            'username' => 'waka.kurikulum',
            'role_id' => $wakaRole->id,
            'must_change_credentials' => false,
        ]);

        $teacherRole = Role::firstOrCreate(['name' => 'guru']);
        $teacher = User::factory()->create([
            'fullname' => 'Guru Pengampu',
            'username' => 'guru.demo',
            'role_id' => $teacherRole->id,
            'must_change_credentials' => false,
        ]);
        $class = SchoolClass::firstOrCreate(['name' => 'X RPL 1', 'grade' => 'X', 'jurusan' => 'RPL']);

        $response = $this->actingAs($waka)->post(route('waka.siswa.assign-homeroom'), [
            'class_id' => $class->id,
            'teacher_id' => $teacher->id,
            'academic_year' => '2026/2027',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('homeroom_assignments', [
            'user_id' => $teacher->id,
            'school_class_id' => $class->id,
        ]);
    }
}
