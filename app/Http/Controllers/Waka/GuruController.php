<?php

namespace App\Http\Controllers\Waka;

use App\Http\Controllers\Controller;
use App\Models\HomeroomAssignment;
use App\Models\Journal;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\User;
use App\Services\MasterDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GuruController extends Controller
{
    /**
     * Tampilkan data master tenaga pendidik (guru)
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $perPageInput = $request->input('per_page', '20');

        $query = User::whereIn('role_id', [2, 5]) // Guru & Wali Kelas
            ->with(['schoolClass', 'homeroomAssignments'])
            ->orderBy('fullname');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('fullname', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%");
            });
        }

        $totalTeachers = User::whereIn('role_id', [2, 5])->count();
        $perPage = $perPageInput === 'all' ? max(100, $totalTeachers) : (int) $perPageInput;
        if ($perPage <= 0) {
            $perPage = 20;
        }

        $gurus = $query->paginate($perPage)->withQueryString();
        $classes = SchoolClass::orderBy('name')->get(['id', 'name', 'grade', 'jurusan']);

        return Inertia::render('Waka/Guru/Index', [
            'gurus' => $gurus,
            'totalTeachers' => $totalTeachers,
            'classes' => $classes,
            'filters' => [
                'search' => $search,
                'per_page' => $perPageInput,
            ],
        ]);
    }

    /**
     * Tambah guru baru beserta akun login
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:50', 'unique:users,username'],
            'fullname' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:100', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:25'],
            'role_id' => ['required', 'in:2,5'], // 2: Guru, 5: Wali Kelas
        ]);

        User::create([
            'role_id' => (int) $validated['role_id'],
            'username' => strtolower($validated['username']),
            'fullname' => $validated['fullname'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make('smkn1bantul2026'),
            'must_change_credentials' => true,
            'is_active' => true,
        ]);

        return back()->with('success', "Tenaga pendidik {$validated['fullname']} berhasil didaftarkan dengan kata sandi awal smkn1bantul2026.");
    }

    /**
     * Perbarui data guru
     */
    public function update(Request $request, User $guru): RedirectResponse
    {
        $validated = $request->validate([
            'fullname' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:100', 'unique:users,email,'.$guru->id],
            'phone' => ['nullable', 'string', 'max:25'],
            'role_id' => ['nullable', 'in:2,5'],
            'class_id' => ['nullable'],
            'is_active' => ['boolean'],
        ]);

        $roleId = isset($validated['role_id']) ? (int) $validated['role_id'] : $guru->role_id;
        $classId = ! empty($validated['class_id']) && $validated['class_id'] !== 'none' ? (int) $validated['class_id'] : null;

        DB::transaction(function () use ($guru, $validated, $roleId, $classId, $request) {
            $guru->update([
                'fullname' => $validated['fullname'],
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'role_id' => $roleId,
                'class_id' => $classId,
                'is_active' => $request->boolean('is_active', true),
            ]);

            if ($classId && $roleId === 5) {
                $class = SchoolClass::find($classId);
                if ($class) {
                    HomeroomAssignment::where('school_class_id', $class->id)->delete();
                    DB::table('user_school_classes')->where('school_class_id', $class->id)->delete();

                    HomeroomAssignment::create([
                        'school_class_id' => $class->id,
                        'class_name' => $class->name,
                        'user_id' => $guru->id,
                        'academic_year' => '2026/2027',
                    ]);

                    DB::table('user_school_classes')->insertOrIgnore([
                        'user_id' => $guru->id,
                        'school_class_id' => $class->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            } elseif ($roleId === 2) {
                HomeroomAssignment::where('user_id', $guru->id)->delete();
                DB::table('user_school_classes')->where('user_id', $guru->id)->delete();
                $guru->update(['class_id' => null]);
            }
        });

        return back()->with('success', "Data tenaga pendidik {$guru->fullname} berhasil diperbarui.");
    }

    /**
     * Reset password akun guru ke default
     */
    public function resetPassword(User $guru): RedirectResponse
    {
        $guru->update([
            'password' => Hash::make('smkn1bantul2026'),
            'must_change_credentials' => true,
        ]);

        return back()->with('success', "Kata sandi akun {$guru->fullname} berhasil di-reset ke smkn1bantul2026.");
    }

    /**
     * Hapus data guru
     */
    public function destroy(User $guru): RedirectResponse
    {
        $dependencies = [];

        $scheduleCount = Schedule::where('teacher_id', $guru->id)->count();
        if ($scheduleCount > 0) {
            $dependencies[] = "{$scheduleCount} jadwal mengajar";
        }

        $journalCount = Journal::where('teacher_id', $guru->id)->count();
        if ($journalCount > 0) {
            $dependencies[] = "{$journalCount} jurnal KBM";
        }

        if ($guru->isWaliKelas()) {
            $dependencies[] = 'penugasan sebagai wali kelas';
        }

        if (! empty($dependencies)) {
            $details = implode(', ', $dependencies);

            return back()->with('error', "Data guru {$guru->fullname} tidak dapat dihapus karena data berelasi masih aktif ({$details}). Silakan nonaktifkan akun guru atau alihkan jadwal/penugasan terlebih dahulu demi integritas data.");
        }

        $name = $guru->fullname;
        DB::transaction(function () use ($guru) {
            DB::table('homeroom_assignments')->where('user_id', $guru->id)->delete();
            DB::table('user_school_classes')->where('user_id', $guru->id)->delete();
            $guru->delete();
        });

        return back()->with('success', "Data guru {$name} berhasil dihapus.");
    }

    /**
     * Reset seluruh data tenaga guru secara keseluruhan dari 0 untuk persiapan angkatan baru
     */
    public function resetAll(): RedirectResponse
    {
        $currentUserId = auth()->id();

        // Seluruh user guru (role_id 2 & 5), kecualikan akun waka/admin saat ini
        $teachers = User::whereIn('role_id', [2, 5])
            ->where('id', '!=', $currentUserId)
            ->where('username', '!=', 'admin')
            ->get();

        $count = $teachers->count();
        if ($count === 0) {
            return back()->with('info', 'Tidak ada data guru yang perlu di-reset.');
        }

        $teacherIds = $teachers->pluck('id')->all();

        DB::transaction(function () use ($teacherIds) {
            // 1. Bersihkan penugasan wali kelas
            DB::table('homeroom_assignments')->whereIn('user_id', $teacherIds)->delete();
            DB::table('user_school_classes')->whereIn('user_id', $teacherIds)->delete();

            // 2. Hapus akun guru (jadwal pelajaran lama otomatis cascade)
            User::whereIn('id', $teacherIds)->delete();
        });

        return back()->with('success', "Seluruh data tenaga guru ({$count} guru) berhasil di-reset dari 0. Silakan impor berkas data guru baru untuk angkatan berikutnya.");
    }

    /**
     * Download template import guru Excel (format resmi Data Pegawai sheet Guru)
     */
    public function template(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Guru');
        $sheet->fromArray([
            'NO',
            'NIP',
            'NAMA',
            'JENIS KELAMIN',
            'GOLONGAN',
            'TMT GOLONGAN',
            'JABATAN',
            'PENDIDIKAN',
            'MAPEL',
            'KODE/ID GURU',
        ], null, 'A1');

        foreach (range('A', 'J') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return response()->streamDownload(
            fn () => (new Xlsx($spreadsheet))->save('php://output'),
            'template_import_guru.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    /**
     * Download contoh file import guru (format resmi sheet Guru dengan contoh data)
     */
    public function contoh(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Guru');
        $sheet->fromArray([
            'NO',
            'NIP',
            'NAMA',
            'JENIS KELAMIN',
            'GOLONGAN',
            'TMT GOLONGAN',
            'JABATAN',
            'PENDIDIKAN',
            'MAPEL',
            'KODE/ID GURU',
        ], null, 'A1');

        $sheet->fromArray([
            [1, '196904041989031010', 'RAHARJO S.IP., M.Pd.', 'L', 'IV/c', '01-04-2022', 'Kepala Sekolah', 'S2 Manajemen Pendidikan', 'Kepala Sekolah', 1],
            [2, '197109021994012001', 'SUNARSIH, S.Pd.', 'P', 'IV/b', '01-10-2021', 'Guru Madya', 'S1 Pendidikan Kimia', 'IPAS', 2],
            [3, '196906051994122003', 'ARIS PURWANTINAH, S.Pd.', 'P', 'IV/b', '01-04-2023', 'Guru Madya', 'S1 Pendidikan Tata Niaga', 'PRODUKTIF BISNIS RITEL', 3],
            [4, '197210221995122001', 'AISYAH WULANDARI, S.Pd', 'P', 'IV/b', '01-10-2023', 'Guru Madya', 'S1 Bimbingan Konseling', 'BIMBINGAN KONSELING', 4],
            [5, '196706051997021002', 'SUJAR HARTONO, S.Pd.', 'L', 'IV/a', '01-04-2020', 'Guru Pembina', 'S1 Pendidikan Bahasa Inggris', 'BAHASA INGGRIS', 9],
        ], null, 'A2');

        foreach (range('A', 'J') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return response()->streamDownload(
            fn () => (new Xlsx($spreadsheet))->save('php://output'),
            'contoh_import_guru_5_data.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    /**
     * Download master data guru dalam format Markdown (.md)
     */
    public function downloadMaster(MasterDataService $masterDataService): StreamedResponse
    {
        $content = $masterDataService->getMasterMarkdownContent('guru');

        return response()->streamDownload(
            function () use ($content) {
                echo $content;
            },
            'master_data_guru.md',
            ['Content-Type' => 'text/markdown; charset=UTF-8']
        );
    }

    /**
     * Import data guru dari Excel, PDF, atau Markdown .md
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:20480'],
        ]);

        $allowedExtensions = ['xlsx', 'xls', 'pdf', 'md', 'txt'];
        $uploadedFile = $request->file('file');
        $extension = strtolower($uploadedFile->getClientOriginalExtension());
        if (! in_array($extension, $allowedExtensions, true)) {
            throw ValidationException::withMessages([
                'file' => 'Format file tidak didukung. Harap gunakan file .xlsx, .xls, .pdf, atau .md.',
            ]);
        }

        @set_time_limit(180);

        $sheetRows = [];

        if ($extension === 'md') {
            $content = (string) file_get_contents($uploadedFile->getRealPath());
            $parsedTable = (new MasterDataService)->parseMarkdownTable($content);
            $sheetRows[] = ['Nama Lengkap', 'Username', 'NIP', 'Phone', 'Email', 'Password', 'Wali Kelas', 'Guru BK'];
            foreach ($parsedTable as $pRow) {
                $fullname = $pRow['nama lengkap & gelar'] ?? ($pRow['nama lengkap'] ?? ($pRow['nama guru'] ?? ($pRow['nama'] ?? '')));
                $username = trim((string) ($pRow['username default'] ?? ($pRow['username'] ?? '')), ' `');
                $nip = trim((string) ($pRow['nip'] ?? ''), ' `');

                if (trim($fullname) === '') {
                    continue;
                }

                $sheetRows[] = [
                    $fullname,
                    $username,
                    $nip,
                    null, // phone
                    null, // email
                    'smkn1bantul2026',
                    null, // wali_kelas
                    null, // guru_bk
                ];
            }
        } elseif (in_array($extension, ['xlsx', 'xls'], true)) {
            try {
                $spreadsheet = IOFactory::load($uploadedFile->getRealPath());
                $sheet = $spreadsheet->getSheetByName('Guru') ?? $spreadsheet->getActiveSheet();
                $sheetRows = $sheet->toArray(null, true, true, false);
            } catch (\Throwable $exception) {
                throw ValidationException::withMessages([
                    'file' => 'File Excel tidak dapat dibaca. Gunakan file .xlsx atau .xls yang valid.',
                ]);
            }
        } elseif ($extension === 'pdf') {
            $filePath = $uploadedFile->getRealPath();
            $pdfText = '';

            // Decompress PDF text streams
            $content = (string) @file_get_contents($filePath);
            if (preg_match_all('/stream[\r\n]+(.*?)[\r\n]+endstream/s', $content, $matches)) {
                foreach ($matches[1] as $stream) {
                    $decompressed = @gzuncompress($stream);
                    $targetStream = $decompressed !== false ? $decompressed : $stream;
                    if (preg_match_all('/\((.*?)\)\s*T[jJ]/s', $targetStream, $textMatches)) {
                        $pdfText .= implode(' ', $textMatches[1])."\n";
                    } elseif (preg_match_all('/\[(.*?)\]\s*TJ/s', $targetStream, $textMatches)) {
                        foreach ($textMatches[1] as $arrayStr) {
                            if (preg_match_all('/\((.*?)\)/s', $arrayStr, $itemMatches)) {
                                $pdfText .= implode('', $itemMatches[1]).' ';
                            }
                        }
                        $pdfText .= "\n";
                    }
                }
            }

            if (trim($pdfText) === '') {
                $rawClean = preg_replace('/[^a-zA-Z0-9\s\|\.,\-_@:]/', ' ', $content);
                $pdfText = $rawClean;
            }

            if (trim($pdfText) === '') {
                throw ValidationException::withMessages([
                    'file' => 'Teks dari file PDF tidak dapat dibaca atau file berupa gambar tanpa teks. Harap gunakan file PDF teks atau file Excel.',
                ]);
            }

            // Parse text lines from PDF
            $lines = explode("\n", $pdfText);
            foreach ($lines as $line) {
                $trimmedLine = trim($line);
                if ($trimmedLine === '') {
                    continue;
                }

                // Support pipe-delimited PDF table: Nama | Username | NIP | Phone | Email | Pass | Walas
                if (str_contains($trimmedLine, '|')) {
                    $cols = array_map('trim', explode('|', $trimmedLine));
                    $sheetRows[] = $cols;

                    continue;
                }

                // Support numbered list e.g. "1. RAHARJO, S.IP., M.Pd" or FET timetable
                if (preg_match('/^\s*\d{1,3}[\.\,\)\s]+([A-Za-z\s\.,]+)/', $trimmedLine, $m)) {
                    $nameCand = trim($m[1]);
                    if (strlen($nameCand) >= 3 && ! preg_match('/^(Daftar|Halaman|Senin|Selasa|Rabu|Kamis|Jumat|Sabtu|Minggu)\b/i', $nameCand)) {
                        $sheetRows[] = [$nameCand];
                    }
                }
            }
        }

        if (count($sheetRows) > 1001) {
            throw ValidationException::withMessages([
                'file' => 'File maksimal berisi 1.000 baris data guru.',
            ]);
        }

        $headerAliases = [
            'nama lengkap' => 'fullname',
            'nama guru' => 'fullname',
            'nama' => 'fullname',
            'fullname' => 'fullname',
            'name' => 'fullname',
            'username' => 'username',
            'user' => 'username',
            'nip' => 'nip',
            'nomor hp' => 'phone',
            'no hp' => 'phone',
            'no_hp' => 'phone',
            'telepon' => 'phone',
            'phone' => 'phone',
            'hp' => 'phone',
            'email pemulihan' => 'email',
            'email' => 'email',
            'password' => 'password',
            'kata sandi' => 'password',
            'pass' => 'password',
            'wali kelas' => 'wali_kelas',
            'kelas wali' => 'wali_kelas',
            'wali' => 'wali_kelas',
            'kelas' => 'wali_kelas',
            'guru bk' => 'guru_bk',
            'kelas bk' => 'guru_bk',
            'bk' => 'guru_bk',
            'kode/id guru' => 'teacher_code',
            'kode guru' => 'teacher_code',
            'kode' => 'teacher_code',
            'id guru' => 'teacher_code',
            'mapel' => 'mapel',
            'jabatan' => 'jabatan',
            'pendidikan' => 'pendidikan',
            'golongan' => 'golongan',
            'jenis kelamin' => 'gender',
            'jk' => 'gender',
        ];

        $columnIndexes = [];
        $dataRows = $sheetRows;

        foreach ($sheetRows as $idx => $row) {
            $matched = [];
            foreach ($row as $colIdx => $cell) {
                $norm = mb_strtolower(trim((string) $cell));
                $norm = ltrim($norm, "\xEF\xBB\xBF");
                if (isset($headerAliases[$norm])) {
                    $matched[$headerAliases[$norm]] = $colIdx;
                }
            }
            if (isset($matched['fullname']) || (isset($matched['username']) && isset($matched['nip'])) || isset($matched['teacher_code'])) {
                $columnIndexes = $matched;
                $dataRows = array_slice($sheetRows, $idx + 1);
                break;
            }
        }

        // Fallback default column order if no header row found
        if ($columnIndexes === []) {
            $defaultFields = ['fullname', 'username', 'nip', 'phone', 'email', 'password', 'wali_kelas', 'guru_bk'];
            foreach ($defaultFields as $k => $f) {
                $columnIndexes[$f] = $k;
            }
        }

        $allSchoolClasses = SchoolClass::all()->keyBy(fn ($c) => mb_strtolower(trim($c->name)));
        $teacherRows = [];
        $rowErrors = [];
        $seenUsernames = [];
        $seenNips = [];
        $seenEmails = [];

        foreach ($dataRows as $rowIndex => $row) {
            $lineNumber = $rowIndex + 2;
            $isEmptyRow = collect($row)->every(fn ($val) => trim((string) $val) === '');
            if ($isEmptyRow) {
                continue;
            }

            $fullname = isset($columnIndexes['fullname']) && isset($row[$columnIndexes['fullname']]) ? trim((string) $row[$columnIndexes['fullname']], " \t\n\r\0\x0B\x0C\f") : '';
            if (preg_replace('/[\s\x00-\x1F\x7F]+/', '', $fullname) === '') {
                continue;
            }
            if (mb_strtolower($fullname) === 'nama lengkap' || mb_strtolower($fullname) === 'nama' || mb_strtolower($fullname) === 'no' || in_array(trim($fullname), ['L', 'P', 'Laki-laki', 'Perempuan'], true) || is_numeric($fullname)) {
                continue;
            }

            $rawUsername = isset($columnIndexes['username']) && isset($row[$columnIndexes['username']]) ? trim((string) $row[$columnIndexes['username']], " \t\n\r\0\x0B`") : '';
            $rawNip = isset($columnIndexes['nip']) && isset($row[$columnIndexes['nip']]) ? trim((string) $row[$columnIndexes['nip']], " \t\n\r\0\x0B`") : '';
            $rawCode = isset($columnIndexes['teacher_code']) && isset($row[$columnIndexes['teacher_code']]) ? trim((string) $row[$columnIndexes['teacher_code']]) : '';
            $rawPhone = isset($columnIndexes['phone']) && isset($row[$columnIndexes['phone']]) ? trim((string) $row[$columnIndexes['phone']]) : '';
            $rawEmail = isset($columnIndexes['email']) && isset($row[$columnIndexes['email']]) ? strtolower(trim((string) $row[$columnIndexes['email']])) : '';
            $rawPassword = isset($columnIndexes['password']) && isset($row[$columnIndexes['password']]) ? trim((string) $row[$columnIndexes['password']]) : '';
            $rawWaliKelas = isset($columnIndexes['wali_kelas']) && isset($row[$columnIndexes['wali_kelas']]) ? trim((string) $row[$columnIndexes['wali_kelas']]) : '';

            $nipDigits = preg_replace('/\D/', '', $rawNip);
            $nip = strlen($nipDigits) >= 8 ? $nipDigits : null;

            // Generate clean username format guru.<kode> (Option 1)
            if ($rawUsername === '') {
                if ($rawCode !== '' && is_numeric($rawCode)) {
                    $baseUser = 'guru.'.str_pad((int) $rawCode, 2, '0', STR_PAD_LEFT);
                } else {
                    $baseUser = 'guru.'.str_pad(count($teacherRows) + 1, 2, '0', STR_PAD_LEFT);
                }
                $finalUser = $baseUser;
                $counter = 1;
                while (isset($seenUsernames[$finalUser])) {
                    $finalUser = $baseUser.'_'.$counter++;
                }
                $username = $finalUser;
            } else {
                $username = $rawUsername;
            }

            $email = $rawEmail !== '' ? $rawEmail : null;
            if ($email !== null) {
                if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $email = null; // Abaikan jika format tidak valid pada sheet
                }
            }

            $password = $rawPassword !== '' ? $rawPassword : 'smkn1bantul2026';
            $phone = $rawPhone !== '' ? $rawPhone : null;

            // Resolve Wali Kelas classes
            $assignedClassIds = [];
            if ($rawWaliKelas !== '') {
                $classNames = array_map('trim', preg_split('/[,;]+/', $rawWaliKelas));
                foreach ($classNames as $cName) {
                    $normCName = mb_strtolower($cName);
                    if (isset($allSchoolClasses[$normCName])) {
                        $assignedClassIds[] = $allSchoolClasses[$normCName]->id;
                    }
                }
            }

            $seenUsernames[$username] = true;
            if ($nip !== null) {
                $seenNips[$nip] = true;
            }

            $teacherRows[] = [
                'fullname' => $fullname,
                'username' => $username,
                'nip' => $nip,
                'phone' => $phone,
                'email' => $email,
                'password' => $password,
                'class_ids' => $assignedClassIds,
            ];
        }

        if ($teacherRows === [] && $rowErrors === []) {
            $rowErrors[] = 'File tidak berisi baris data guru yang valid.';
        }

        if ($rowErrors !== []) {
            throw ValidationException::withMessages(['file' => $rowErrors]);
        }

        $defaultPassword = Hash::make('smkn1bantul2026');

        DB::transaction(function () use ($teacherRows, $defaultPassword) {
            foreach ($teacherRows as $t) {
                $existing = null;
                if (! empty($t['nip'])) {
                    $existing = User::where('nip', $t['nip'])->first();
                }
                if (! $existing && ! empty($t['username'])) {
                    $existing = User::where('username', $t['username'])->first();
                }
                if (! $existing) {
                    $existing = User::whereIn('role_id', [2, 5])
                        ->where('fullname', $t['fullname'])
                        ->first();
                }

                $roleId = ! empty($t['class_ids']) ? 5 : 2;

                $passwordHash = $t['password'] === 'smkn1bantul2026'
                    ? $defaultPassword
                    : Hash::make($t['password']);

                if ($existing) {
                    $existing->update([
                        'fullname' => $t['fullname'],
                        'phone' => $t['phone'] ?? $existing->phone,
                        'email' => $t['email'] ?? $existing->email,
                        'nip' => $t['nip'] ?? $existing->nip,
                        'role_id' => $existing->role_id === 1 ? 1 : $roleId, // Pertahankan waka jika waka
                    ]);
                    $userId = $existing->id;
                } else {
                    $user = User::create([
                        'fullname' => $t['fullname'],
                        'username' => $t['username'],
                        'email' => $t['email'],
                        'nip' => $t['nip'],
                        'phone' => $t['phone'],
                        'password' => $passwordHash,
                        'role_id' => $roleId,
                        'is_active' => true,
                        'must_change_credentials' => true,
                    ]);
                    $userId = $user->id;
                }

                if (! empty($t['class_ids'])) {
                    foreach ($t['class_ids'] as $classId) {
                        // User school class pivot
                        DB::table('user_school_classes')->insertOrIgnore([
                            'user_id' => $userId,
                            'school_class_id' => $classId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        // Homeroom assignment
                        $targetClass = SchoolClass::find($classId);
                        if ($targetClass) {
                            HomeroomAssignment::firstOrCreate([
                                'user_id' => $userId,
                                'class_name' => $targetClass->name,
                                'academic_year' => '2026/2027',
                            ], [
                                'school_class_id' => $targetClass->id,
                            ]);
                        }
                    }
                }
            }
        });

        $fileTypeName = $extension === 'pdf' ? 'PDF' : 'Excel';

        return redirect()->route('waka.guru.index')
            ->with('success', count($teacherRows)." data guru berhasil diimpor dari {$fileTypeName}.");
    }
}
