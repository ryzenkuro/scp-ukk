<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AkunRoleFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_waka_can_filter_siswa_by_class(): void
    {
        $wakaRole = Role::firstOrCreate(['name' => 'waka']);
        $waka = User::factory()->create([
            'role_id' => $wakaRole->id,
            'username' => 'waka.filter.siswa',
            'must_change_credentials' => false,
        ]);

        $siswaRole = Role::firstOrCreate(['name' => 'siswa']);

        $classUnique = SchoolClass::create([
            'name' => 'XII RPL UNIQUE FILTER',
            'grade' => 'XII',
            'jurusan' => 'RPL',
        ]);

        $siswa = Siswa::create([
            'nis' => '99887711',
            'name' => 'Siswa Kelas Unique',
            'kelas' => $classUnique->name,
            'jurusan' => 'RPL',
            'gender' => 'L',
        ]);

        $user = User::factory()->create([
            'role_id' => $siswaRole->id,
            'username' => '99887711',
            'fullname' => 'Siswa Kelas Unique',
            'student_id' => $siswa->id,
        ]);

        $response = $this->actingAs($waka)->get(route('waka.akun.index', [
            'role_id' => $siswaRole->id,
            'class_id' => $classUnique->id,
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Waka/AkunRole/Index')
            ->where('filters.class_id', (string) $classUnique->id)
            ->where('filters.role_id', (string) $siswaRole->id)
            ->has('users.data', 1)
            ->where('users.data.0.username', '99887711')
            ->where('users.data.0.student.kelas', $classUnique->name)
        );
    }

    public function test_waka_can_filter_orang_tua_by_class(): void
    {
        $wakaRole = Role::firstOrCreate(['name' => 'waka']);
        $waka = User::factory()->create([
            'role_id' => $wakaRole->id,
            'username' => 'waka.filter.ortu',
            'must_change_credentials' => false,
        ]);

        $ortuRole = Role::firstOrCreate(['name' => 'orang_tua']);

        $classUnique = SchoolClass::create([
            'name' => 'XII DKV UNIQUE FILTER',
            'grade' => 'XII',
            'jurusan' => 'DKV',
        ]);

        $siswa = Siswa::create([
            'nis' => '99887722',
            'name' => 'Anak Unique',
            'kelas' => $classUnique->name,
            'jurusan' => 'DKV',
            'gender' => 'P',
        ]);

        $ortuUser = User::factory()->create([
            'role_id' => $ortuRole->id,
            'username' => 'ortu_99887722',
            'fullname' => 'Ortu Anak Unique',
            'student_id' => $siswa->id,
        ]);

        $response = $this->actingAs($waka)->get(route('waka.akun.index', [
            'role_id' => $ortuRole->id,
            'class_id' => $classUnique->id,
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Waka/AkunRole/Index')
            ->where('filters.class_id', (string) $classUnique->id)
            ->where('filters.role_id', (string) $ortuRole->id)
            ->has('users.data', 1)
            ->where('users.data.0.username', 'ortu_99887722')
            ->where('users.data.0.student.kelas', $classUnique->name)
        );
    }

    public function test_class_filter_is_ignored_for_non_student_or_parent_roles(): void
    {
        $wakaRole = Role::firstOrCreate(['name' => 'waka']);
        $waka = User::factory()->create([
            'role_id' => $wakaRole->id,
            'username' => 'waka.filter.guru',
            'must_change_credentials' => false,
        ]);

        $guruRole = Role::firstOrCreate(['name' => 'guru']);
        $classUnique = SchoolClass::create([
            'name' => 'X TKJ UNIQUE FILTER',
            'grade' => 'X',
            'jurusan' => 'TKJ',
        ]);

        User::factory()->create([
            'role_id' => $guruRole->id,
            'username' => 'guru.filter.satu',
            'fullname' => 'Guru Filter Satu',
        ]);

        // Filter role guru dengan class_id dikirimkan
        $response = $this->actingAs($waka)->get(route('waka.akun.index', [
            'role_id' => $guruRole->id,
            'class_id' => $classUnique->id,
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Waka/AkunRole/Index')
            ->where('filters.class_id', 'all')
            ->where('filters.role_id', (string) $guruRole->id)
        );
    }
}
