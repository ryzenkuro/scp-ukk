<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Journal;
use App\Models\Schedule;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase5JournalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_guru_can_view_dashboard_with_schedules(): void
    {
        $guru = User::where('username', 'guru1')->first();

        $response = $this->actingAs($guru)->get('/guru/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Guru/Dashboard')
            ->has('todaySchedules')
            ->has('recentJournals')
            ->has('stats')
            ->has('config')
        );
    }

    public function test_guru_can_view_journal_index(): void
    {
        $guru = User::where('username', 'guru1')->first();

        $response = $this->actingAs($guru)->get('/guru/journal');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Guru/Journal/Index')
            ->has('journals')
            ->has('filters')
        );
    }

    public function test_guru_can_create_journal_and_attendances(): void
    {
        $guru = User::where('username', 'guru1')->first();
        $schedule = Schedule::where('teacher_id', $guru->id)->first();
        $students = Siswa::where('kelas', $schedule->class_name)->get();

        $this->assertNotEmpty($students);

        $attendanceData = [];
        foreach ($students as $index => $s) {
            $attendanceData[] = [
                'student_id' => $s->id,
                'status' => $index === 0 ? 'hadir' : 'sakit',
                'notes' => $index === 0 ? 'Mengikuti KBM tepat waktu' : 'Surat dokter terlampir',
            ];
        }

        Storage::fake('public');
        $photo = UploadedFile::fake()->image('kbm_clean_code.jpg', 800, 600);

        $response = $this->actingAs($guru)->post('/guru/journal', [
            'schedule_id' => $schedule->id,
            'date' => '2026-11-09',
            'topic' => 'Pengenalan Arsitektur Clean Code & MVC',
            'notes' => 'Kelas berlangsung kondusif dan siswa aktif bertanya',
            'documentation' => $photo,
            'attendances' => $attendanceData,
        ]);

        $response->assertRedirect('/guru/journal');
        $response->assertSessionHas('success');

        // 1. Verifikasi Jurnal dibuat
        $this->assertDatabaseHas('journals', [
            'schedule_id' => $schedule->id,
            'teacher_id' => $guru->id,
            'topic' => 'Pengenalan Arsitektur Clean Code & MVC',
        ]);

        $journal = Journal::where('topic', 'Pengenalan Arsitektur Clean Code & MVC')->first();
        $this->assertNotNull($journal);
        $this->assertEquals('2026-11-09', $journal->date->format('Y-m-d'));
        $this->assertNotNull($journal->documentation_path);
        Storage::disk('public')->assertExists($journal->documentation_path);

        // 2. Verifikasi Presensi Siswa dibuat
        $this->assertDatabaseHas('attendances', [
            'journal_id' => $journal->id,
            'student_id' => $students[0]->id,
            'status' => 'hadir',
        ]);

        if (count($students) > 1) {
            $this->assertDatabaseHas('attendances', [
                'journal_id' => $journal->id,
                'student_id' => $students[1]->id,
                'status' => 'sakit',
            ]);
        }
    }

    public function test_guru_cannot_create_journal_without_documentation_photo(): void
    {
        $guru = User::where('username', 'guru1')->first();
        $schedule = Schedule::where('teacher_id', $guru->id)->first();
        $students = Siswa::where('kelas', $schedule->class_name)->get();

        $attendanceData = [];
        foreach ($students as $s) {
            $attendanceData[] = [
                'student_id' => $s->id,
                'status' => 'hadir',
                'notes' => null,
            ];
        }

        $response = $this->actingAs($guru)->post('/guru/journal', [
            'schedule_id' => $schedule->id,
            'date' => '2026-11-09',
            'topic' => 'Jurnal Tanpa Foto',
            'notes' => 'Harus ditolak',
            'attendances' => $attendanceData,
        ]);

        $response->assertSessionHasErrors(['documentation']);
        $this->assertEquals(
            'Dokumentasi foto kegiatan KBM wajib dilampirkan (bisa foto langsung atau unggah file).',
            session('errors')->first('documentation')
        );
    }

    public function test_guru_can_edit_journal_and_update_attendance_status(): void
    {
        $guru = User::where('username', 'guru1')->first();
        $schedule = Schedule::where('teacher_id', $guru->id)->first();
        $student = Siswa::where('kelas', $schedule->class_name)->first();

        // Buat jurnal awal
        $journal = Journal::create([
            'schedule_id' => $schedule->id,
            'teacher_id' => $guru->id,
            'date' => '2026-11-09',
            'topic' => 'Materi Awal',
            'notes' => 'Catatan awal',
        ]);

        $attendance = Attendance::create([
            'journal_id' => $journal->id,
            'student_id' => $student->id,
            'schedule_id' => $schedule->id,
            'date' => '2026-11-09',
            'status' => 'hadir',
        ]);

        // Guru melakukan edit jurnal dan koreksi presensi (dari hadir menjadi izin)
        $response = $this->actingAs($guru)->put("/guru/journal/{$journal->id}", [
            'date' => '2026-11-09',
            'topic' => 'Materi Diperbarui: State Management',
            'notes' => 'Catatan perbaikan',
            'attendances' => [
                [
                    'student_id' => $student->id,
                    'status' => 'izin',
                    'notes' => 'Izin keperluan keluarga',
                ],
            ],
        ]);

        $response->assertRedirect('/guru/journal');
        $response->assertSessionHas('success');

        $journal->refresh();
        $attendance->refresh();

        $this->assertEquals('Materi Diperbarui: State Management', $journal->topic);
        $this->assertEquals('izin', $attendance->status);
    }

    public function test_guru_can_delete_journal_and_it_cascades_attendances(): void
    {
        $guru = User::where('username', 'guru1')->first();
        $schedule = Schedule::where('teacher_id', $guru->id)->first();
        $student = Siswa::where('kelas', $schedule->class_name)->first();

        $journal = Journal::create([
            'schedule_id' => $schedule->id,
            'teacher_id' => $guru->id,
            'date' => '2026-11-09',
            'topic' => 'Materi yang akan dihapus',
        ]);

        $attendance = Attendance::create([
            'journal_id' => $journal->id,
            'student_id' => $student->id,
            'schedule_id' => $schedule->id,
            'date' => '2026-11-09',
            'status' => 'hadir',
        ]);

        $response = $this->actingAs($guru)->delete("/guru/journal/{$journal->id}");
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('journals', ['id' => $journal->id]);
        $this->assertDatabaseMissing('attendances', ['id' => $attendance->id]);
    }

    public function test_guru_cannot_modify_other_teachers_journal(): void
    {
        $guru1 = User::where('username', 'guru1')->first();
        $guru2 = User::where('username', 'guru2')->first();
        $schedule = Schedule::where('teacher_id', $guru1->id)->first();

        $journal = Journal::create([
            'schedule_id' => $schedule->id,
            'teacher_id' => $guru1->id,
            'date' => '2026-11-09',
            'topic' => 'Materi Milik Guru 1',
        ]);

        // Guru 2 mencoba mengedit jurnal Guru 1
        $response = $this->actingAs($guru2)->put("/guru/journal/{$journal->id}", [
            'date' => '2026-11-09',
            'topic' => 'Coba bajak jurnal',
            'attendances' => [],
        ]);

        $response->assertStatus(403);

        // Guru 2 mencoba menghapus jurnal Guru 1
        $delResponse = $this->actingAs($guru2)->delete("/guru/journal/{$journal->id}");
        $delResponse->assertStatus(403);
    }
}
