<?php

namespace Tests\Feature;

use App\Models\LeaveRequest;
use App\Models\Schedule;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageERestApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_login_success_and_returns_token(): void
    {
        $user = User::where('role_id', 1)->first();

        $response = $this->postJson('/api/login', [
            'username' => $user->username,
            'password' => 'smkn1bantul2026',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'user' => ['id', 'username', 'fullname', 'role_id'],
                'token_type',
                'access_token',
            ],
        ]);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/login', [
            'username' => 'nonexistent',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Username atau password salah',
        ]);
    }

    public function test_public_endpoints_list_siswas_and_schedules(): void
    {
        $resSiswas = $this->getJson('/api/siswas');
        $resSiswas->assertStatus(200);
        $resSiswas->assertJsonStructure([
            'status',
            'total',
            'data',
        ]);

        $resSchedules = $this->getJson('/api/schedules');
        $resSchedules->assertStatus(200);
        $resSchedules->assertJsonStructure([
            'status',
            'total',
            'data',
        ]);
    }

    public function test_me_and_profile_endpoint_with_sanctum_token(): void
    {
        $user = User::where('role_id', 1)->first();
        $token = $user->createToken('test_token')->plainTextToken;

        $resMe = $this->withHeader('Authorization', 'Bearer ' . $token)->getJson('/api/me');
        $resMe->assertStatus(200);
        $resMe->assertJson(['status' => 'success']);

        $resProfile = $this->withHeader('Authorization', 'Bearer ' . $token)->getJson('/api/profile');
        $resProfile->assertStatus(200);
        $resProfile->assertJsonPath('data.username', $user->username);
    }

    public function test_siswa_crud_protected_endpoint(): void
    {
        $waka = User::where('role_id', 1)->first();
        $token = $waka->createToken('waka_token')->plainTextToken;

        // Create Siswa via API
        $resCreate = $this->withHeader('Authorization', 'Bearer ' . $token)->postJson('/api/siswas', [
            'name' => 'API Student Test',
            'nis' => 'API9901',
            'kelas' => 'X RPL 1',
            'jurusan' => 'RPL',
            'alamat' => 'Yogyakarta',
        ]);
        $resCreate->assertStatus(201);
        $studentId = $resCreate->json('data.id');

        $this->assertDatabaseHas('siswas', ['id' => $studentId, 'nis' => 'API9901']);

        // Update Siswa via API
        $resUpdate = $this->withHeader('Authorization', 'Bearer ' . $token)->putJson("/api/siswas/{$studentId}", [
            'name' => 'API Student Updated',
        ]);
        $resUpdate->assertStatus(200);
        $this->assertDatabaseHas('siswas', ['id' => $studentId, 'name' => 'API Student Updated']);

        // Delete Siswa via API
        $resDelete = $this->withHeader('Authorization', 'Bearer ' . $token)->deleteJson("/api/siswas/{$studentId}");
        $resDelete->assertStatus(200);
        $this->assertDatabaseMissing('siswas', ['id' => $studentId]);
    }

    public function test_attendance_and_journal_creation_via_api(): void
    {
        $guru = User::where('role_id', 2)->first();
        $token = $guru->createToken('guru_token')->plainTextToken;
        $schedule = Schedule::where('teacher_id', $guru->id)->first();
        $student = Siswa::first();

        // Store Journal
        $resJournal = $this->withHeader('Authorization', 'Bearer ' . $token)->postJson('/api/journals', [
            'schedule_id' => $schedule->id,
            'date' => '2026-11-20',
            'topic' => 'Pengenalan REST API Sanctum',
            'notes' => 'Siswa praktik login dan query token.',
        ]);
        $resJournal->assertStatus(201);
        $journalId = $resJournal->json('data.id');

        // Store Attendance
        $resAttendance = $this->withHeader('Authorization', 'Bearer ' . $token)->postJson('/api/attendances', [
            'journal_id' => $journalId,
            'student_id' => $student->id,
            'schedule_id' => $schedule->id,
            'date' => '2026-11-20',
            'status' => 'hadir',
            'notes' => 'Tepat waktu',
        ]);
        $resAttendance->assertStatus(201);
        $this->assertDatabaseHas('attendances', [
            'journal_id' => $journalId,
            'student_id' => $student->id,
            'status' => 'hadir',
        ]);
    }

    public function test_leave_request_submission_and_approval_via_api(): void
    {
        $studentUser = User::where('role_id', 3)->first();
        $studentToken = $studentUser->createToken('student_token')->plainTextToken;
        $student = $studentUser->student;

        // Student submits leave
        $resSubmit = $this->withHeader('Authorization', 'Bearer ' . $studentToken)->postJson('/api/leave-requests', [
            'student_id' => $student->id,
            'start_date' => '2026-11-25',
            'end_date' => '2026-11-26',
            'reason' => 'Periksa kesehatan gigi',
            'type' => 'sakit',
        ]);
        $resSubmit->assertStatus(201);
        $leaveId = $resSubmit->json('data.id');

        // Waka / Wali Kelas approves leave
        $waka = User::where('role_id', 1)->first();
        $wakaToken = $waka->createToken('waka_token')->plainTextToken;

        $resApprove = $this->withHeader('Authorization', 'Bearer ' . $wakaToken)->putJson("/api/leave-requests/{$leaveId}/status", [
            'status' => 'approved',
        ]);
        $resApprove->assertStatus(200);

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveId,
            'status' => 'approved',
        ]);

        // Verified auto sync attendance created for those dates
        $this->assertDatabaseHas('attendances', [
            'student_id' => $student->id,
            'status' => 'sakit',
        ]);
    }

    public function test_logout_revokes_token(): void
    {
        $user = User::where('role_id', 1)->first();
        $token = $user->createToken('temp_token')->plainTextToken;

        $resLogout = $this->withHeader('Authorization', 'Bearer ' . $token)->postJson('/api/logout');
        $resLogout->assertStatus(200);
        $resLogout->assertJson(['status' => 'success', 'message' => 'Logout berhasil']);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'temp_token',
        ]);

        $this->app['auth']->forgetGuards();

        // Next request with revoked token fails authentication
        $resMe = $this->withHeader('Authorization', 'Bearer ' . $token)->getJson('/api/me');
        $resMe->assertStatus(401);
    }
}
