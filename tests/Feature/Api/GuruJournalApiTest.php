<?php

namespace Tests\Feature\Api;

use App\Models\Journal;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GuruJournalApiTest extends TestCase
{
    public function test_teacher_can_fetch_schedules_with_journal_status(): void
    {
        $teacher = User::where('username', 'guru1')->first();

        $response = $this->actingAs($teacher, 'sanctum')
            ->getJson(route('api.guru.schedules'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'active_cycle',
                'selected_cycle',
                'current_day',
                'is_schedule_locked',
                'data' => [
                    '*' => [
                        'id',
                        'subject',
                        'day_of_week',
                        'start_time',
                        'end_time',
                        'cycle_type',
                        'class_id',
                        'class_name',
                        'room_id',
                        'room_name',
                        'has_journal',
                    ],
                ],
            ]);
    }

    private function getOrCreateSchedule(User $teacher): Schedule
    {
        $schedule = Schedule::where('teacher_id', $teacher->id)->first();
        if ($schedule) {
            return $schedule;
        }

        $class = SchoolClass::first() ?? SchoolClass::create(['class_name' => 'XII RPL 1']);
        $room = Room::first() ?? Room::create(['room_name' => 'Lab RPL 1']);

        return Schedule::create([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'room_id' => $room->id,
            'cycle_type' => 'Putaran 1',
            'day_of_week' => 'Senin',
            'start_time' => '07:00:00',
            'end_time' => '09:15:00',
            'subject' => 'Pemrograman Perangkat Bergerak',
        ]);
    }

    public function test_teacher_can_fetch_students_for_schedule(): void
    {
        $teacher = User::where('username', 'guru1')->first();
        $schedule = $this->getOrCreateSchedule($teacher);

        $response = $this->actingAs($teacher, 'sanctum')
            ->getJson(route('api.guru.schedules.students', $schedule->id));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'schedule',
                'date',
                'total',
                'students' => [
                    '*' => [
                        'id',
                        'username',
                        'fullname',
                        'default_status',
                        'is_approved_leave',
                    ],
                ],
            ]);
    }

    public function test_teacher_can_submit_journal_and_attendances_via_api(): void
    {
        Storage::fake('public');

        $teacher = User::where('username', 'guru1')->first();
        $schedule = $this->getOrCreateSchedule($teacher);

        // Bersihkan jurnal hari ini jika sudah ada
        Journal::where('schedule_id', $schedule->id)
            ->where('date', now()->toDateString())
            ->delete();

        // Ambil siswa di kelas tersebut
        $students = User::where('class_id', $schedule->class_id)
            ->whereHas('role', fn($q) => $q->where('role_name', 'siswa'))
            ->get();

        $attendances = [];
        foreach ($students as $student) {
            $attendances[$student->id] = 'Hadir';
        }

        $photo = UploadedFile::fake()->image('tefa_lab_session.jpg', 800, 600);

        $payload = [
            'schedule_id'       => $schedule->id,
            'topic_description' => 'Implementasi REST API Mobile Sanctum dan Autentikasi Menggunakan Flutter Clean Architecture',
            'photo'             => $photo,
            'attendances'       => $attendances,
        ];

        $response = $this->actingAs($teacher, 'sanctum')
            ->post(route('api.guru.journals.store'), $payload);

        $response->assertStatus(201)
            ->assertJson([
                'status'  => 'success',
                'message' => 'Jurnal mengajar dan presensi kelas berhasil disimpan!',
            ])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'date',
                    'topic_description',
                    'photo_url',
                    'class_name',
                    'subject',
                    'total_students',
                ],
            ]);

        $journalId = $response->json('data.id');
        $this->assertDatabaseHas('journals', [
            'id'          => $journalId,
            'schedule_id' => $schedule->id,
        ]);

        // Verifikasi detail jurnal
        $detailRes = $this->actingAs($teacher, 'sanctum')
            ->getJson(route('api.guru.journals.show', $journalId));

        $detailRes->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'stats' => ['total', 'hadir', 'izin', 'sakit', 'alpha'],
                    'attendances',
                ],
            ]);
    }
}

