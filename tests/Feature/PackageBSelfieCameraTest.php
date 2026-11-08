<?php

namespace Tests\Feature;

use App\Models\LeaveRequest;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PackageBSelfieCameraTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_student_can_submit_leave_request_with_selfie_photo(): void
    {
        Storage::fake('public');

        $studentUser = User::where('role_id', 3)->first();
        $studentUser->update(['must_change_credentials' => false]);
        $student = $studentUser->student;

        $photoFile = UploadedFile::fake()->image('selfie_watermarked.jpg', 1280, 720);

        $response = $this->actingAs($studentUser)->post('/siswa/izin', [
            'type' => 'sakit',
            'start_date' => '2026-11-12',
            'end_date' => '2026-11-13',
            'reason' => 'Demam tinggi dan istirahat dokter',
            'attachment' => $photoFile,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $leave = LeaveRequest::where('student_id', $student->id)->latest('id')->first();
        $this->assertNotNull($leave);
        $this->assertEquals('sakit', $leave->type);
        $this->assertNotNull($leave->attachment_path);

        Storage::disk('public')->assertExists($leave->attachment_path);
    }

    public function test_student_cannot_submit_leave_request_without_attachment(): void
    {
        $studentUser = User::where('role_id', 3)->first();
        $studentUser->update(['must_change_credentials' => false]);
        $student = $studentUser->student;

        $response = $this->actingAs($studentUser)->post('/siswa/izin', [
            'type' => 'izin',
            'start_date' => '2026-11-15',
            'end_date' => '2026-11-15',
            'reason' => 'Keperluan keluarga penting',
        ]);

        $response->assertSessionHasErrors('attachment');
    }

    public function test_submitting_invalid_attachment_format_fails_validation(): void
    {
        $studentUser = User::where('role_id', 3)->first();
        $studentUser->update(['must_change_credentials' => false]);

        $invalidFile = UploadedFile::fake()->create('document.exe', 500);

        $response = $this->actingAs($studentUser)->post('/siswa/izin', [
            'type' => 'sakit',
            'start_date' => '2026-11-12',
            'end_date' => '2026-11-12',
            'reason' => 'Sakit perut',
            'attachment' => $invalidFile,
        ]);

        $response->assertSessionHasErrors('attachment');
    }
}

