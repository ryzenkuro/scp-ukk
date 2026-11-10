<?php

namespace Tests\Feature;

use App\Models\LeaveRequest;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase6PortalAndReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_siswa_can_view_dashboard_with_personal_stats(): void
    {
        $siswaUser = User::where('username', '202611001')->first();
        $siswaUser->update(['must_change_credentials' => false]);

        $response = $this->actingAs($siswaUser)->get('/siswa/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Siswa/Dashboard')
            ->has('student')
            ->has('todaySchedules')
            ->has('recentLeaves')
            ->has('stats')
        );
    }

    public function test_siswa_can_view_class_schedule(): void
    {
        $siswaUser = User::where('username', '202611001')->first();
        $siswaUser->update(['must_change_credentials' => false]);

        $response = $this->actingAs($siswaUser)->get('/siswa/jadwal');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Siswa/Jadwal')
            ->has('student')
            ->has('schedules')
        );
    }

    public function test_siswa_can_submit_leave_request(): void
    {
        Storage::fake('public');
        $siswaUser = User::where('username', '202611001')->first();
        $siswaUser->update(['must_change_credentials' => false]);

        $selfie = UploadedFile::fake()->image('selfie.jpg', 640, 480);
        $attachment = UploadedFile::fake()->create('surat_dokter.pdf', 100, 'application/pdf');

        $response = $this->actingAs($siswaUser)->post('/siswa/izin', [
            'type' => 'sakit',
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-12',
            'reason' => 'Sakit demam dan beristirahat sesuai anjuran dokter',
            'selfie' => $selfie,
            'attachment' => $attachment,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('leave_requests', [
            'student_id' => $siswaUser->student_id,
            'type' => 'sakit',
            'reason' => 'Sakit demam dan beristirahat sesuai anjuran dokter',
            'status' => 'pending',
        ]);
    }

    public function test_waka_can_view_laporan_presensi(): void
    {
        $waka = User::where('role_id', 1)->first();

        $response = $this->actingAs($waka)->get('/waka/laporan/presensi?kelas=XII RPL 2');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Waka/Laporan/Presensi')
            ->has('kelasList')
            ->has('rekapData')
            ->has('summary')
            ->has('config')
        );
    }

    public function test_waka_can_view_and_verify_leave_request(): void
    {
        $waka = User::where('role_id', 1)->first();
        $student = Siswa::first();

        // 1. Siswa mengajukan izin
        $leave = LeaveRequest::create([
            'student_id' => $student->id,
            'type' => 'izin',
            'start_date' => '2026-11-15',
            'end_date' => '2026-11-16',
            'reason' => 'Mengikuti kegiatan keluarga di luar kota',
            'status' => 'pending',
        ]);

        // 2. Waka membuka laporan izin
        $viewResponse = $this->actingAs($waka)->get('/waka/laporan/izin');
        $viewResponse->assertStatus(200);
        $viewResponse->assertInertia(fn ($page) => $page
            ->component('Waka/Laporan/Izin')
            ->has('leaves')
        );

        // 3. Waka menyetujui izin
        $verifyResponse = $this->actingAs($waka)->post("/waka/laporan/izin/{$leave->id}/verify", [
            'status' => 'approved',
        ]);

        $verifyResponse->assertRedirect();
        $verifyResponse->assertSessionHas('success');

        $leave->refresh();
        $this->assertEquals('approved', $leave->status);
        $this->assertEquals($waka->id, $leave->verified_by);
        $this->assertNotNull($leave->verified_at);
    }
}
