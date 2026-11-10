<?php

namespace App\Console\Commands;

use App\Services\MasterDataService;
use Illuminate\Console\Command;

class SyncMasterDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-master-data 
                            {--compile : Kompilasi ulang file mentah di tests/ ke master data .md dan .json terlebih dahulu}
                            {--only=all : Sinkronisasi hanya entitas tertentu (all, kelas, guru, siswa, ortu, jadwal)}
                            {--fresh : Bersihkan data lama pada jadwal sebelum sinkronisasi}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi Master Data Sekolah (Siswa & Rombel, Tenaga Guru, Orang Tua, dan Jadwal KBM) ke Database melalui Pipeline 2-Tahap';

    /**
     * Execute the console command.
     */
    public function handle(MasterDataService $service): int
    {
        $this->info('=== SINKRONISASI MASTER DATA SEKOLAH (TWO-STAGE PIPELINE) ===');

        $shouldCompile = $this->option('compile');
        $only = strtolower($this->option('only') ?? 'all');
        $isFresh = (bool) $this->option('fresh');

        // Tahap 1: Kompilasi jika diminta atau file master belum ada
        if ($shouldCompile || ! file_exists(database_path('data/master_siswa.json'))) {
            $this->info("\n[TAHAP 1] Mengompilasi dan Mengurasi Berkas Sumber Mentah ke Master Data (.md / .json)...");

            $absenFile = base_path('tests/Absen 2026-2027.xlsx');
            if (file_exists($absenFile) && in_array($only, ['all', 'kelas', 'siswa', 'ortu'], true)) {
                $this->line(" - Mengekstrak Siswa & Kelas dari: {$absenFile}");
                $resSiswa = $service->compileSiswaDanKelas($absenFile);
                $this->info("   ✔ Berhasil: {$resSiswa['classes']} Kelas, {$resSiswa['students']} Siswa, {$resSiswa['parents']} Akun Orang Tua.");
            }

            $pegawaiFile = base_path('tests/Data Pegawai 2026 Per Juni 2026 FIX.xlsx');
            if (file_exists($pegawaiFile) && in_array($only, ['all', 'guru'], true)) {
                $this->line(" - Mengekstrak Tenaga Guru dari: {$pegawaiFile}");
                $resGuru = $service->compileGuru($pegawaiFile);
                $this->info("   ✔ Berhasil: {$resGuru['teachers']} Tenaga Guru resmi (guru.01 - guru.91).");
            }

            $pdf1 = base_path('tests/JADWAL_KELAS_PUT_1_REV_SEPT.pdf');
            $pdf2 = base_path('tests/JADWAL KELAS PUT 2 SEM GANJIL 26_27 REVISI.pdf');
            $pdfRuang1 = base_path('tests/JADWAL_RUANG_PUT 1_REVISI_SEPT.pdf');
            $pdfRuang2 = base_path('tests/JADWAL RUANGAN PUT 2 GANJIL 26_27 OK.pdf');
            if ((file_exists($pdf1) || file_exists($pdf2)) && in_array($only, ['all', 'jadwal'], true)) {
                $this->line(' - Mengekstrak dan Menormalisasi Jadwal Sekolah PDF (Kelas & Ruangan Putaran 1 & 2)...');
                $resJadwal = $service->compileJadwal($pdf1, $pdf2, $pdfRuang1, $pdfRuang2);
                $this->info("   ✔ Berhasil: {$resJadwal['putaran_1']} sesi Putaran 1, {$resJadwal['putaran_2']} sesi Putaran 2 (Total: {$resJadwal['total']} sesi).");
            }
        }

        // Tahap 2: Sinkronisasi ke Database
        $this->info("\n[TAHAP 2] Menyinkronkan Master Data Terkurasi ke Database...");
        $results = $service->syncToDatabase([
            'only' => $only,
            'fresh' => $isFresh,
        ]);

        foreach ($results as $entity => $msg) {
            $this->info(' ✔ ['.strtoupper($entity)."] {$msg}");
        }

        $this->newLine();
        $this->info('=== SINKRONISASI MASTER DATA SELESAI DENGAN SUKSES ===');

        return Command::SUCCESS;
    }
}
