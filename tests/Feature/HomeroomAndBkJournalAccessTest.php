<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class HomeroomAndBkJournalAccessTest extends TestCase
{
    private User $waliKelas;
    private User $guruBk;
    private User $siswa;
    private SchoolClass $class;

    protected function setUp(): void
    {
        parent::setUp();

        $roleWali = Role::firstOrCreate(['role_name' => 'wali_kelas']);
        $roleBk = Role::firstOrCreate(['role_name' => 'guru_bk']);
        $roleSiswa = Role::firstOrCreate(['role_name' => 'siswa']);

        $suffix = uniqid();
        $this->class = SchoolClass::create(['class_name' => 'XII RPL TEST ' . $suffix]);

        $this->waliKelas = User::create([
            'username' => 'wali_kbm_' . $suffix,
            'fullname' => 'Wali Kelas KBM ' . $suffix,
            'password' => Hash::make('password'),
            'role_id' => $roleWali->id,
            'class_id' => $this->class->id,
        ]);

        $this->guruBk = User::create([
            'username' => 'bk_kbm_' . $suffix,
            'fullname' => 'Guru BK KBM ' . $suffix,
            'password' => Hash::make('password'),
            'role_id' => $roleBk->id,
        ]);

        $this->siswa = User::create([
            'username' => 'siswa_kbm_' . $suffix,
            'fullname' => 'Siswa ' . $suffix,
            'password' => Hash::make('password'),
            'role_id' => $roleSiswa->id,
            'class_id' => $this->class->id,
        ]);
    }

    protected function tearDown(): void
    {
        $this->siswa->delete();
        $this->guruBk->delete();
        $this->waliKelas->delete();
        $this->class->delete();

        parent::tearDown();
    }

    public function test_wali_kelas_can_access_schedules_and_journals(): void
    {
        // 1. Wali Kelas mengakses jadwal mengajar
        $response = $this->actingAs($this->waliKelas)->get(route('guru.schedules.index'));
        $response->assertStatus(200);

        // 2. Wali Kelas mengakses jurnal mengajar
        $response = $this->actingAs($this->waliKelas)->get(route('guru.journals.index'));
        $response->assertStatus(200);
    }

    public function test_guru_bk_can_access_schedules_and_journals(): void
    {
        // 1. Guru BK mengakses jadwal
        $response = $this->actingAs($this->guruBk)->get(route('guru.schedules.index'));
        $response->assertStatus(200);

        // 2. Guru BK mengakses jurnal
        $response = $this->actingAs($this->guruBk)->get(route('guru.journals.index'));
        $response->assertStatus(200);
    }

    public function test_siswa_cannot_access_guru_kbm_modules(): void
    {
        $response = $this->actingAs($this->siswa)->get(route('guru.schedules.index'));
        $response->assertStatus(403);

        $response = $this->actingAs($this->siswa)->get(route('guru.journals.index'));
        $response->assertStatus(403);
    }
}

