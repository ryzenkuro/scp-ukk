<?php

namespace Tests\Feature\Api;

use App\Models\Attendance;
use App\Models\Journal;
use App\Models\LeaveRequest;
use App\Models\Role;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentPortalApiTest extends TestCase
{
    private function ensureTestData(): array
    {
        $class = SchoolClass::first() ?? SchoolClass::create(['class_name' => 'XII RPL 1']);
        $room = Room::first() ?? Room::create(['room_name' => 'Lab RPL 1']);

        $roleSiswa = Role::where('role_name', 'siswa')->first() ?? Role::create(['role_name' => 'siswa']);
        $roleOrtu = Role::where('role_name', 'orang_tua')->first() ?? Role::create(['role_name' => 'orang_tua']);
        $roleGuru = Role::where('role_name', 'guru')->first() ?? Role::create(['role_name' => 'guru']);

        $parent = User::firstOrCreate(['username' => 'ortu_test'], [
            'fullname' => 'Orang Tua Siswa Test',
            'role_id'  => $roleOrtu->id,
            'password' => bcrypt('password'),
        ]);

        $student = User::firstOrCreate(['username' => 'siswa_test'], [
            'fullname'  => 'Siswa Bintang RPL',
            'role_id'   => $roleSiswa->id,
            'parent_id' => $parent->id,
            'class_id'  => $class->id,
            'password'  => bcrypt('password'),
            'nisn_nip'  => '0051234567',
        ]);

        // Pastikan relasi parent_id tersimpan
        if ($student->parent_id !== $parent->id || $student->class_id !== $class->id) {
            $student->update(['parent_id' => $parent->id, 'class_id' => $class->id]);
        }

        $teacher = User::firstOrCreate(['username' => 'guru_test'], [
            'fullname' => 'Guru Pengampu Test',
            'role_id'  => $roleGuru->id,
            'password' => bcrypt('password'),
        ]);

        $schedule = Schedule::firstOrCreate([
            'teacher_id'  => $teacher->id,
            'class_id'    => $class->id,
            'room_id'     => $room->id,
            'cycle_type'  => 'Putaran 1',
            'day_of_week' => 'Senin',
            'start_time'  => '07:00:00',
            'end_time'    => '09:15:00',
        ], [
            'subject' => 'Pemrograman Perangkat Bergerak',
        ]);

        return [
            'student'  => $student,
            'parent'   => $parent,
            'teacher'  => $teacher,
            'schedule' => $schedule,
            'class'    => $class,
        ];
    }

    public function test_student_can_fetch_class_schedules(): void
    {
        $data = $this->ensureTestData();
        $student = $data['student'];

        $response = $this->actingAs($student, 'sanctum')
            ->getJson(route('api.portal.schedules', ['day' => 'Senin', 'cycle' => 'Putaran 1']));

        $response->assertStatus(200)
            ->assertJson([
                'status'         => 'success',
                'selected_cycle' => 'Putaran 1',
                'selected_day'   => 'Senin',
            ])
            ->assertJsonStructure([
                'status',
                'active_cycle',
                'selected_cycle',
                'student' => ['id', 'fullname', 'nisn', 'class_name'],
                'data'    => [
                    '*' => [
                        'id',
                        'subject',
                        'teacher_name',
                        'room_name',
                        'class_name',
                        'day_of_week',
                        'start_time',
                        'end_time',
                        'cycle_type',
                    ],
                ],
            ]);
    }

    public function test_student_can_fetch_attendance_summary_and_stats(): void
    {
        $data = $this->ensureTestData();
        $student = $data['student'];
        $schedule = $data['schedule'];

        // Buat dummy journal & attendance jika belum ada
        $journal = Journal::firstOrCreate([
            'schedule_id' => $schedule->id,
            'date'        => now()->toDateString(),
        ], [
            'topic_description' => 'Materi Praktik Flutter State Management BLoC',
            'photo_path'        => 'lab_journals/sample.jpg',
        ]);

        Attendance::firstOrCreate([
            'journal_id' => $journal->id,
            'student_id' => $student->id,
            'date'       => now()->toDateString(),
        ], [
            'status' => 'Hadir',
        ]);

        $response = $this->actingAs($student, 'sanctum')
            ->getJson(route('api.portal.attendance.summary'));

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ])
            ->assertJsonStructure([
                'status',
                'student' => ['id', 'fullname', 'nisn', 'class_name'],
                'stats'   => [
                    'total',
                    'hadir',
                    'sakit',
                    'izin',
                    'alpha',
                    'attendance_percentage',
                ],
                'history',
            ]);
    }

    public function test_student_and_parent_can_submit_and_view_leave_requests(): void
    {
        Storage::fake('public');
        $data = $this->ensureTestData();
        $student = $data['student'];
        $parent = $data['parent'];

        $today = now()->addDays(5)->toDateString();
        $tomorrow = now()->addDays(6)->toDateString();

        // Bersihkan izin siswa ini sebelum pengujian
        LeaveRequest::where('student_id', $student->id)->delete();

        $file = UploadedFile::fake()->image('surat_dokter.jpg', 600, 800);

        // 1. Siswa mengajukan surat izin sakit
        $response = $this->actingAs($student, 'sanctum')
            ->post(route('api.portal.leave-requests.store'), [
                'leave_type' => 'Sakit',
                'start_date' => $today,
                'end_date'   => $tomorrow,
                'reason'     => 'Demam tinggi dan istirahat dokter selama 2 hari',
                'document'   => $file,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'status'  => 'success',
                'message' => 'Surat permohonan izin berhasil diajukan dan sedang menunggu verifikasi Wali Kelas / Guru BK.',
            ])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'leave_type',
                    'start_date',
                    'end_date',
                    'reason',
                    'document_url',
                    'status_approval',
                ],
            ]);

        $leaveId = $response->json('data.id');
        $this->assertDatabaseHas('leave_requests', [
            'id'              => $leaveId,
            'student_id'      => $student->id,
            'status_approval' => 'Pending',
        ]);

        // 2. Anti-overlap: pengajuan ulang pada rentang waktu yang sama harus ditolak
        $overlapResponse = $this->actingAs($student, 'sanctum')
            ->post(route('api.portal.leave-requests.store'), [
                'leave_type' => 'Izin',
                'start_date' => $today,
                'end_date'   => $tomorrow,
                'reason'     => 'Acara keluarga',
                'document'   => $file,
            ]);

        $overlapResponse->assertStatus(422)
            ->assertJson([
                'status' => 'error',
            ]);

        // 3. Orang tua dapat melihat riwayat izin anak
        $parentResponse = $this->actingAs($parent, 'sanctum')
            ->getJson(route('api.portal.leave-requests.index'));

        $parentResponse->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ])
            ->assertJsonFragment([
                'id'         => $leaveId,
                'leave_type' => 'Sakit',
            ]);
    }

    public function test_parent_can_fetch_linked_children(): void
    {
        $data = $this->ensureTestData();
        $parent = $data['parent'];
        $student = $data['student'];

        $response = $this->actingAs($parent, 'sanctum')
            ->getJson(route('api.portal.children'));

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ])
            ->assertJsonFragment([
                'id'       => $student->id,
                'fullname' => $student->fullname,
            ]);
    }
}

