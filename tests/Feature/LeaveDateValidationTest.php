<?php

namespace Tests\Feature;

use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LeaveDateValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_cannot_submit_leave_when_end_date_is_earlier_than_start_date(): void
    {
        Storage::fake('public');

        $studentUser = User::where('role_id', 3)->first();
        $studentUser->update(['must_change_credentials' => false]);

        $selfieFile = UploadedFile::fake()->image('selfie.jpg', 640, 480);
        $photoFile = UploadedFile::fake()->image('surat_dokter.jpg', 800, 600);

        // Mulai: 9 Nov 2026, Sampai: 8 Nov 2026 (kurang dari mulai tanggal) -> HARUS DITOLAK
        $response = $this->actingAs($studentUser)->post('/siswa/izin', [
            'type' => 'sakit',
            'start_date' => '2026-11-09',
            'end_date' => '2026-11-08',
            'reason' => 'Demam tinggi',
            'selfie' => $selfieFile,
            'attachment' => $photoFile,
        ]);

        $response->assertSessionHasErrors(['end_date']);
        $this->assertEquals(
            'Sampai tanggal tidak boleh lebih awal dari mulai tanggal.',
            session('errors')->first('end_date')
        );
    }

    public function test_can_submit_leave_when_end_date_is_equal_to_start_date(): void
    {
        Storage::fake('public');

        $studentUser = User::where('role_id', 3)->first();
        $studentUser->update(['must_change_credentials' => false]);
        $student = $studentUser->student;

        $selfieFile = UploadedFile::fake()->image('selfie.jpg', 640, 480);
        $photoFile = UploadedFile::fake()->image('surat_dokter.jpg', 800, 600);

        // Mulai: 9 Nov 2026, Sampai: 9 Nov 2026 (tanggal hari itu juga / 1 hari) -> HARUS DITERIMA
        $response = $this->actingAs($studentUser)->post('/siswa/izin', [
            'type' => 'sakit',
            'start_date' => '2026-11-09',
            'end_date' => '2026-11-09',
            'reason' => 'Sakit demam 1 hari',
            'selfie' => $selfieFile,
            'attachment' => $photoFile,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $leave = LeaveRequest::where('student_id', $student->id)->latest('id')->first();
        $this->assertNotNull($leave);
        $this->assertStringStartsWith('2026-11-09', (string) $leave->start_date);
        $this->assertStringStartsWith('2026-11-09', (string) $leave->end_date);
    }

    public function test_can_submit_leave_when_end_date_is_after_start_date(): void
    {
        Storage::fake('public');

        $studentUser = User::where('role_id', 3)->first();
        $studentUser->update(['must_change_credentials' => false]);
        $student = $studentUser->student;

        $selfieFile = UploadedFile::fake()->image('selfie.jpg', 640, 480);
        $photoFile = UploadedFile::fake()->image('surat_dokter.jpg', 800, 600);

        // Mulai: 9 Nov 2026, Sampai: 10 Nov 2026 (H+1) -> HARUS DITERIMA
        $response = $this->actingAs($studentUser)->post('/siswa/izin', [
            'type' => 'sakit',
            'start_date' => '2026-11-09',
            'end_date' => '2026-11-10',
            'reason' => 'Sakit berobat ke dokter',
            'selfie' => $selfieFile,
            'attachment' => $photoFile,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $leave = LeaveRequest::where('student_id', $student->id)->latest('id')->first();

        $this->assertNotNull($leave);
        $this->assertStringStartsWith('2026-11-09', (string) $leave->start_date);
        $this->assertStringStartsWith('2026-11-10', (string) $leave->end_date);
    }
}
