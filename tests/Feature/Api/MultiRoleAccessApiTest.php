<?php

namespace Tests\Feature\Api;

use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class MultiRoleAccessApiTest extends TestCase
{
    private function getOrCreateRole(string $roleName): Role
    {
        return Role::firstOrCreate(['role_name' => $roleName], ['role_name' => $roleName]);
    }

    private function getOrCreateUser(string $roleName, string $username, ?int $classId = null): User
    {
        $role = $this->getOrCreateRole($roleName);

        return User::updateOrCreate(
            ['username' => $username],
            [
                'fullname' => 'Test User ' . ucfirst($roleName),
                'email'    => "{$username}@smkn1bantul.sch.id",
                'password' => bcrypt('password123'),
                'role_id'  => $role->id,
                'class_id' => $classId,
                'is_active'=> true,
            ]
        );
    }

    public function test_waka_kurikulum_has_supervision_access_to_all_operational_endpoints(): void
    {
        $waka = $this->getOrCreateUser('waka_kurikulum', 'waka_audit_test');

        // 1. Waka dapat mengaudit jadwal KBM sekolah via /api/guru/schedules
        $respJadwal = $this->actingAs($waka, 'sanctum')->getJson(route('api.guru.schedules'));
        $respJadwal->assertStatus(200)
            ->assertJson(['status' => 'success']);

        // 2. Waka dapat mengaudit riwayat jurnal guru via /api/guru/journals
        $respJurnal = $this->actingAs($waka, 'sanctum')->getJson(route('api.guru.journals.index'));
        $respJurnal->assertStatus(200)
            ->assertJson(['status' => 'success']);

        // 3. Waka dapat memantau jadwal blok via /api/portal/schedules
        $respPortalJadwal = $this->actingAs($waka, 'sanctum')->getJson(route('api.portal.schedules'));
        $respPortalJadwal->assertStatus(200)
            ->assertJson(['status' => 'success']);

        // 4. Waka dapat melihat summary presensi via /api/portal/attendance/summary
        $respPortalPresensi = $this->actingAs($waka, 'sanctum')->getJson(route('api.portal.attendance.summary'));
        $respPortalPresensi->assertStatus(200)
            ->assertJson(['status' => 'success']);

        // 5. Waka dapat mengakses antrean approval izin sekolah via /api/approval/leave-requests
        $respApproval = $this->actingAs($waka, 'sanctum')->getJson(route('api.approval.leave-requests.index'));
        $respApproval->assertStatus(200)
            ->assertJson(['status' => 'success']);
    }

    public function test_wali_kelas_and_guru_bk_access_matrix(): void
    {
        $class = SchoolClass::firstOrCreate(['class_name' => 'XII RPL 1']);
        $wali = $this->getOrCreateUser('wali_kelas', 'wali_audit_test', $class->id);
        $bk = $this->getOrCreateUser('guru_bk', 'bk_audit_test');

        // Wali Kelas dapat melihat jadwal rombel binaannya via portal
        $respWaliJadwal = $this->actingAs($wali, 'sanctum')->getJson(route('api.portal.schedules'));
        $respWaliJadwal->assertStatus(200)
            ->assertJson(['status' => 'success']);

        // Wali Kelas dapat mengakses antrean approval izin rombel
        $respWaliApproval = $this->actingAs($wali, 'sanctum')->getJson(route('api.approval.leave-requests.index'));
        $respWaliApproval->assertStatus(200)
            ->assertJson(['status' => 'success']);

        // Guru BK dapat mengakses antrean approval izin seluruh sekolah
        $respBkApproval = $this->actingAs($bk, 'sanctum')->getJson(route('api.approval.leave-requests.index'));
        $respBkApproval->assertStatus(200)
            ->assertJson(['status' => 'success']);

        // Guru BK dapat melihat jadwal sekolah via portal
        $respBkJadwal = $this->actingAs($bk, 'sanctum')->getJson(route('api.portal.schedules'));
        $respBkJadwal->assertStatus(200)
            ->assertJson(['status' => 'success']);
    }

    public function test_role_boundary_enforcement(): void
    {
        $guru = $this->getOrCreateUser('guru', 'guru_audit_test');
        $siswa = $this->getOrCreateUser('siswa', 'siswa_audit_test');

        // Guru tidak berhak membuka antrean approval izin kesiswaan
        $respGuruApproval = $this->actingAs($guru, 'sanctum')->getJson(route('api.approval.leave-requests.index'));
        $respGuruApproval->assertStatus(403);

        // Siswa tidak berhak membuka rute operasional guru
        $respSiswaGuru = $this->actingAs($siswa, 'sanctum')->getJson(route('api.guru.schedules'));
        $respSiswaGuru->assertStatus(403);
    }
}

