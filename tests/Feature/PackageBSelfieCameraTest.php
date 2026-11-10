<?php

namespace Tests\Feature;

use App\Models\LeaveRequest;
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

    public function test_student_can_submit_leave_request_with_selfie_photo_and_document(): void
    {
        Storage::fake('public');

        $studentUser = User::where('role_id', 3)->first();
        $studentUser->update(['must_change_credentials' => false]);
        $student = $studentUser->student;

        $selfieFile = UploadedFile::fake()->image('selfie_watermarked.jpg', 1280, 720);
        $docFile = UploadedFile::fake()->create('surat_dokter.pdf', 300, 'application/pdf');

        $response = $this->actingAs($studentUser)->post('/siswa/izin', [
            'type' => 'sakit',
            'start_date' => '2026-11-12',
            'end_date' => '2026-11-13',
            'reason' => 'Demam tinggi dan istirahat dokter',
            'selfie' => $selfieFile,
            'attachment' => $docFile,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $leave = LeaveRequest::where('student_id', $student->id)->latest('id')->first();
        $this->assertNotNull($leave);
        $this->assertEquals('sakit', $leave->type);
        $this->assertNotNull($leave->selfie_path);
        $this->assertNotNull($leave->attachment_path);

        Storage::disk('public')->assertExists($leave->selfie_path);
        Storage::disk('public')->assertExists($leave->attachment_path);
    }

    public function test_student_cannot_submit_leave_request_without_selfie(): void
    {
        $studentUser = User::where('role_id', 3)->first();
        $studentUser->update(['must_change_credentials' => false]);

        $docFile = UploadedFile::fake()->create('surat_dokter.pdf', 300, 'application/pdf');

        $response = $this->actingAs($studentUser)->post('/siswa/izin', [
            'type' => 'izin',
            'start_date' => '2026-11-15',
            'end_date' => '2026-11-16',
            'reason' => 'Keperluan keluarga penting',
            'attachment' => $docFile,
        ]);

        $response->assertSessionHasErrors('selfie');
    }

    public function test_student_cannot_submit_leave_request_without_attachment(): void
    {
        $studentUser = User::where('role_id', 3)->first();
        $studentUser->update(['must_change_credentials' => false]);

        $selfieFile = UploadedFile::fake()->image('selfie.jpg', 640, 480);

        $response = $this->actingAs($studentUser)->post('/siswa/izin', [
            'type' => 'izin',
            'start_date' => '2026-11-15',
            'end_date' => '2026-11-16',
            'reason' => 'Keperluan keluarga penting',
            'selfie' => $selfieFile,
        ]);

        $response->assertSessionHasErrors('attachment');
    }

    public function test_student_cannot_submit_identical_file_for_both_selfie_and_attachment(): void
    {
        $studentUser = User::where('role_id', 3)->first();
        $studentUser->update(['must_change_credentials' => false]);

        $sameContent = 'identical_fake_image_bytes_1234567890';
        $selfieFile = UploadedFile::fake()->createWithContent('file1.jpg', $sameContent);
        $docFile = UploadedFile::fake()->createWithContent('file2.jpg', $sameContent);

        $response = $this->actingAs($studentUser)->post('/siswa/izin', [
            'type' => 'sakit',
            'start_date' => '2026-11-15',
            'end_date' => '2026-11-16',
            'reason' => 'Mencoba upload berkas yang sama',
            'selfie' => $selfieFile,
            'attachment' => $docFile,
        ]);

        $response->assertSessionHasErrors('attachment');
    }

    public function test_submitting_invalid_attachment_format_fails_validation(): void
    {
        $studentUser = User::where('role_id', 3)->first();
        $studentUser->update(['must_change_credentials' => false]);

        $selfieFile = UploadedFile::fake()->image('selfie.jpg', 640, 480);
        $invalidFile = UploadedFile::fake()->create('document.exe', 500);

        $response = $this->actingAs($studentUser)->post('/siswa/izin', [
            'type' => 'sakit',
            'start_date' => '2026-11-12',
            'end_date' => '2026-11-13',
            'reason' => 'Sakit perut',
            'selfie' => $selfieFile,
            'attachment' => $invalidFile,
        ]);

        $response->assertSessionHasErrors('attachment');
    }
}
