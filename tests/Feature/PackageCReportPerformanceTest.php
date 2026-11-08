<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageCReportPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_waka_can_view_guru_performance_audit(): void
    {
        $waka = User::where('role_id', 1)->first();
        $waka->update(['must_change_credentials' => false]);

        $response = $this->actingAs($waka)->get('/waka/laporan/guru');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Waka/Laporan/Guru')
            ->has('rekapGuru')
            ->has('summary')
            ->has('period')
        );
    }

    public function test_waka_can_view_guru_detail_journals(): void
    {
        $waka = User::where('role_id', 1)->first();
        $waka->update(['must_change_credentials' => false]);

        $guru = User::where('role_id', 2)->first();

        $response = $this->actingAs($waka)->get("/waka/laporan/guru/{$guru->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Waka/Laporan/GuruDetail')
            ->has('guru')
            ->has('journals')
            ->has('schedules')
        );
    }

    public function test_waka_guru_and_siswa_can_export_pdf_schedules(): void
    {
        $waka = User::where('role_id', 1)->first();
        $waka->update(['must_change_credentials' => false]);

        $guru = User::where('role_id', 2)->first();
        $guru->update(['must_change_credentials' => false]);

        $siswa = User::where('role_id', 3)->first();
        $siswa->update(['must_change_credentials' => false]);

        // Waka schedule export
        $resWaka = $this->actingAs($waka)->get('/waka/jadwal/export-pdf');
        $resWaka->assertStatus(200);
        $resWaka->assertSee('SMK NEGERI 1 BANTUL');
        $resWaka->assertSee('JADWAL PELAJARAN SEKOLAH');

        // Guru schedule export
        $resGuru = $this->actingAs($guru)->get('/guru/jadwal/export-pdf');
        $resGuru->assertStatus(200);
        $resGuru->assertSee('SMK NEGERI 1 BANTUL');
        $resGuru->assertSee('JADWAL MENGAJAR GURU');

        // Siswa schedule export
        $resSiswa = $this->actingAs($siswa)->get('/siswa/jadwal/export-pdf');
        $resSiswa->assertStatus(200);
        $resSiswa->assertSee('SMK NEGERI 1 BANTUL');
        $resSiswa->assertSee('JADWAL BELAJAR SISWA');
    }

    public function test_non_waka_cannot_access_guru_performance_audit(): void
    {
        $guru = User::where('role_id', 2)->first();
        $guru->update(['must_change_credentials' => false]);

        $siswa = User::where('role_id', 3)->first();
        $siswa->update(['must_change_credentials' => false]);

        $res1 = $this->actingAs($guru)->get('/waka/laporan/guru');
        $res1->assertStatus(403);

        $res2 = $this->actingAs($siswa)->get('/waka/laporan/guru');
        $res2->assertStatus(403);
    }
}
