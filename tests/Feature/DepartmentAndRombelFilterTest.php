<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DepartmentAndRombelFilterTest extends TestCase
{
    private User $waka;
    private Role $roleWaka;

    protected function setUp(): void
    {
        parent::setUp();

        $this->roleWaka = Role::firstOrCreate(['role_name' => 'waka_kurikulum']);
        $suffix = uniqid();

        $this->waka = User::whereHas('role', fn ($q) => $q->where('role_name', 'waka_kurikulum'))->first()
            ?? User::create([
                'username' => 'waka_dept_' . $suffix,
                'fullname' => 'Waka Test ' . $suffix,
                'password' => Hash::make('password'),
                'role_id' => $this->roleWaka->id,
            ]);
    }

    public function test_waka_can_create_and_manage_departments(): void
    {
        $code = 'TEST' . rand(10, 99);
        // 1. Tambah Jurusan
        $response = $this->actingAs($this->waka)->post(route('waka.departments.store'), [
            'department_code' => $code,
            'department_name' => 'Jurusan Pengujian Otomatis',
            'description' => 'Deskripsi jurusan pengujian',
        ]);

        $response->assertSessionHas('success');
        $department = Department::where('department_code', $code)->first();
        $this->assertNotNull($department);

        // 2. Buat Kelas Terstruktur dengan Tingkat & Jurusan
        $response = $this->actingAs($this->waka)->post(route('waka.classes.store'), [
            'grade' => 'X',
            'department_id' => $department->id,
            'rombel_number' => '9',
        ]);
        $response->assertSessionHas('success');

        $createdClass = SchoolClass::where('class_name', "X {$code} 9")->first();
        $this->assertNotNull($createdClass);
        $this->assertEquals('X', $createdClass->grade);
        $this->assertEquals($department->id, $createdClass->department_id);

        // 3. Hapus Jurusan ditolak jika masih ada rombel yang mereferensikannya
        $response = $this->actingAs($this->waka)->delete(route('waka.departments.destroy', $department->id));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('departments', ['id' => $department->id]);

        // 4. Hapus kelas terlebih dahulu, lalu hapus jurusan
        $createdClass->delete();
        $response = $this->actingAs($this->waka)->delete(route('waka.departments.destroy', $department->id));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('departments', ['id' => $department->id]);
    }

    public function test_waka_dashboard_loads_departments_and_structured_classes(): void
    {
        Department::firstOrCreate([
            'department_code' => 'RPL',
        ], [
            'department_name' => 'Rekayasa Perangkat Lunak',
        ]);
        $response = $this->actingAs($this->waka)->get(route('waka.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('department_code');
        $response->assertSee('Rekayasa Perangkat Lunak');
    }
}
