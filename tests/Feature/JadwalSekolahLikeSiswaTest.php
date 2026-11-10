<?php

namespace Tests\Feature;

use App\Models\HomeroomAssignment;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JadwalSekolahLikeSiswaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        // Pastikan seluruh 12 slot master SMK N 1 Bantul tersedia
        $slots = [
            ['name' => 'Jam Ke-1', 'start_time' => '07:15:00', 'end_time' => '08:00:00', 'is_break' => false, 'order' => 1],
            ['name' => 'Jam Ke-2', 'start_time' => '08:00:00', 'end_time' => '08:45:00', 'is_break' => false, 'order' => 2],
            ['name' => 'Jam Ke-3', 'start_time' => '08:45:00', 'end_time' => '09:30:00', 'is_break' => false, 'order' => 3],
            ['name' => 'Istirahat Pagi', 'start_time' => '09:30:00', 'end_time' => '09:45:00', 'is_break' => true, 'order' => 4],
            ['name' => 'Jam Ke-4', 'start_time' => '09:45:00', 'end_time' => '10:30:00', 'is_break' => false, 'order' => 5],
            ['name' => 'Jam Ke-5', 'start_time' => '10:30:00', 'end_time' => '11:15:00', 'is_break' => false, 'order' => 6],
            ['name' => 'Jam Ke-6', 'start_time' => '11:15:00', 'end_time' => '12:00:00', 'is_break' => false, 'order' => 7],
            ['name' => 'Istirahat Siang', 'start_time' => '12:00:00', 'end_time' => '12:30:00', 'is_break' => true, 'order' => 8],
            ['name' => 'Jam Ke-7', 'start_time' => '12:30:00', 'end_time' => '13:15:00', 'is_break' => false, 'order' => 9],
            ['name' => 'Jam Ke-8', 'start_time' => '13:15:00', 'end_time' => '14:00:00', 'is_break' => false, 'order' => 10],
            ['name' => 'Jam Ke-9', 'start_time' => '14:00:00', 'end_time' => '14:45:00', 'is_break' => false, 'order' => 11],
            ['name' => 'Jam Ke-10', 'start_time' => '14:45:00', 'end_time' => '15:30:00', 'is_break' => false, 'order' => 12],
        ];
        foreach ($slots as $slot) {
            TimeSlot::updateOrCreate(['name' => $slot['name']], $slot);
        }
    }

    public function test_guru_can_access_personal_jadwal_mengajar(): void
    {
        $guruUser = User::where('role_id', 2)->first();
        $this->assertNotNull($guruUser);
        $guruUser->update(['must_change_credentials' => false]);

        $response = $this->actingAs($guruUser)->get('/guru/jadwal');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Guru/Jadwal')
            ->has('teacher')
            ->has('schedules')
            ->has('timeSlots', 12)
            ->has('todayName')
            ->has('activeCycle')
        );
    }

    public function test_wali_kelas_can_access_jadwal_kelas_binaan(): void
    {
        $roleWali = Role::firstOrCreate(['name' => 'wali_kelas'], ['display_name' => 'Wali Kelas']);
        $waliUser = User::where('role_id', 5)->first();
        if (! $waliUser) {
            $waliUser = User::create([
                'role_id' => $roleWali->id,
                'name' => 'Wali Kelas Demo',
                'fullname' => 'Wali Kelas Demo, S.Pd',
                'username' => 'walidemo',
                'password' => bcrypt('password'),
                'must_change_credentials' => false,
            ]);
        } else {
            $waliUser->update(['must_change_credentials' => false]);
        }

        $class = SchoolClass::first();
        HomeroomAssignment::firstOrCreate(
            ['user_id' => $waliUser->id, 'class_name' => $class->name],
            ['academic_year' => '2026/2027']
        );

        $response = $this->actingAs($waliUser)->get('/wali-kelas/jadwal');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('WaliKelas/Jadwal')
            ->has('classNames')
            ->has('selectedClass')
            ->has('schedules')
            ->has('timeSlots', 12)
            ->has('todayName')
        );
    }

    public function test_guru_bk_can_access_shared_jadwal_sekolah_viewer(): void
    {
        $roleBk = Role::firstOrCreate(['name' => 'guru_bk'], ['display_name' => 'Guru BK']);
        $bkUser = User::where('role_id', 4)->first();
        if (! $bkUser) {
            $bkUser = User::create([
                'role_id' => $roleBk->id,
                'name' => 'Guru BK Demo',
                'fullname' => 'Guru BK Demo, S.Pd',
                'username' => 'bkdemo',
                'password' => bcrypt('password'),
                'must_change_credentials' => false,
            ]);
        } else {
            $bkUser->update(['must_change_credentials' => false]);
        }

        // Test route /bk/jadwal
        $response = $this->actingAs($bkUser)->get('/bk/jadwal');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Jadwal/JadwalSekolahViewer')
            ->has('classes')
            ->has('teachers')
            ->has('timeSlots', 12)
            ->has('schedules')
            ->has('filters')
            ->where('canManage', false)
        );

        // Test route /jadwal-sekolah
        $responseShared = $this->actingAs($bkUser)->get('/jadwal-sekolah');
        $responseShared->assertStatus(200);
        $responseShared->assertInertia(fn ($page) => $page
            ->component('Jadwal/JadwalSekolahViewer')
            ->where('canManage', false)
        );
    }

    public function test_waka_can_access_shared_jadwal_sekolah_with_manage_true(): void
    {
        $wakaUser = User::where('role_id', 1)->first();
        $this->assertNotNull($wakaUser);
        $wakaUser->update(['must_change_credentials' => false]);

        $response = $this->actingAs($wakaUser)->get('/jadwal-sekolah');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Jadwal/JadwalSekolahViewer')
            ->where('canManage', true)
        );
    }
}
