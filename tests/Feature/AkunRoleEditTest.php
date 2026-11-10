<?php

namespace Tests\Feature;

use App\Models\HomeroomAssignment;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AkunRoleEditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_waka_can_update_user_basic_info(): void
    {
        $wakaRole = Role::firstOrCreate(['name' => 'waka']);
        $waka = User::factory()->create([
            'role_id' => $wakaRole->id,
            'username' => 'waka.test',
            'must_change_credentials' => false,
        ]);

        $guruRole = Role::firstOrCreate(['name' => 'guru']);
        $user = User::factory()->create([
            'role_id' => $guruRole->id,
            'fullname' => 'Nama Lama',
            'username' => 'guru.lama',
            'email' => 'lama@smkn1bantul.sch.id',
            'phone' => '0811111111',
            'nip' => '198001012000011001',
            'must_change_credentials' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs($waka)->put(route('waka.akun.update', $user), [
            'fullname' => 'Nama Baru Lengkap',
            'username' => 'guru.baru',
            'role_id' => $guruRole->id,
            'email' => 'baru@smkn1bantul.sch.id',
            'phone' => '0822222222',
            'nip' => '198001012000011002',
            'is_active' => true,
            'must_change_credentials' => false,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'fullname' => 'Nama Baru Lengkap',
            'username' => 'guru.baru',
            'email' => 'baru@smkn1bantul.sch.id',
            'phone' => '0822222222',
            'nip' => '198001012000011002',
        ]);
    }

    public function test_waka_can_change_role_to_wali_kelas_with_class(): void
    {
        $wakaRole = Role::firstOrCreate(['name' => 'waka']);
        $waka = User::factory()->create([
            'role_id' => $wakaRole->id,
            'username' => 'waka.test2',
            'must_change_credentials' => false,
        ]);

        $guruRole = Role::firstOrCreate(['name' => 'guru']);
        $waliRole = Role::firstOrCreate(['name' => 'wali_kelas']);
        $class = SchoolClass::firstOrCreate(['name' => 'X RPL 1', 'grade' => 'X', 'jurusan' => 'RPL']);

        $user = User::factory()->create([
            'role_id' => $guruRole->id,
            'fullname' => 'Calon Wali Kelas',
            'username' => 'calon.wali',
            'must_change_credentials' => false,
        ]);

        $response = $this->actingAs($waka)->put(route('waka.akun.update', $user), [
            'fullname' => 'Calon Wali Kelas',
            'username' => 'calon.wali',
            'role_id' => $waliRole->id,
            'class_id' => $class->id,
            'is_active' => true,
            'must_change_credentials' => false,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role_id' => $waliRole->id,
            'class_id' => $class->id,
        ]);

        $this->assertDatabaseHas('homeroom_assignments', [
            'user_id' => $user->id,
            'school_class_id' => $class->id,
        ]);
    }

    public function test_waka_can_demote_wali_kelas_to_guru(): void
    {
        $wakaRole = Role::firstOrCreate(['name' => 'waka']);
        $waka = User::factory()->create([
            'role_id' => $wakaRole->id,
            'username' => 'waka.test3',
            'must_change_credentials' => false,
        ]);

        $guruRole = Role::firstOrCreate(['name' => 'guru']);
        $waliRole = Role::firstOrCreate(['name' => 'wali_kelas']);
        $class = SchoolClass::firstOrCreate(['name' => 'XI RPL 1', 'grade' => 'XI', 'jurusan' => 'RPL']);

        $user = User::factory()->create([
            'role_id' => $waliRole->id,
            'class_id' => $class->id,
            'fullname' => 'Wali Turun Jabatan',
            'username' => 'wali.turun',
            'must_change_credentials' => false,
        ]);

        HomeroomAssignment::create([
            'user_id' => $user->id,
            'school_class_id' => $class->id,
            'class_name' => $class->name,
            'academic_year' => '2026/2027',
        ]);

        $response = $this->actingAs($waka)->put(route('waka.akun.update', $user), [
            'fullname' => 'Wali Turun Jabatan',
            'username' => 'wali.turun',
            'role_id' => $guruRole->id,
            'class_id' => null,
            'is_active' => true,
            'must_change_credentials' => false,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role_id' => $guruRole->id,
            'class_id' => null,
        ]);

        $this->assertDatabaseMissing('homeroom_assignments', [
            'user_id' => $user->id,
        ]);
    }

    public function test_waka_cannot_demote_active_waka_role(): void
    {
        $wakaRole = Role::firstOrCreate(['name' => 'waka']);
        $guruRole = Role::firstOrCreate(['name' => 'guru']);

        $waka = User::factory()->create([
            'role_id' => $wakaRole->id,
            'username' => 'waka.utama',
            'must_change_credentials' => false,
        ]);

        $response = $this->actingAs($waka)->put(route('waka.akun.update', $waka), [
            'fullname' => 'Waka Diubah Guru',
            'username' => 'waka.utama',
            'role_id' => $guruRole->id,
            'is_active' => false,
            'must_change_credentials' => false,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('users', [
            'id' => $waka->id,
            'role_id' => $wakaRole->id,
            'is_active' => true,
        ]);
    }

    public function test_waka_can_update_password_optionally(): void
    {
        $wakaRole = Role::firstOrCreate(['name' => 'waka']);
        $waka = User::factory()->create([
            'role_id' => $wakaRole->id,
            'username' => 'waka.test4',
            'must_change_credentials' => false,
        ]);

        $guruRole = Role::firstOrCreate(['name' => 'guru']);
        $oldPasswordHash = Hash::make('password_lama');
        $user = User::factory()->create([
            'role_id' => $guruRole->id,
            'username' => 'guru.pass',
            'password' => $oldPasswordHash,
            'must_change_credentials' => false,
        ]);

        // 1. Update tanpa password baru -> password lama tetap
        $this->actingAs($waka)->put(route('waka.akun.update', $user), [
            'fullname' => $user->fullname,
            'username' => $user->username,
            'role_id' => $guruRole->id,
            'password' => '',
            'is_active' => true,
            'must_change_credentials' => false,
        ]);

        $user->refresh();
        $this->assertTrue(Hash::check('password_lama', $user->password));

        // 2. Update dengan password baru -> password ter-update
        $this->actingAs($waka)->put(route('waka.akun.update', $user), [
            'fullname' => $user->fullname,
            'username' => $user->username,
            'role_id' => $guruRole->id,
            'password' => 'password_baru_123',
            'is_active' => true,
            'must_change_credentials' => false,
        ]);

        $user->refresh();
        $this->assertTrue(Hash::check('password_baru_123', $user->password));
    }
}
