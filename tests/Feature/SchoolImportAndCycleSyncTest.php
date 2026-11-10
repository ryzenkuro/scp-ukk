<?php

namespace Tests\Feature;

use App\Models\SchoolClass;
use App\Models\SystemConfiguration;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SchoolImportAndCycleSyncTest extends TestCase
{
    use RefreshDatabase;

    private User $waka;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->waka = User::where('role_id', 1)->first();
        $this->waka->update(['must_change_credentials' => false]);
    }

    public function test_can_switch_learning_cycle(): void
    {
        $response = $this->actingAs($this->waka)->post('/waka/jam-pelajaran/switch-cycle', [
            'cycle' => 2,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals(2, SystemConfiguration::current()->active_cycle);

        $response1 = $this->actingAs($this->waka)->post('/waka/jam-pelajaran/switch-cycle', [
            'cycle' => 1,
        ]);

        $response1->assertRedirect();
        $this->assertEquals(1, SystemConfiguration::current()->active_cycle);
    }

    public function test_can_reset_and_sync_time_slots_from_standard_schedule(): void
    {
        // 1. Reset all time slots
        $resReset = $this->actingAs($this->waka)->delete('/waka/jam-pelajaran/reset-semua');
        $resReset->assertRedirect();
        $this->assertEquals(0, TimeSlot::count());

        // 2. Sync from schedule
        $resSync = $this->actingAs($this->waka)->post('/waka/jam-pelajaran/sync-jadwal');
        $resSync->assertRedirect();
        $resSync->assertSessionHas('success');

        // Harus ada 12 sesi jam KBM standar
        $this->assertEquals(12, TimeSlot::count());
        $this->assertEquals(10, TimeSlot::where('is_break', false)->count());
        $this->assertEquals(2, TimeSlot::where('is_break', true)->count());

        $firstSlot = TimeSlot::orderBy('order')->first();
        $this->assertEquals('Jam Ke-1', $firstSlot->name);
        $this->assertEquals('07:15:00', $firstSlot->start_time);
    }

    public function test_official_student_absen_excel_can_be_imported(): void
    {
        $filePath = base_path('tests/Absen 2026-2027.xlsx');
        if (! file_exists($filePath)) {
            $this->markTestSkipped('Berkas tests/Absen 2026-2027.xlsx tidak ditemukan.');
        }

        $uploaded = new UploadedFile($filePath, 'Absen 2026-2027.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->actingAs($this->waka)->post('/waka/siswa/import', [
            'file' => $uploaded,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertGreaterThan(1000, DB::table('siswas')->count());
        $this->assertGreaterThanOrEqual(48, SchoolClass::count());
    }

    public function test_official_pegawai_guru_excel_can_be_imported(): void
    {
        $filePath = base_path('tests/Data Pegawai 2026 Per Juni 2026 FIX.xlsx');
        if (! file_exists($filePath)) {
            $this->markTestSkipped('Berkas tests/Data Pegawai 2026 Per Juni 2026 FIX.xlsx tidak ditemukan.');
        }

        $uploaded = new UploadedFile($filePath, 'Data Pegawai 2026 Per Juni 2026 FIX.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->actingAs($this->waka)->post('/waka/guru/import', [
            'file' => $uploaded,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertGreaterThanOrEqual(90, User::whereIn('role_id', [2, 5])->count());
    }

    public function test_official_pdf_schedules_import_with_cycle_isolation(): void
    {
        $pdf1 = base_path('tests/JADWAL_KELAS_PUT_1_REV_SEPT.pdf');
        $pdf2 = base_path('tests/JADWAL KELAS PUT 2 SEM GANJIL 26_27 REVISI.pdf');

        if (! file_exists($pdf1) || ! file_exists($pdf2)) {
            $this->markTestSkipped('Berkas PDF jadwal tidak ditemukan.');
        }

        SchoolClass::firstOrCreate(['name' => 'X AKL 1']);

        // Impor Putaran 1
        $uploaded1 = new UploadedFile($pdf1, 'JADWAL_KELAS_PUT_1_REV_SEPT.pdf', 'application/pdf', null, true);
        $res1 = $this->actingAs($this->waka)->post('/waka/jadwal/import', [
            'file' => $uploaded1,
            'cycle_number' => '1',
            'replace_existing' => false,
        ]);
        $res1->assertRedirect();
        $countPut1 = DB::table('schedules')->where('cycle_number', 1)->count();
        $this->assertGreaterThan(0, $countPut1);

        // Impor Putaran 2
        $uploaded2 = new UploadedFile($pdf2, 'JADWAL KELAS PUT 2 SEM GANJIL 26_27 REVISI.pdf', 'application/pdf', null, true);
        $res2 = $this->actingAs($this->waka)->post('/waka/jadwal/import', [
            'file' => $uploaded2,
            'cycle_number' => '2',
            'replace_existing' => false,
        ]);
        $res2->assertRedirect();

        $countPut2 = DB::table('schedules')->where('cycle_number', 2)->count();
        $this->assertGreaterThan(0, $countPut2);

        // Pastikan Putaran 1 tetap utuh dan tidak terhapus
        $countPut1After = DB::table('schedules')->where('cycle_number', 1)->count();
        $this->assertEquals($countPut1, $countPut1After);
    }

    public function test_can_multi_upload_4_files_simultaneously_and_merge_rooms(): void
    {
        $pdf1 = base_path('tests/JADWAL_KELAS_PUT_1_REV_SEPT.pdf');
        $pdf2 = base_path('tests/JADWAL KELAS PUT 2 SEM GANJIL 26_27 REVISI.pdf');
        $pdfRuang1 = base_path('tests/JADWAL_RUANG_PUT 1_REVISI_SEPT.pdf');
        $pdfRuang2 = base_path('tests/JADWAL RUANGAN PUT 2 GANJIL 26_27 OK.pdf');

        if (! file_exists($pdf1) || ! file_exists($pdf2) || ! file_exists($pdfRuang1) || ! file_exists($pdfRuang2)) {
            $this->markTestSkipped('Sebagian berkas PDF 4-file jadwal tidak ditemukan.');
        }

        $files = [
            new UploadedFile($pdf1, 'JADWAL_KELAS_PUT_1_REV_SEPT.pdf', 'application/pdf', null, true),
            new UploadedFile($pdf2, 'JADWAL KELAS PUT 2 SEM GANJIL 26_27 REVISI.pdf', 'application/pdf', null, true),
            new UploadedFile($pdfRuang1, 'JADWAL_RUANG_PUT 1_REVISI_SEPT.pdf', 'application/pdf', null, true),
            new UploadedFile($pdfRuang2, 'JADWAL RUANGAN PUT 2 GANJIL 26_27 OK.pdf', 'application/pdf', null, true),
        ];

        $response = $this->actingAs($this->waka)->post('/waka/jadwal/import', [
            'files' => $files,
            'cycle_number' => 'auto',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $totalSchedules = DB::table('schedules')->count();
        $this->assertGreaterThan(1000, $totalSchedules);

        $withRooms = DB::table('schedules')->whereNotNull('room_name')->count();
        $this->assertGreaterThan(1000, $withRooms);

        // Verifikasi bahwa ruangan spesifik seperti OR1 atau LAB ada dan terisi
        $orSchedule = DB::table('schedules')
            ->where('cycle_number', 1)
            ->where('room_name', 'OR1')
            ->first();
        $this->assertNotNull($orSchedule);
    }
}
