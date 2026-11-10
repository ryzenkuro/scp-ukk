<?php

namespace App\Services;

use App\Models\HomeroomAssignment;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Siswa;
use App\Models\SystemConfiguration;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\IOFactory;

class MasterDataService
{
    protected string $dataDir;

    public function __construct()
    {
        $this->dataDir = database_path('data');
        if (! is_dir($this->dataDir)) {
            @mkdir($this->dataDir, 0755, true);
        }
    }

    /**
     * Parse generic Markdown table text into array of associative rows
     */
    public function parseMarkdownTable(string $markdown): array
    {
        $lines = explode("\n", $markdown);
        $headers = [];
        $rows = [];
        $headerFound = false;

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || ! str_starts_with($trimmed, '|')) {
                continue;
            }

            // Parse columns
            $cols = array_map('trim', explode('|', trim($trimmed, '|')));

            // Skip separator line e.g. |:---|:---|
            if (collect($cols)->every(fn ($c) => preg_match('/^:?-+:?$/', $c))) {
                $headerFound = true;

                continue;
            }

            if (! $headerFound && empty($headers)) {
                $headers = array_map(function ($h) {
                    $norm = mb_strtolower(trim(preg_replace('/\s+/', ' ', $h)));

                    return $norm;
                }, $cols);

                continue;
            }

            if ($headerFound && ! empty($headers)) {
                $row = [];
                foreach ($headers as $idx => $header) {
                    $row[$header] = $cols[$idx] ?? '';
                }
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * Kompilasi Master Siswa, Kelas & Orang Tua dari file Absen Excel
     */
    public function compileSiswaDanKelas(string $excelPath): array
    {
        if (! file_exists($excelPath)) {
            throw new \RuntimeException("File Absen tidak ditemukan: {$excelPath}");
        }

        $spreadsheet = IOFactory::load($excelPath);

        $classes = [];
        $students = [];
        $parents = [];

        $determineJurusan = function (string $className): string {
            $upper = strtoupper($className);
            if (str_contains($upper, 'RPL')) {
                return 'RPL';
            }
            if (str_contains($upper, 'TKJ')) {
                return 'TKJ';
            }
            if (str_contains($upper, 'DKV')) {
                return 'DKV';
            }
            if (str_contains($upper, 'AKL') || str_contains($upper, 'AK')) {
                return 'AKL';
            }
            if (str_contains($upper, 'LPS')) {
                return 'LPS';
            }
            if (str_contains($upper, 'MP')) {
                return 'MP';
            }
            if (str_contains($upper, 'BR')) {
                return 'BR';
            }
            if (str_contains($upper, 'BD')) {
                return 'BD';
            }

            return 'RPL';
        };

        $determineGrade = function (string $className): string {
            $upper = strtoupper($className);
            if (str_starts_with($upper, 'XII')) {
                return 'XII';
            }
            if (str_starts_with($upper, 'XI')) {
                return 'XI';
            }

            return 'X';
        };

        $determineAngkatan = function (string $grade): int {
            if ($grade === 'XII') {
                return 2024;
            }
            if ($grade === 'XI') {
                return 2025;
            }

            return 2026;
        };

        foreach (['X', 'XI', 'XII'] as $sheetName) {
            $sheet = $spreadsheet->getSheetByName($sheetName);
            if (! $sheet) {
                continue;
            }

            $rows = $sheet->toArray(null, true, true, false);
            $currentClass = null;

            foreach ($rows as $row) {
                $rowText = implode(' ', array_filter(array_map('trim', array_map('strval', $row))));

                if (preg_match('/KELAS\s*:\s*([A-Za-z0-9\s\-]+)/i', $rowText, $m)) {
                    $rawClass = trim($m[1]);
                    $rawClass = preg_replace('/\bTAHUN\b.*$/i', '', $rawClass);
                    $rawClass = trim($rawClass);
                    if ($rawClass !== '') {
                        $currentClass = $rawClass;
                        $cGrade = $determineGrade($currentClass);
                        $cJurusan = $determineJurusan($currentClass);
                        $classes[$currentClass] = [
                            'name' => $currentClass,
                            'grade' => $cGrade,
                            'jurusan' => $cJurusan,
                            'angkatan' => $determineAngkatan($cGrade),
                            'total_siswa' => 0,
                        ];

                        continue;
                    }
                }

                if (! $currentClass) {
                    continue;
                }

                $num = isset($row[0]) ? trim((string) $row[0]) : '';
                $nis = isset($row[1]) ? trim((string) $row[1]) : '';
                $name = isset($row[2]) ? trim((string) $row[2]) : '';
                $genderCol = isset($row[3]) ? strtoupper(trim((string) $row[3])) : '';

                if (is_numeric($num) && (int) $num >= 1 && (int) $num <= 60 && ! empty($name) && ! preg_match('/^(NAMA|NOMOR|L\/P|DAFTAR|TAHUN)/i', $name)) {
                    $cleanNis = preg_match('/\b(\d{4,12})\b/', $nis, $nm) ? $nm[1] : null;
                    $cleanName = trim($name, " \t\n\r\0\x0B*");
                    $gender = in_array($genderCol, ['L', 'P']) ? $genderCol : 'L';

                    $classes[$currentClass]['total_siswa']++;

                    $studentEntry = [
                        'nis' => $cleanNis,
                        'name' => $cleanName,
                        'kelas' => $currentClass,
                        'grade' => $classes[$currentClass]['grade'],
                        'jurusan' => $classes[$currentClass]['jurusan'],
                        'angkatan' => $classes[$currentClass]['angkatan'],
                        'gender' => $gender,
                        'alamat' => 'Bantul',
                        'username' => $cleanNis,
                        'default_password' => 'smkn1bantul2026',
                        'must_change_credentials' => true,
                    ];
                    $students[] = $studentEntry;

                    if ($cleanNis) {
                        $parents[] = [
                            'student_nis' => $cleanNis,
                            'student_name' => $cleanName,
                            'kelas' => $currentClass,
                            'username' => 'ortu_'.$cleanNis,
                            'fullname' => 'Orang Tua '.$cleanName,
                            'default_password' => 'smkn1bantul2026',
                            'must_change_credentials' => true,
                        ];
                    }
                }
            }
        }

        // Simpan JSON
        file_put_contents($this->dataDir.'/master_kelas.json', json_encode(array_values($classes), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        file_put_contents($this->dataDir.'/master_siswa.json', json_encode($students, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        file_put_contents($this->dataDir.'/master_orang_tua.json', json_encode($parents, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // Generate Markdown Rombel Kelas
        $kelasMd = "# Master Data Rombel Kelas SMK N 1 Bantul\n\n";
        $kelasMd .= 'Total Rombel: '.count($classes)." Kelas | Tahun Ajaran 2026/2027\n\n";
        $kelasMd .= "| No | Tingkat | Rombel Kelas | Jurusan | Angkatan | Total Siswa |\n";
        $kelasMd .= "|:---:|:---:|:---|:---:|:---:|:---:|\n";
        $kIdx = 1;
        foreach ($classes as $c) {
            $kelasMd .= "| {$kIdx} | {$c['grade']} | {$c['name']} | {$c['jurusan']} | {$c['angkatan']} | {$c['total_siswa']} |\n";
            $kIdx++;
        }
        file_put_contents($this->dataDir.'/master_kelas.md', $kelasMd);

        // Generate Markdown Siswa (dikelompokkan per kelas)
        $siswaMd = "# Master Data Siswa SMK N 1 Bantul (Resmi)\n\n";
        $siswaMd .= 'Total Siswa: '.count($students)." Siswa | 48 Rombel Kelas | Tahun Ajaran 2026/2027\n\n";
        $siswaMd .= "Kata sandi bawaan seluruh akun siswa: `smkn1bantul2026` (Wajib aktivasi mandiri saat login perdana).\n\n";

        $studentsByClass = [];
        foreach ($students as $s) {
            $studentsByClass[$s['kelas']][] = $s;
        }

        foreach ($studentsByClass as $cName => $cStudents) {
            $siswaMd .= "## Kelas {$cName} (".count($cStudents)." Siswa)\n\n";
            $siswaMd .= "| No | NIS | Username | Nama Lengkap Siswa | L/P | Jurusan | Angkatan | Status Akun |\n";
            $siswaMd .= "|:---:|:---:|:---|:---|:---:|:---:|:---:|:---:|\n";
            foreach ($cStudents as $sIdx => $st) {
                $uNo = $sIdx + 1;
                $siswaMd .= "| {$uNo} | {$st['nis']} | `{$st['username']}` | {$st['name']} | {$st['gender']} | {$st['jurusan']} | {$st['angkatan']} | Perlu Aktivasi |\n";
            }
            $siswaMd .= "\n";
        }
        file_put_contents($this->dataDir.'/master_siswa.md', $siswaMd);

        // Generate Markdown Orang Tua
        $ortuMd = "# Master Data Orang Tua Siswa SMK N 1 Bantul\n\n";
        $ortuMd .= 'Total Akun: '.count($parents)." Akun Terhubung (1:1 Siswa)\n\n";
        $ortuMd .= "Kata sandi bawaan seluruh akun orang tua: `smkn1bantul2026` (Wajib aktivasi mandiri saat login perdana).\n\n";
        $ortuMd .= "| No | Username Ortu | Nama Akun | Siswa Terhubung | NIS | Kelas | Status Akun |\n";
        $ortuMd .= "|:---:|:---|:---|:---|:---:|:---:|:---:|\n";
        foreach ($parents as $pIdx => $p) {
            $pNo = $pIdx + 1;
            $ortuMd .= "| {$pNo} | `{$p['username']}` | {$p['fullname']} | {$p['student_name']} | {$p['student_nis']} | {$p['kelas']} | Perlu Aktivasi |\n";
        }
        file_put_contents($this->dataDir.'/master_orang_tua.md', $ortuMd);

        return [
            'classes' => count($classes),
            'students' => count($students),
            'parents' => count($parents),
        ];
    }

    /**
     * Kompilasi Master Tenaga Guru dari Excel resmi
     */
    public function compileGuru(string $excelPath): array
    {
        if (! file_exists($excelPath)) {
            throw new \RuntimeException("File Guru tidak ditemukan: {$excelPath}");
        }

        $spreadsheet = IOFactory::load($excelPath);
        $sheet = $spreadsheet->getSheetByName('Guru') ?? $spreadsheet->getActiveSheet();
        $sheetRows = $sheet->toArray(null, true, true, false);

        $teachers = [];
        $headerRow = -1;

        foreach ($sheetRows as $rIdx => $row) {
            $rowStr = mb_strtolower(implode(' ', array_filter(array_map('strval', $row))));
            if (str_contains($rowStr, 'nama') && (str_contains($rowStr, 'nip') || str_contains($rowStr, 'kode') || str_contains($rowStr, 'guru'))) {
                $headerRow = $rIdx;
                break;
            }
        }

        $colMap = [];
        if ($headerRow >= 0) {
            foreach ($sheetRows[$headerRow] as $cIdx => $val) {
                $v = mb_strtolower(trim((string) $val));
                if (in_array($v, ['no', 'nomor', 'no.'])) {
                    $colMap['no'] = $cIdx;
                } elseif (str_contains($v, 'nip') || str_contains($v, 'nik')) {
                    $colMap['nip'] = $cIdx;
                } elseif (str_contains($v, 'nama')) {
                    $colMap['nama'] = $cIdx;
                } elseif (str_contains($v, 'kelamin') || $v === 'jk') {
                    $colMap['jk'] = $cIdx;
                } elseif (str_contains($v, 'golongan') && ! str_contains($v, 'tmt')) {
                    $colMap['gol'] = $cIdx;
                } elseif (str_contains($v, 'jabatan')) {
                    $colMap['jabatan'] = $cIdx;
                } elseif (str_contains($v, 'mapel') || str_contains($v, 'mata pelajaran')) {
                    $colMap['mapel'] = $cIdx;
                } elseif (str_contains($v, 'fet') || str_contains($v, 'kode')) {
                    $colMap['fet'] = $cIdx;
                }
            }
        }

        $colNo = $colMap['no'] ?? 0;
        $colNip = $colMap['nip'] ?? 1;
        $colNama = $colMap['nama'] ?? 2;
        $colJk = $colMap['jk'] ?? 3;
        $colGol = $colMap['gol'] ?? 4;
        $colJabatan = $colMap['jabatan'] ?? 6;
        $colMapel = $colMap['mapel'] ?? 8;

        $dataRows = $headerRow >= 0 ? array_slice($sheetRows, $headerRow + 1) : $sheetRows;

        $tIdx = 1;
        foreach ($dataRows as $row) {
            $rawNo = trim((string) ($row[$colNo] ?? ''));
            $rawNip = trim((string) ($row[$colNip] ?? ''));
            $rawName = trim((string) ($row[$colNama] ?? ''));
            $rawJk = trim((string) ($row[$colJk] ?? ''));
            $rawGol = trim((string) ($row[$colGol] ?? ''));
            $rawJabatan = trim((string) ($row[$colJabatan] ?? ''));
            $rawMapel = trim((string) ($row[$colMapel] ?? ''));

            if ($rawName === '' || ! is_numeric($rawNo) || in_array(mb_strtolower($rawName), ['nama', 'nama guru', 'nama lengkap'], true)) {
                continue;
            }

            $code = (int) $rawNo;
            $username = 'guru.'.str_pad($code, 2, '0', STR_PAD_LEFT);
            $cleanNip = preg_replace('/\D/', '', $rawNip);
            if (strlen($cleanNip) < 8) {
                $cleanNip = null;
            }

            $teachers[] = [
                'kode' => $code,
                'username' => $username,
                'name' => $rawName,
                'nip' => $cleanNip,
                'jk' => (stripos($rawJk, 'perempuan') !== false || strtoupper($rawJk) === 'P') ? 'P' : 'L',
                'gol' => $rawGol,
                'jabatan' => $rawJabatan,
                'mapel' => $rawMapel,
                'default_password' => 'smkn1bantul2026',
                'must_change_credentials' => true,
            ];
            $tIdx++;
        }

        // Simpan JSON
        file_put_contents($this->dataDir.'/master_guru.json', json_encode($teachers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // Simpan Markdown
        $guruMd = "# Master Data Tenaga Pendidik (Guru) Resmi SMK N 1 Bantul\n\n";
        $guruMd .= "Dokumen ini merupakan sumber data resmi (single source of truth) untuk 91 Tenaga Pendidik SMK N 1 Bantul tahun ajaran 2026/2027 yang telah dipilah, dibersihkan dari duplikasi, dan distandarkan format akunnya.\n\n";
        $guruMd .= "## Aturan Kredensial Bawaan & Aktivasi\n";
        $guruMd .= "- **Format Username Bawaan**: `guru.<kode>` (contoh: `guru.01`, `guru.02`, `guru.57`, `guru.89`)\n";
        $guruMd .= "- **Alternatif Login**: Menggunakan NIP (18 digit) atau Username\n";
        $guruMd .= "- **Kata Sandi Bawaan**: `smkn1bantul2026`\n";
        $guruMd .= "- **Status Akun Awal**: `Perlu Aktivasi` (`must_change_credentials = true`)\n";
        $guruMd .= "- **Alur Aktivasi**: Saat pertama kali masuk, guru wajib menentukan username dan kata sandi baru pilihan sendiri sebelum dapat mengakses portal KBM.\n\n";
        $guruMd .= "## Daftar 91 Guru Resmi\n\n";
        $guruMd .= "| Kode FET | Username Default | NIP | Nama Lengkap & Gelar | L/P | Golongan | Jabatan | Mata Pelajaran |\n";
        $guruMd .= "| :---: | :---: | :---: | :--- | :---: | :---: | :--- | :--- |\n";
        foreach ($teachers as $t) {
            $nipStr = $t['nip'] ?? '-';
            $guruMd .= "| {$t['kode']} | `{$t['username']}` | {$nipStr} | {$t['name']} | {$t['jk']} | {$t['gol']} | {$t['jabatan']} | {$t['mapel']} |\n";
        }
        file_put_contents($this->dataDir.'/master_guru.md', $guruMd);

        return ['teachers' => count($teachers)];
    }

    /**
     * Kompilasi Master Jadwal Putaran 1 & Putaran 2 (Termasuk Berkas Jadwal Ruangan)
     */
    public function compileJadwal(
        ?string $pdfPath1 = null,
        ?string $pdfPath2 = null,
        ?string $pdfRuangPath1 = null,
        ?string $pdfRuangPath2 = null
    ): array {
        $parserService = new SchedulePdfParserService;

        $pdfPath1 = $pdfPath1 ?? base_path('tests/JADWAL_KELAS_PUT_1_REV_SEPT.pdf');
        $pdfPath2 = $pdfPath2 ?? base_path('tests/JADWAL KELAS PUT 2 SEM GANJIL 26_27 REVISI.pdf');
        $pdfRuangPath1 = $pdfRuangPath1 ?? base_path('tests/JADWAL_RUANG_PUT 1_REVISI_SEPT.pdf');
        $pdfRuangPath2 = $pdfRuangPath2 ?? base_path('tests/JADWAL RUANGAN PUT 2 GANJIL 26_27 OK.pdf');

        // 1. Ekstrak Jadwal Kelas (ruangan null)
        $schedules1 = file_exists($pdfPath1) ? $parserService->parse($pdfPath1, 1) : [];
        $schedules2 = file_exists($pdfPath2) ? $parserService->parse($pdfPath2, 2) : [];

        // 2. Ekstrak & Gabungkan Ruangan jika berkas tersedia
        if (file_exists($pdfRuangPath1)) {
            $roomMap1 = $parserService->parseRoomPdf($pdfRuangPath1, 1);
            $parserService->mergeSchedulesWithRooms($schedules1, $roomMap1);
        }

        if (file_exists($pdfRuangPath2)) {
            $roomMap2 = $parserService->parseRoomPdf($pdfRuangPath2, 2);
            $parserService->mergeSchedulesWithRooms($schedules2, $roomMap2);
        }

        // Normalisasi nama kelas menggunakan SchoolClass resmi
        $classMap = $this->buildSchoolClassMap();
        foreach ($schedules1 as &$s1) {
            $c = $this->resolveSchoolClass($s1['class_name'], $classMap);
            if ($c) {
                $s1['class_name'] = $c->name;
                $s1['class_id'] = $c->id;
            }
        }
        unset($s1);

        foreach ($schedules2 as &$s2) {
            $c = $this->resolveSchoolClass($s2['class_name'], $classMap);
            if ($c) {
                $s2['class_name'] = $c->name;
                $s2['class_id'] = $c->id;
            }
        }
        unset($s2);

        // Generate Markdown Putaran 1
        $md1 = "# Master Jadwal Pembelajaran Sekolah - Putaran 1\n\n";
        $md1 .= 'Total Sesi KBM: '.count($schedules1)." Sesi | Semester Ganjil 2026/2027\n\n";
        $md1 .= "| No | Hari | Rombel Kelas | Jam Pelajaran | Mata Pelajaran | Guru Pengampu | Ruang |\n";
        $md1 .= "|:---:|:---:|:---|:---:|:---|:---|:---:|\n";
        foreach ($schedules1 as $idx => $s) {
            $no = $idx + 1;
            $jam = substr($s['start_time'], 0, 5).' - '.substr($s['end_time'], 0, 5);
            $rName = $s['room_name'] ?? 'Ruang Kelas';
            $md1 .= "| {$no} | {$s['day_of_week']} | {$s['class_name']} | {$jam} | {$s['subject']} | {$s['teacher_name']} | {$rName} |\n";
        }
        file_put_contents($this->dataDir.'/master_jadwal_putaran_1.md', $md1);

        // Generate Markdown Putaran 2
        $md2 = "# Master Jadwal Pembelajaran Sekolah - Putaran 2\n\n";
        $md2 .= 'Total Sesi KBM: '.count($schedules2)." Sesi | Semester Ganjil 2026/2027\n\n";
        $md2 .= "| No | Hari | Rombel Kelas | Jam Pelajaran | Mata Pelajaran | Guru Pengampu | Ruang |\n";
        $md2 .= "|:---:|:---:|:---|:---:|:---|:---|:---:|\n";
        foreach ($schedules2 as $idx => $s) {
            $no = $idx + 1;
            $jam = substr($s['start_time'], 0, 5).' - '.substr($s['end_time'], 0, 5);
            $rName = $s['room_name'] ?? 'Ruang Kelas';
            $md2 .= "| {$no} | {$s['day_of_week']} | {$s['class_name']} | {$jam} | {$s['subject']} | {$s['teacher_name']} | {$rName} |\n";
        }
        file_put_contents($this->dataDir.'/master_jadwal_putaran_2.md', $md2);

        // Gabungan JSON
        $allSchedules = array_merge($schedules1, $schedules2);
        file_put_contents($this->dataDir.'/master_jadwal.json', json_encode($allSchedules, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return [
            'putaran_1' => count($schedules1),
            'putaran_2' => count($schedules2),
            'total' => count($allSchedules),
        ];
    }

    /**
     * Sinkronisasi Seluruh Master Data ke Database secara Idempoten dan Transaksional
     */
    public function syncToDatabase(array $options = []): array
    {
        $only = $options['only'] ?? 'all';
        $fresh = (bool) ($options['fresh'] ?? false);
        $output = [];

        $defaultPassword = Hash::make('smkn1bantul2026');

        // 1. Sinkronisasi Rombel Kelas
        if (in_array($only, ['all', 'kelas', 'siswa'], true)) {
            $kelasJson = $this->dataDir.'/master_kelas.json';
            if (file_exists($kelasJson)) {
                $classes = json_decode(file_get_contents($kelasJson), true);
                $existingClasses = SchoolClass::pluck('id', 'name')->toArray();
                $inserted = 0;
                $updated = 0;

                foreach ($classes as $c) {
                    if (isset($existingClasses[$c['name']])) {
                        SchoolClass::where('id', $existingClasses[$c['name']])->update([
                            'grade' => $c['grade'],
                            'jurusan' => $c['jurusan'],
                            'updated_at' => now(),
                        ]);
                        $updated++;
                    } else {
                        $newC = SchoolClass::create([
                            'name' => $c['name'],
                            'grade' => $c['grade'],
                            'jurusan' => $c['jurusan'],
                        ]);
                        $existingClasses[$c['name']] = $newC->id;
                        $inserted++;
                    }
                }
                $output['kelas'] = "{$inserted} kelas baru ditambahkan, {$updated} kelas diperbarui.";
            }
        }

        // 2. Sinkronisasi Tenaga Guru
        if (in_array($only, ['all', 'guru'], true)) {
            $guruJson = $this->dataDir.'/master_guru.json';
            if (file_exists($guruJson)) {
                $teachers = json_decode(file_get_contents($guruJson), true);
                $existingNips = User::whereNotNull('nip')->pluck('id', 'nip')->toArray();
                $existingUsers = User::pluck('id', 'username')->toArray();
                $allSchoolClasses = SchoolClass::pluck('id', 'name')->toArray();

                $syncedGuru = 0;
                DB::transaction(function () use ($teachers, &$existingNips, &$existingUsers, $defaultPassword, &$syncedGuru) {
                    foreach ($teachers as $t) {
                        $nip = $t['nip'];
                        $user = null;

                        if (! empty($nip) && isset($existingNips[$nip])) {
                            $user = User::find($existingNips[$nip]);
                        } elseif (isset($existingUsers[$t['username']])) {
                            $user = User::find($existingUsers[$t['username']]);
                        } else {
                            $user = User::whereIn('role_id', [1, 2, 5])
                                ->where('fullname', $t['name'])
                                ->first();
                        }

                        $roleId = 2; // Default Guru Pengampu
                        if ($user && $user->role_id === 1) {
                            $roleId = 1; // Tetap Waka jika sudah Waka
                        }

                        // Cegah tabrakan username unik jika akun guru lain sebelumnya memegang username ini
                        if ($user) {
                            User::where('username', $t['username'])
                                ->where('id', '!=', $user->id)
                                ->update(['username' => 'temp_guru_'.uniqid()]);

                            $user->update([
                                'fullname' => $t['name'],
                                'nip' => $nip ?? $user->nip,
                                'username' => $t['username'] ?? $user->username,
                                'role_id' => $roleId,
                                'must_change_credentials' => true,
                                'is_active' => true,
                            ]);
                        } else {
                            // Jika ada user lama dengan username ini, pakai user tersebut
                            $userWithUsername = User::where('username', $t['username'])->first();
                            if ($userWithUsername) {
                                $userWithUsername->update([
                                    'fullname' => $t['name'],
                                    'nip' => $nip,
                                    'role_id' => $roleId,
                                    'must_change_credentials' => true,
                                    'is_active' => true,
                                ]);
                                $user = $userWithUsername;
                            } else {
                                $user = User::create([
                                    'fullname' => $t['name'],
                                    'username' => $t['username'],
                                    'nip' => $nip,
                                    'email' => null,
                                    'phone' => null,
                                    'password' => $defaultPassword,
                                    'role_id' => $roleId,
                                    'must_change_credentials' => true,
                                    'is_active' => true,
                                ]);
                            }
                        }

                        if (! empty($nip)) {
                            $existingNips[$nip] = $user->id;
                        }
                        $existingUsers[$user->username] = $user->id;
                        $syncedGuru++;
                    }
                });
                $output['guru'] = "{$syncedGuru} tenaga guru berhasil disinkronisasi dengan status Perlu Aktivasi.";
            }
        }

        // 3. Sinkronisasi Data Siswa
        if (in_array($only, ['all', 'siswa'], true)) {
            $siswaJson = $this->dataDir.'/master_siswa.json';
            if (file_exists($siswaJson)) {
                $students = json_decode(file_get_contents($siswaJson), true);
                $existingSiswas = Siswa::pluck('id', 'nis')->toArray();
                $existingUsers = User::pluck('id', 'username')->toArray();

                $syncedSiswa = 0;
                DB::transaction(function () use ($students, &$existingSiswas, &$existingUsers, $defaultPassword, &$syncedSiswa) {
                    foreach ($students as $st) {
                        $nis = $st['nis'];
                        $siswaId = null;

                        if (! empty($nis) && isset($existingSiswas[$nis])) {
                            $siswaId = $existingSiswas[$nis];
                            Siswa::where('id', $siswaId)->update([
                                'name' => $st['name'],
                                'kelas' => $st['kelas'],
                                'jurusan' => $st['jurusan'],
                                'angkatan' => $st['angkatan'],
                                'gender' => $st['gender'],
                                'alamat' => $st['alamat'] ?? 'Bantul',
                                'status' => 'aktif',
                            ]);
                        } else {
                            $newS = Siswa::create([
                                'name' => $st['name'],
                                'nis' => $nis,
                                'kelas' => $st['kelas'],
                                'jurusan' => $st['jurusan'],
                                'angkatan' => $st['angkatan'],
                                'gender' => $st['gender'],
                                'alamat' => $st['alamat'] ?? 'Bantul',
                                'status' => 'aktif',
                            ]);
                            $siswaId = $newS->id;
                            if ($nis) {
                                $existingSiswas[$nis] = $siswaId;
                            }
                        }

                        // Akun User Siswa
                        if (! empty($nis)) {
                            if (isset($existingUsers[$nis])) {
                                User::where('id', $existingUsers[$nis])->update([
                                    'fullname' => $st['name'],
                                    'student_id' => $siswaId,
                                    'role_id' => 3,
                                    'must_change_credentials' => true,
                                    'is_active' => true,
                                ]);
                            } else {
                                $u = User::create([
                                    'username' => $nis,
                                    'role_id' => 3,
                                    'student_id' => $siswaId,
                                    'fullname' => $st['name'],
                                    'password' => $defaultPassword,
                                    'must_change_credentials' => true,
                                    'is_active' => true,
                                ]);
                                $existingUsers[$nis] = $u->id;
                            }
                        }
                        $syncedSiswa++;
                    }
                });
                $output['siswa'] = "{$syncedSiswa} siswa dan akun login berhasil disinkronisasi.";
            }
        }

        // 4. Sinkronisasi Data Orang Tua
        if (in_array($only, ['all', 'ortu'], true)) {
            $ortuJson = $this->dataDir.'/master_orang_tua.json';
            if (file_exists($ortuJson)) {
                $parents = json_decode(file_get_contents($ortuJson), true);
                $existingSiswas = Siswa::pluck('id', 'nis')->toArray();
                $existingUsers = User::pluck('id', 'username')->toArray();

                $syncedOrtu = 0;
                DB::transaction(function () use ($parents, $existingSiswas, &$existingUsers, $defaultPassword, &$syncedOrtu) {
                    foreach ($parents as $p) {
                        $nis = $p['student_nis'];
                        $siswaId = $existingSiswas[$nis] ?? null;
                        $uName = $p['username'];

                        if (isset($existingUsers[$uName])) {
                            User::where('id', $existingUsers[$uName])->update([
                                'fullname' => $p['fullname'],
                                'student_id' => $siswaId,
                                'role_id' => 6,
                                'must_change_credentials' => true,
                                'is_active' => true,
                            ]);
                        } else {
                            $pu = User::create([
                                'username' => $uName,
                                'role_id' => 6,
                                'student_id' => $siswaId,
                                'fullname' => $p['fullname'],
                                'password' => $defaultPassword,
                                'must_change_credentials' => true,
                                'is_active' => true,
                            ]);
                            $existingUsers[$uName] = $pu->id;
                        }
                        $syncedOrtu++;
                    }
                });
                $output['ortu'] = "{$syncedOrtu} akun orang tua berhasil disinkronisasi.";
            }
        }

        // 5. Sinkronisasi Jadwal Pembelajaran
        if (in_array($only, ['all', 'jadwal'], true)) {
            $jadwalJson = $this->dataDir.'/master_jadwal.json';
            if (file_exists($jadwalJson)) {
                $schedules = json_decode(file_get_contents($jadwalJson), true);
                $classMap = $this->buildSchoolClassMap();
                $teachers = User::whereIn('role_id', [1, 2, 5])->pluck('id', 'fullname')->toArray();
                $teachersByUsername = User::whereIn('role_id', [1, 2, 5])->pluck('id', 'username')->toArray();

                // Pastikan seluruh ruang ada di tabel rooms
                $rooms = Room::pluck('id', 'name')->toArray();
                $neededRooms = [];
                foreach ($schedules as $s) {
                    $rmName = $s['room_name'] ?? 'Ruang Kelas';
                    if (! isset($rooms[$rmName])) {
                        $neededRooms[$rmName] = true;
                    }
                }
                foreach (array_keys($neededRooms) as $nR) {
                    $roomObj = Room::firstOrCreate(['name' => $nR]);
                    $rooms[$nR] = $roomObj->id;
                }

                $syncedJadwal = 0;
                DB::transaction(function () use ($schedules, $classMap, $teachers, $rooms, $fresh, &$syncedJadwal) {
                    if ($fresh) {
                        Schedule::truncate();
                    }

                    $fallbackTeacherId = ! empty($teachers) ? reset($teachers) : (User::whereIn('role_id', [1, 2, 5])->value('id') ?? 1);

                    foreach ($schedules as $s) {
                        $cObj = $this->resolveSchoolClass($s['class_name'], $classMap);
                        if (! $cObj) {
                            continue;
                        }

                        $classId = $cObj->id;
                        $className = $cObj->name;

                        $teacherId = null;
                        if (! empty($s['teacher_name']) && isset($teachers[$s['teacher_name']])) {
                            $teacherId = $teachers[$s['teacher_name']];
                        } elseif (! empty($s['teacher_id'])) {
                            $teacherId = $s['teacher_id'];
                        } else {
                            // Coba pencocokan nama guru
                            $norm = strtoupper(preg_replace('/[^A-Z]/', '', $s['teacher_name'] ?? ''));
                            foreach ($teachers as $origName => $tId) {
                                if (strtoupper(preg_replace('/[^A-Z]/', '', $origName)) === $norm) {
                                    $teacherId = $tId;
                                    break;
                                }
                            }
                        }

                        if (! $teacherId) {
                            $teacherId = $fallbackTeacherId;
                        }

                        $rName = $s['room_name'] ?? 'Ruang Kelas';
                        $roomId = $rooms[$rName] ?? null;

                        Schedule::updateOrCreate([
                            'cycle_number' => $s['cycle_number'],
                            'day_of_week' => $s['day_of_week'],
                            'class_id' => $classId,
                            'start_time' => $s['start_time'],
                            'end_time' => $s['end_time'],
                        ], [
                            'class_name' => $className,
                            'subject' => $s['subject'],
                            'teacher_id' => $teacherId,
                            'room_name' => $rName,
                            'room_id' => $roomId,
                            'academic_year' => $s['academic_year'] ?? '2026/2027',
                        ]);
                        $syncedJadwal++;
                    }
                });
                $output['jadwal'] = "{$syncedJadwal} sesi jadwal pembelajaran berhasil disinkronisasi.";
            }
        }

        // 6. Penugasan Awal Wali Kelas Otomatis
        if (in_array($only, ['all', 'guru', 'kelas'], true)) {
            $assignedCount = $this->seedDefaultHomerooms();
            if ($assignedCount > 0) {
                $output['wali_kelas'] = "{$assignedCount} rombel kelas berhasil ditugaskan Wali Kelas.";
            }
        }

        return $output;
    }

    /**
     * Bangun kamus normalisasi untuk seluruh rombel kelas sekolah
     */
    public function buildSchoolClassMap(): array
    {
        $classes = SchoolClass::all();
        $classMap = [];
        foreach ($classes as $c) {
            $norm = preg_replace('/[^a-z0-9]/', '', strtolower($c->name));
            $classMap[$norm] = $c;
            $classMap[preg_replace('/akl/', 'ak', $norm)] = $c;
            $classMap[preg_replace('/mplb/', 'mp', $norm)] = $c;
            $classMap[preg_replace('/bdp/', 'br', $norm)] = $c;
            $classMap[preg_replace('/bdp/', 'bd', $norm)] = $c;
        }

        return $classMap;
    }

    /**
     * Resolusi nama kelas mentah dari FET / spreadsheet ke entitas SchoolClass resmi
     */
    public function resolveSchoolClass(string $rawName, array $classMap): ?SchoolClass
    {
        $norm = preg_replace('/[^a-z0-9]/', '', strtolower($rawName));
        if (isset($classMap[$norm])) {
            return $classMap[$norm];
        }

        // Variasi singkatan FET: AK -> AKL, MP -> MPLB, BR -> BDP, BD -> BDP
        $var1 = preg_replace('/ak([0-9])/', 'akl$1', $norm);
        if (isset($classMap[$var1])) {
            return $classMap[$var1];
        }

        $var2 = preg_replace('/mp([0-9])/', 'mplb$1', $norm);
        if (isset($classMap[$var2])) {
            return $classMap[$var2];
        }

        $var3 = preg_replace('/br([0-9])/', 'bdp$1', $norm);
        if (isset($classMap[$var3])) {
            return $classMap[$var3];
        }

        $var4 = preg_replace('/bd([0-9]?)/', 'bdp$1', $norm);
        if (isset($classMap[$var4])) {
            return $classMap[$var4];
        }

        return null;
    }

    /**
     * Penugasan Awal Wali Kelas untuk Seluruh Rombel Sekolah
     */
    public function seedDefaultHomerooms(): int
    {
        $classes = SchoolClass::orderBy('name')->get();
        if ($classes->isEmpty()) {
            return 0;
        }

        $config = SystemConfiguration::current();
        $academicYear = $config->academic_year ?? '2026/2027';

        // Cari akun Rosyida Ramadhani (pengampu RPL)
        $rosyida = User::where('fullname', 'like', '%ROSYIDA RAMADHANI%')
            ->orWhere('username', 'like', '%rosyida%')
            ->first();

        // Ambil guru aktif
        $teachers = User::whereIn('role_id', [2, 5])
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        if ($teachers->isEmpty()) {
            return 0;
        }

        $assigned = 0;
        $teacherIdx = 0;

        DB::transaction(function () use ($classes, $teachers, $rosyida, $academicYear, &$assigned, &$teacherIdx) {
            foreach ($classes as $c) {
                // Prioritaskan Rosyida ke XI RPL 1
                $t = null;
                if ($rosyida && $c->name === 'XI RPL 1') {
                    $t = $rosyida;
                } else {
                    $t = $teachers[$teacherIdx % $teachers->count()];
                    if ($rosyida && $t->id === $rosyida->id) {
                        $teacherIdx++;
                        $t = $teachers[$teacherIdx % $teachers->count()];
                    }
                    $teacherIdx++;
                }

                if (! $t) {
                    continue;
                }

                HomeroomAssignment::updateOrCreate([
                    'school_class_id' => $c->id,
                    'class_name' => $c->name,
                    'academic_year' => $academicYear,
                ], [
                    'user_id' => $t->id,
                ]);

                DB::table('user_school_classes')->updateOrInsert([
                    'school_class_id' => $c->id,
                ], [
                    'user_id' => $t->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $t->update([
                    'class_id' => $c->id,
                    'role_id' => 5, // Status Wali Kelas
                ]);

                $assigned++;
            }
        });

        return $assigned;
    }

    /**
     * Unduh master file Markdown berdasarkan tipe
     */
    public function getMasterMarkdownContent(string $type): string
    {
        $fileName = match ($type) {
            'siswa' => 'master_siswa.md',
            'kelas' => 'master_kelas.md',
            'guru' => 'master_guru.md',
            'orang_tua', 'ortu' => 'master_orang_tua.md',
            'jadwal_1' => 'master_jadwal_putaran_1.md',
            'jadwal_2' => 'master_jadwal_putaran_2.md',
            default => 'master_guru.md',
        };

        $fullPath = $this->dataDir.'/'.$fileName;
        if (! file_exists($fullPath)) {
            return "# Berkas Master Belum Tersedia\n\nSilakan jalankan sinkronisasi data master terlebih dahulu.";
        }

        return file_get_contents($fullPath);
    }
}
