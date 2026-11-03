<?php

namespace Tests\Feature\Api;

use App\Models\Room;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\SystemConfiguration;
use App\Models\User;
use Tests\TestCase;

class ScheduleRestApiTest extends TestCase
{
    public function test_get_schedules_requires_auth_and_guru_role(): void
    {
        // 1. Tanpa token -> 401 Unauthorized
        $responseUnauth = $this->getJson(route('api.schedules.index'));
        $responseUnauth->assertStatus(401);

        // 2. Dengan akun siswa -> 403 Forbidden
        $this->app['auth']->forgetGuards();
        $siswa = User::where('username', 'siswa1')->first();
        $siswaToken = $siswa->createToken('test')->plainTextToken;
        $responseForbidden = $this->withHeader('Authorization', 'Bearer ' . $siswaToken)
            ->getJson(route('api.schedules.index'));
        $responseForbidden->assertStatus(403);

        // 3. Dengan akun guru -> 200 OK dengan format JSON terstruktur
        $this->app['auth']->forgetGuards();
        $guru = User::where('username', 'guru1')->first();
        $guruToken = $guru->createToken('test')->plainTextToken;
        $responseGuru = $this->withHeader('Authorization', 'Bearer ' . $guruToken)
            ->getJson(route('api.schedules.index'));

        $responseGuru->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'active_cycle',
                'selected_cycle',
                'is_schedule_locked',
                'data',
            ]);
    }

    public function test_post_and_delete_schedules_via_api_with_conflict_and_lock_protections(): void
    {
        $guru1 = User::where('username', 'guru1')->first();
        $guru2 = User::where('username', 'guru2')->first();
        $class1 = SchoolClass::first();
        $room = Room::first();

        $config = SystemConfiguration::current();
        $config->update([
            'active_cycle' => 'Putaran 1',
            'is_schedule_locked' => false,
        ]);

        $guru1Token = $guru1->createToken('test')->plainTextToken;
        $guru2Token = $guru2->createToken('test')->plainTextToken;

        // Bersihkan data test lama jika ada
        Schedule::where('subject', 'REST API Testing Schedule')->delete();

        // 1. Guru1 membuat jadwal baru via POST /api/schedules -> 201 Created
        $responseCreate = $this->withHeader('Authorization', 'Bearer ' . $guru1Token)
            ->postJson(route('api.schedules.store'), [
                'class_id' => $class1->id,
                'room_id' => $room->id,
                'subject' => 'REST API Testing Schedule',
                'day_of_week' => 'Sabtu',
                'start_time' => '13:00',
                'end_time' => '15:00',
                'cycle_type' => 'Putaran 1',
            ]);

        $responseCreate->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Jadwal mengajar berhasil disimpan!',
            ]);

        $createdScheduleId = $responseCreate->json('data.id');
        $this->assertNotNull($createdScheduleId);

        // 2. Guru2 mencoba membuat jadwal di ruangan yang sama pada waktu bertabrakan -> 422 Unprocessable (Anti-Bentrok 3D)
        $this->app['auth']->forgetGuards();
        $responseConflict = $this->withHeader('Authorization', 'Bearer ' . $guru2Token)
            ->postJson(route('api.schedules.store'), [
                'class_id' => $class1->id,
                'room_id' => $room->id,
                'subject' => 'Jadwal Bertabrakan',
                'day_of_week' => 'Sabtu',
                'start_time' => '14:00',
                'end_time' => '16:00',
                'cycle_type' => 'Putaran 1',
            ]);

        $responseConflict->assertStatus(422)
            ->assertJson([
                'status' => 'error',
            ]);

        // 3. Kunci jadwal oleh Waka (is_schedule_locked = true)
        $config->update(['is_schedule_locked' => true]);

        // Guru1 mencoba menambah jadwal saat dikunci -> 403 Forbidden
        $this->app['auth']->forgetGuards();
        $responseLocked = $this->withHeader('Authorization', 'Bearer ' . $guru1Token)
            ->postJson(route('api.schedules.store'), [
                'class_id' => $class1->id,
                'room_id' => $room->id,
                'subject' => 'Mencoba Tambah Saat Terkunci',
                'day_of_week' => 'Sabtu',
                'start_time' => '15:30',
                'end_time' => '17:00',
                'cycle_type' => 'Putaran 1',
            ]);

        $responseLocked->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'message' => 'Jadwal telah dikunci oleh Waka Kurikulum. Perubahan tidak diizinkan.',
            ]);

        // 4. Buka kembali kunci jadwal
        $config->update(['is_schedule_locked' => false]);

        // Guru2 mencoba menghapus jadwal milik Guru1 -> 403 Forbidden
        $this->app['auth']->forgetGuards();
        $responseDeleteForbidden = $this->withHeader('Authorization', 'Bearer ' . $guru2Token)
            ->deleteJson(route('api.schedules.destroy', $createdScheduleId));

        $responseDeleteForbidden->assertStatus(403);

        // Guru1 menghapus jadwal miliknya sendiri -> 200 OK
        $this->app['auth']->forgetGuards();
        $responseDelete = $this->withHeader('Authorization', 'Bearer ' . $guru1Token)
            ->deleteJson(route('api.schedules.destroy', $createdScheduleId));

        $responseDelete->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Jadwal berhasil dihapus!',
            ]);

        $this->assertDatabaseMissing('schedules', ['id' => $createdScheduleId]);
    }
}
