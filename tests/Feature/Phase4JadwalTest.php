<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\SystemConfiguration;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase4JadwalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_waka_can_view_jadwal_index(): void
    {
        $waka = User::where('role_id', 1)->first();

        $response = $this->actingAs($waka)->get('/waka/jadwal');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Waka/Jadwal/Index')
            ->has('schedules')
            ->has('classes')
            ->has('rooms')
            ->has('teachers')
            ->has('timeSlots')
            ->has('config')
        );
    }

    public function test_waka_cannot_add_schedule_when_locked(): void
    {
        $waka = User::where('role_id', 1)->first();
        $config = SystemConfiguration::current();
        $config->update(['is_scheduled_locked' => true]);

        $guru = User::where('username', 'guru1')->first();
        $class = SchoolClass::first();
        $room = Room::first();
        $slot1 = TimeSlot::where('order', 1)->first();
        $slot2 = TimeSlot::where('order', 2)->first();

        $response = $this->actingAs($waka)->post('/waka/jadwal', [
            'day_of_week' => 'Selasa',
            'cycle_number' => 1,
            'subject' => 'Pemrograman Berorientasi Objek',
            'teacher_id' => $guru->id,
            'class_id' => $class->id,
            'room_id' => $room->id,
            'start_slot_id' => $slot1->id,
            'end_slot_id' => $slot2->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseMissing('schedules', [
            'subject' => 'Pemrograman Berorientasi Objek',
            'day_of_week' => 'Selasa',
        ]);
    }

    public function test_waka_can_add_schedule_when_unlocked(): void
    {
        $waka = User::where('role_id', 1)->first();
        $config = SystemConfiguration::current();
        $config->update(['is_scheduled_locked' => false]);

        $guru = User::where('username', 'guru1')->first();
        $class = SchoolClass::first();
        $room = Room::first();
        $slot1 = TimeSlot::where('order', 1)->first();
        $slot2 = TimeSlot::where('order', 2)->first();

        $response = $this->actingAs($waka)->post('/waka/jadwal', [
            'day_of_week' => 'Selasa',
            'cycle_number' => 1,
            'subject' => 'Pemrograman Berorientasi Objek',
            'teacher_id' => $guru->id,
            'class_id' => $class->id,
            'room_id' => $room->id,
            'start_slot_id' => $slot1->id,
            'end_slot_id' => $slot2->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('schedules', [
            'subject' => 'Pemrograman Berorientasi Objek',
            'day_of_week' => 'Selasa',
            'cycle_number' => 1,
            'teacher_id' => $guru->id,
        ]);
    }

    public function test_conflict_detection_prevents_teacher_overlap(): void
    {
        $waka = User::where('role_id', 1)->first();
        $config = SystemConfiguration::current();
        $config->update(['is_scheduled_locked' => false]);

        $guru1 = User::where('username', 'guru1')->first();
        $classes = SchoolClass::all();
        $rooms = Room::all();
        $slot1 = TimeSlot::where('order', 1)->first();
        $slot2 = TimeSlot::where('order', 2)->first();
        $slot3 = TimeSlot::where('order', 3)->first();

        // 1. Jadwal Pertama: Guru 1 mengajar kelas X di ruang 1 pada Jam 1 - Jam 2 (07:00 - 08:30)
        Schedule::create([
            'day_of_week' => 'Kamis',
            'cycle_number' => 1,
            'subject' => 'Basis Data Dasar',
            'teacher_id' => $guru1->id,
            'class_id' => $classes[0]->id,
            'class_name' => $classes[0]->name,
            'room_id' => $rooms[0]->id,
            'room_name' => $rooms[0]->name,
            'start_time' => $slot1->start_time,
            'end_time' => $slot2->end_time,
            'start_slot_id' => $slot1->id,
            'end_slot_id' => $slot2->id,
        ]);

        // 2. Coba buat jadwal kedua dengan Guru 1 pada waktu yang bertabrakan (Jam 2 - Jam 3: 07:45 - 09:15)
        $response = $this->actingAs($waka)->post('/waka/jadwal', [
            'day_of_week' => 'Kamis',
            'cycle_number' => 1,
            'subject' => 'Pemrograman Lanjutan',
            'teacher_id' => $guru1->id,
            'class_id' => $classes[1]->id,
            'room_id' => $rooms[1]->id,
            'start_slot_id' => $slot2->id,
            'end_slot_id' => $slot3->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertStringContainsString('Bentrok Tenaga Pendidik', session('error'));

        $this->assertDatabaseMissing('schedules', [
            'subject' => 'Pemrograman Lanjutan',
            'day_of_week' => 'Kamis',
        ]);
    }

    public function test_conflict_detection_prevents_room_overlap(): void
    {
        $waka = User::where('role_id', 1)->first();
        $config = SystemConfiguration::current();
        $config->update(['is_scheduled_locked' => false]);

        $guru1 = User::where('username', 'guru1')->first();
        $guru2 = User::where('username', 'guru2')->first();
        $classes = SchoolClass::all();
        $room = Room::first();
        $slot1 = TimeSlot::where('order', 1)->first();
        $slot2 = TimeSlot::where('order', 2)->first();

        // 1. Jadwal Pertama di Ruangan 1
        Schedule::create([
            'day_of_week' => 'Jumat',
            'cycle_number' => 1,
            'subject' => 'Praktikum Komputer 1',
            'teacher_id' => $guru1->id,
            'class_id' => $classes[0]->id,
            'class_name' => $classes[0]->name,
            'room_id' => $room->id,
            'room_name' => $room->name,
            'start_time' => $slot1->start_time,
            'end_time' => $slot2->end_time,
            'start_slot_id' => $slot1->id,
            'end_slot_id' => $slot2->id,
        ]);

        // 2. Coba pakai ruangan yang sama oleh Guru 2 & Kelas lain pada jam yang sama
        $response = $this->actingAs($waka)->post('/waka/jadwal', [
            'day_of_week' => 'Jumat',
            'cycle_number' => 1,
            'subject' => 'Praktikum Komputer 2',
            'teacher_id' => $guru2->id,
            'class_id' => $classes[1]->id,
            'room_id' => $room->id,
            'start_slot_id' => $slot1->id,
            'end_slot_id' => $slot2->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertStringContainsString('Bentrok Ruangan', session('error'));

        $this->assertDatabaseMissing('schedules', [
            'subject' => 'Praktikum Komputer 2',
            'day_of_week' => 'Jumat',
        ]);
    }

    public function test_conflict_detection_prevents_class_overlap(): void
    {
        $waka = User::where('role_id', 1)->first();
        $config = SystemConfiguration::current();
        $config->update(['is_scheduled_locked' => false]);

        $guru1 = User::where('username', 'guru1')->first();
        $guru2 = User::where('username', 'guru2')->first();
        $class = SchoolClass::first();
        $rooms = Room::all();
        $slot1 = TimeSlot::where('order', 1)->first();
        $slot2 = TimeSlot::where('order', 2)->first();

        // 1. Jadwal Pertama untuk Kelas 1
        Schedule::create([
            'day_of_week' => 'Rabu',
            'cycle_number' => 1,
            'subject' => 'Matematika Terapan',
            'teacher_id' => $guru1->id,
            'class_id' => $class->id,
            'class_name' => $class->name,
            'room_id' => $rooms[0]->id,
            'room_name' => $rooms[0]->name,
            'start_time' => $slot1->start_time,
            'end_time' => $slot2->end_time,
            'start_slot_id' => $slot1->id,
            'end_slot_id' => $slot2->id,
        ]);

        // 2. Coba buat jadwal kedua untuk Kelas 1 yang sama di ruangan dan guru berbeda
        $response = $this->actingAs($waka)->post('/waka/jadwal', [
            'day_of_week' => 'Rabu',
            'cycle_number' => 1,
            'subject' => 'Bahasa Inggris Kejuruan',
            'teacher_id' => $guru2->id,
            'class_id' => $class->id,
            'room_id' => $rooms[1]->id,
            'start_slot_id' => $slot1->id,
            'end_slot_id' => $slot2->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertStringContainsString('Bentrok Kelas', session('error'));

        $this->assertDatabaseMissing('schedules', [
            'subject' => 'Bahasa Inggris Kejuruan',
            'day_of_week' => 'Rabu',
        ]);
    }

    public function test_waka_can_update_and_delete_schedule(): void
    {
        $waka = User::where('role_id', 1)->first();
        $config = SystemConfiguration::current();
        $config->update(['is_scheduled_locked' => false]);

        $schedule = Schedule::first();
        $slot1 = TimeSlot::where('order', 1)->first();
        $slot2 = TimeSlot::where('order', 2)->first();

        // 1. Update jadwal
        $response = $this->actingAs($waka)->put("/waka/jadwal/{$schedule->id}", [
            'day_of_week' => $schedule->day_of_week,
            'cycle_number' => $schedule->cycle_number,
            'subject' => 'Mata Pelajaran Diperbarui',
            'teacher_id' => $schedule->teacher_id,
            'class_id' => $schedule->class_id,
            'room_id' => $schedule->room_id,
            'start_slot_id' => $slot1->id,
            'end_slot_id' => $slot2->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $schedule->refresh();
        $this->assertEquals('Mata Pelajaran Diperbarui', $schedule->subject);

        // 2. Delete jadwal saat terkunci (harus ditolak)
        $config->update(['is_scheduled_locked' => true]);
        $delResponse = $this->actingAs($waka)->delete("/waka/jadwal/{$schedule->id}");
        $delResponse->assertSessionHas('error');
        $this->assertDatabaseHas('schedules', ['id' => $schedule->id]);

        // 3. Delete jadwal saat terbuka (harus berhasil)
        $config->update(['is_scheduled_locked' => false]);
        $delResponse2 = $this->actingAs($waka)->delete("/waka/jadwal/{$schedule->id}");
        $delResponse2->assertSessionHas('success');
        $this->assertDatabaseMissing('schedules', ['id' => $schedule->id]);
    }

    public function test_waka_can_reset_all_schedules_from_zero(): void
    {
        $waka = User::where('role_id', 1)->first();
        $config = SystemConfiguration::current();
        $config->update(['is_scheduled_locked' => false]);

        $this->assertGreaterThan(0, Schedule::count());
        $initialTimeSlots = TimeSlot::count();
        $this->assertGreaterThan(0, $initialTimeSlots);

        $response = $this->actingAs($waka)->delete('/waka/jadwal/reset-semua');
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Jadwal kosong, tapi master jam pembelajaran TIDAK terhapus (terpisah)
        $this->assertEquals(0, Schedule::count());
        $this->assertEquals($initialTimeSlots, TimeSlot::count());
    }

    public function test_waka_can_reset_all_time_slots_separately(): void
    {
        $waka = User::where('role_id', 1)->first();
        $initialScheduleCount = Schedule::count();
        $this->assertGreaterThan(0, $initialScheduleCount);
        $this->assertGreaterThan(0, TimeSlot::count());

        $response = $this->actingAs($waka)->delete('/waka/jam-pelajaran/reset-semua');
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Master jam pembelajaran kosong
        $this->assertEquals(0, TimeSlot::count());

        // Jadwal pelajaran tetap aman
        $this->assertEquals($initialScheduleCount, Schedule::count());
    }

    public function test_waka_can_download_jam_pelajaran_template_and_contoh(): void
    {
        $waka = User::where('role_id', 1)->first();

        $responseTemplate = $this->actingAs($waka)->get('/waka/jam-pelajaran/template');
        $responseTemplate->assertStatus(200);

        $responseContoh = $this->actingAs($waka)->get('/waka/jam-pelajaran/contoh');
        $responseContoh->assertStatus(200);
    }

    public function test_waka_can_import_jam_pelajaran_separately(): void
    {
        $waka = User::where('role_id', 1)->first();

        // Buat file CSV master jam pembelajaran
        $csvContent = "Urutan,Nama Jam / Sesi,Jam Mulai,Jam Selesai,Tipe\n";
        $csvContent .= "1,Jam Ke-1,07:00,07:45,Jam KBM\n";
        $csvContent .= "2,Jam Ke-2,07:45,08:30,Jam KBM\n";
        $csvContent .= "3,Istirahat Pagi,08:30,09:00,Istirahat\n";

        $tempFile = tempnam(sys_get_temp_dir(), 'test_jam_') . '.csv';
        file_put_contents($tempFile, $csvContent);

        $uploadedFile = new \Illuminate\Http\UploadedFile(
            $tempFile,
            'jam_test.csv',
            'text/csv',
            null,
            true
        );

        $response = $this->actingAs($waka)->post('/waka/jam-pelajaran/import', [
            'file' => $uploadedFile,
            'replace_existing' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals(3, TimeSlot::count());
        $this->assertDatabaseHas('time_slots', ['name' => 'Jam Ke-1', 'is_break' => false]);
        $this->assertDatabaseHas('time_slots', ['name' => 'Istirahat Pagi', 'is_break' => true]);

        @unlink($tempFile);
    }

    public function test_waka_jam_pelajaran_displays_active_cycle_from_dashboard(): void
    {
        $waka = User::where('role_id', 1)->first();
        $config = SystemConfiguration::current();
        $config->update(['active_cycle' => 2]);

        $response = $this->actingAs($waka)->get('/waka/jam-pelajaran');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Waka/JamPelajaran/Index')
            ->has('timeSlots')
            ->where('activeCycle', 2)
        );
    }

    public function test_delete_time_slot_only_deletes_time_slot_and_preserves_schedules(): void
    {
        $waka = User::where('role_id', 1)->first();
        $slot = TimeSlot::first();
        $schedule = Schedule::where('start_slot_id', $slot->id)->first();
        $this->assertNotNull($schedule);

        $response = $this->actingAs($waka)->delete("/waka/jam-pelajaran/{$slot->id}");
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Pastikan slot terhapus
        $this->assertDatabaseMissing('time_slots', ['id' => $slot->id]);

        // Pastikan jadwal tetap ADA dan tidak terhapus
        $this->assertDatabaseHas('schedules', ['id' => $schedule->id]);
        $schedule->refresh();
        $this->assertNull($schedule->start_slot_id);
    }

    public function test_import_jadwal_maps_to_existing_time_slots(): void
    {
        $waka = User::where('role_id', 1)->first();
        $config = SystemConfiguration::current();
        $config->update(['is_scheduled_locked' => false]);

        Schedule::query()->delete();

        // Buat file CSV jadwal
        $csvContent = "Hari,Guru,Mata Pelajaran,Kelas,Ruangan,Sesi Mulai,Sesi Selesai,Putaran\n";
        $csvContent .= "Senin,Budi Santoso,Pemrograman Web,X RPL 1,Lab RPL 1,Jam ke-1,Jam ke-3,1\n";
        $csvContent .= "Selasa,Budi Santoso,Basis Data,X RPL 1,Lab RPL 1,Jam ke-4,Jam ke-6,1\n";

        $tempFile = tempnam(sys_get_temp_dir(), 'test_jadwal_') . '.csv';
        file_put_contents($tempFile, $csvContent);

        $uploadedFile = new \Illuminate\Http\UploadedFile(
            $tempFile,
            'jadwal_test.csv',
            'text/csv',
            null,
            true
        );

        $response = $this->actingAs($waka)->post('/waka/jadwal/import', [
            'file' => $uploadedFile,
            'cycle_number' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Pastikan jadwal terisi
        $this->assertEquals(2, Schedule::count());

        @unlink($tempFile);
    }
}

