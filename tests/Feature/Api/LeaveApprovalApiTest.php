<?php

namespace Tests\Feature\Api;

use App\Models\LeaveRequest;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\User;
use Tests\TestCase;

class LeaveApprovalApiTest extends TestCase
{
    private function ensureApprovalTestData(): array
    {
        $classA = SchoolClass::firstOrCreate(['class_name' => 'XII RPL 1']);
        $classB = SchoolClass::firstOrCreate(['class_name' => 'XII RPL 2']);

        $roleWali = Role::where('role_name', 'wali_kelas')->first() ?? Role::create(['role_name' => 'wali_kelas']);
        $roleSiswa = Role::where('role_name', 'siswa')->first() ?? Role::create(['role_name' => 'siswa']);

        $waliKelasA = User::firstOrCreate(['username' => 'wali_a_test'], [
            'fullname' => 'Wali Kelas XII RPL 1',
            'role_id'  => $roleWali->id,
            'class_id' => $classA->id,
            'password' => bcrypt('password'),
        ]);
        if ($waliKelasA->class_id !== $classA->id) {
            $waliKelasA->update(['class_id' => $classA->id]);
        }

        $studentA = User::firstOrCreate(['username' => 'siswa_a_test'], [
            'fullname' => 'Siswa Kelas A',
            'role_id'  => $roleSiswa->id,
            'class_id' => $classA->id,
            'password' => bcrypt('password'),
        ]);

        $studentB = User::firstOrCreate(['username' => 'siswa_b_test'], [
            'fullname' => 'Siswa Kelas B',
            'role_id'  => $roleSiswa->id,
            'class_id' => $classB->id,
            'password' => bcrypt('password'),
        ]);

        return [
            'wali_a'    => $waliKelasA,
            'student_a' => $studentA,
            'student_b' => $studentB,
            'class_a'   => $classA,
            'class_b'   => $classB,
        ];
    }

    public function test_homeroom_teacher_can_fetch_pending_leave_queue(): void
    {
        $data = $this->ensureApprovalTestData();
        $wali = $data['wali_a'];
        $studentA = $data['student_a'];

        $leave = LeaveRequest::create([
            'student_id'      => $studentA->id,
            'applicant_id'    => $studentA->id,
            'leave_type'      => 'Sakit',
            'start_date'      => now()->addDays(20)->toDateString(),
            'end_date'        => now()->addDays(21)->toDateString(),
            'reason'          => 'Surat sakit dokter puskesmas',
            'document_path'   => 'leave_documents/sample.jpg',
            'status_approval' => 'Pending',
        ]);

        $response = $this->actingAs($wali, 'sanctum')
            ->getJson(route('api.approval.leave-requests.index', ['status' => 'Pending']));

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ])
            ->assertJsonStructure([
                'status',
                'pending_count',
                'data' => [
                    '*' => [
                        'id',
                        'student_id',
                        'student_name',
                        'class_name',
                        'leave_type',
                        'start_date',
                        'end_date',
                        'reason',
                        'status_approval',
                    ],
                ],
            ]);

        $this->assertContains($leave->id, collect($response->json('data'))->pluck('id')->toArray());
    }

    public function test_homeroom_teacher_can_approve_leave_request(): void
    {
        $data = $this->ensureApprovalTestData();
        $wali = $data['wali_a'];
        $studentA = $data['student_a'];

        $leave = LeaveRequest::create([
            'student_id'      => $studentA->id,
            'applicant_id'    => $studentA->id,
            'leave_type'      => 'Izin',
            'start_date'      => now()->addDays(25)->toDateString(),
            'end_date'        => now()->addDays(25)->toDateString(),
            'reason'          => 'Acara keluarga di luar kota',
            'document_path'   => 'leave_documents/sample.jpg',
            'status_approval' => 'Pending',
        ]);

        $response = $this->actingAs($wali, 'sanctum')
            ->patchJson(route('api.approval.leave-requests.verify', $leave->id), [
                'status' => 'Approved',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data'   => [
                    'id'              => $leave->id,
                    'status_approval' => 'Approved',
                ],
            ]);

        $this->assertDatabaseHas('leave_requests', [
            'id'              => $leave->id,
            'status_approval' => 'Approved',
            'verified_by'     => $wali->id,
        ]);
    }

    public function test_homeroom_teacher_can_reject_leave_with_note(): void
    {
        $data = $this->ensureApprovalTestData();
        $wali = $data['wali_a'];
        $studentA = $data['student_a'];

        $leave = LeaveRequest::create([
            'student_id'      => $studentA->id,
            'applicant_id'    => $studentA->id,
            'leave_type'      => 'Sakit',
            'start_date'      => now()->addDays(30)->toDateString(),
            'end_date'        => now()->addDays(30)->toDateString(),
            'reason'          => 'Sakit flu',
            'document_path'   => 'leave_documents/sample.jpg',
            'status_approval' => 'Pending',
        ]);

        $response = $this->actingAs($wali, 'sanctum')
            ->patchJson(route('api.approval.leave-requests.verify', $leave->id), [
                'status'         => 'Rejected',
                'rejection_note' => 'Lampiran surat dokter tidak terbaca, silakan unggah ulang.',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data'   => [
                    'id'              => $leave->id,
                    'status_approval' => 'Rejected',
                ],
            ]);

        $this->assertDatabaseHas('leave_requests', [
            'id'              => $leave->id,
            'status_approval' => 'Rejected',
            'verified_by'     => $wali->id,
        ]);
    }

    public function test_homeroom_teacher_cannot_verify_student_from_other_class(): void
    {
        $data = $this->ensureApprovalTestData();
        $wali = $data['wali_a'];
        $studentB = $data['student_b']; // Kelas B

        $leave = LeaveRequest::create([
            'student_id'      => $studentB->id,
            'applicant_id'    => $studentB->id,
            'leave_type'      => 'Sakit',
            'start_date'      => now()->addDays(35)->toDateString(),
            'end_date'        => now()->addDays(35)->toDateString(),
            'reason'          => 'Sakit demam',
            'document_path'   => 'leave_documents/sample.jpg',
            'status_approval' => 'Pending',
        ]);

        $response = $this->actingAs($wali, 'sanctum')
            ->patchJson(route('api.approval.leave-requests.verify', $leave->id), [
                'status' => 'Approved',
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
            ]);
    }
}

