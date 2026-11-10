<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Siswa;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiswaJadwalByDayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        // Ensure all 12 official SMK N 1 Bantul time slots exist for complete timeline testing
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

    public function test_siswa_can_view_schedule_with_time_slots_and_ordered_days(): void
    {
        $siswaUser = User::where('username', '202611001')->first();
        if (! $siswaUser) {
            $roleSiswa = Role::firstOrCreate(['name' => 'siswa'], ['display_name' => 'Siswa / Murid']);
            $student = Siswa::first();
            $siswaUser = User::create([
                'role_id' => $roleSiswa->id,
                'name' => $student->name,
                'username' => $student->nis,
                'password' => bcrypt('password'),
                'student_id' => $student->id,
                'must_change_credentials' => false,
            ]);
        } else {
            $siswaUser->update(['must_change_credentials' => false]);
        }

        $response = $this->actingAs($siswaUser)->get('/siswa/jadwal');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Siswa/Jadwal')
            ->has('student')
            ->has('schedules')
            ->has('timeSlots', 12)
            ->has('todayName')
            ->has('activeCycle')
            ->has('config')
        );

        // Verify days ordering is calendar school week (Senin -> Jumat), NOT alphabetical
        $props = $response->original->getData()['page']['props'];
        $schedules = $props['schedules'];
        $daysSeen = [];
        foreach ($schedules as $s) {
            $day = is_array($s) ? $s['day_of_week'] : $s->day_of_week;
            if (! in_array($day, $daysSeen, true)) {
                $daysSeen[] = $day;
            }
        }

        $expectedDaysOrder = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        $filteredExpected = array_values(array_intersect($expectedDaysOrder, $daysSeen));
        $this->assertEquals($filteredExpected, $daysSeen, 'Schedules must be ordered by school week calendar (Senin to Jumat), not alphabetically');
    }

    public function test_siswa_can_filter_schedule_by_cycle(): void
    {
        $siswaUser = User::where('username', '202611001')->first();
        $siswaUser->update(['must_change_credentials' => false]);

        $response = $this->actingAs($siswaUser)->get('/siswa/jadwal?cycle=1');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Siswa/Jadwal')
            ->where('activeCycle', 1)
        );

        $props = $response->original->getData()['page']['props'];
        foreach ($props['schedules'] as $s) {
            $cycle = is_array($s) ? $s['cycle_number'] : $s->cycle_number;
            $this->assertEquals(1, $cycle);
        }
    }

    public function test_orang_tua_can_view_student_schedule_with_slots(): void
    {
        $student = Siswa::where('status', 'aktif')->first();
        $roleOrtu = Role::firstOrCreate(['name' => 'orang_tua'], ['display_name' => 'Orang Tua / Wali']);
        $ortuUser = User::firstOrCreate(
            ['username' => 'ortu_'.$student->nis],
            [
                'role_id' => $roleOrtu->id,
                'name' => 'Orang Tua '.$student->name,
                'password' => bcrypt('password'),
                'student_id' => $student->id,
                'must_change_credentials' => false,
            ]
        );
        $ortuUser->update(['must_change_credentials' => false]);

        $response = $this->actingAs($ortuUser)->get('/siswa/jadwal');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Siswa/Jadwal')
            ->has('timeSlots', 12)
            ->has('todayName')
        );
    }

    public function test_siswa_can_export_pdf_schedule(): void
    {
        $siswaUser = User::where('username', '202611001')->first();
        $siswaUser->update(['must_change_credentials' => false]);

        $response = $this->actingAs($siswaUser)->get('/siswa/jadwal/export-pdf?cycle=1');

        $response->assertStatus(200);
        $response->assertSee('JADWAL BELAJAR SISWA');
    }
}
