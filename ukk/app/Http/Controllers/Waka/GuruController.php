<?php

namespace App\Http\Controllers\Waka;

use App\Http\Controllers\Controller;
use App\Models\HomeroomAssignment;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
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

        $query = User::whereIn('role_id', [2, 5]) // Guru & Wali Kelas
            ->orderBy('fullname');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('fullname', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $gurus = $query->paginate(20)->withQueryString();

        return Inertia::render('Waka/Guru/Index', [
            'gurus' => $gurus,
            'filters' => [
                'search' => $search,
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
            'email' => ['nullable', 'email', 'max:100', 'unique:users,email,' . $guru->id],
            'phone' => ['nullable', 'string', 'max:25'],
            'is_active' => ['boolean'],
        ]);

        $guru->update([
            'fullname' => $validated['fullname'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

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
        $name = $guru->fullname;
        $guru->delete();

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
     * Download template import guru Excel
     */
    public function template(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(['Nama Lengkap', 'Username', 'NIP', 'Nomor HP', 'Email', 'Password', 'Wali Kelas', 'Guru BK'], null, 'A1');
        foreach (range('A', 'H') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return response()->streamDownload(
            fn () => (new Xlsx($spreadsheet))->save('php://output'),
            'template_import_guru.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    /**
     * Download contoh file import guru 5 data
     */
    public function contoh(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(['Nama Lengkap', 'Username', 'NIP', 'Nomor HP', 'Email', 'Password', 'Wali Kelas', 'Guru BK'], null, 'A1');
        $sheet->fromArray([
            ['Dra. Siti Aminah, M.Pd.', 'siti.aminah', '197505122000032001', '081234567001', 'siti.aminah@sekolah.sch.id', 'GuruPass123', 'X RPL 1', ''],
            ['Bambang Wijaya, S.Kom.', 'bambang.w', '198203152008011002', '081234567002', 'bambang.wijaya@sekolah.sch.id', 'GuruPass123', 'XI TKJ 1', ''],
            ['Candra Rahadian, S.Pd.', 'candra.r', '198506102010011003', '081234567003', 'candra.rahadian@sekolah.sch.id', 'GuruPass123', '', 'X AK 1, X AK 2, X AK 3'],
            ['Ahmad Fauzi, M.T.', 'ahmad.fauzi', '198011252006041004', '081234567004', 'ahmad.fauzi@sekolah.sch.id', 'GuruPass123', 'XII DKV 1', ''],
            ['Ryawidyaningtyas, S.Pd.', 'rya.widya', '199201102016032005', '081234567005', 'rya.widyaningtyas@sekolah.sch.id', 'GuruPass123', '', 'X RPL 1, X RPL 2'],
        ], null, 'A2');
        foreach (range('A', 'H') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return response()->streamDownload(
            fn () => (new Xlsx($spreadsheet))->save('php://output'),
            'contoh_import_guru_5_data.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    /**
     * Import data guru dari Excel atau PDF
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,pdf', 'max:20480'],
        ]);

        @set_time_limit(180);

        $uploadedFile = $request->file('file');
        $extension = strtolower($uploadedFile->getClientOriginalExtension());
        $sheetRows = [];

        if (in_array($extension, ['xlsx', 'xls'], true)) {
            try {
                $spreadsheet = IOFactory::load($uploadedFile->getRealPath());
                $sheetRows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
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
                        $pdfText .= implode(' ', $textMatches[1]) . "\n";
                    } elseif (preg_match_all('/\[(.*?)\]\s*TJ/s', $targetStream, $textMatches)) {
                        foreach ($textMatches[1] as $arrayStr) {
                            if (preg_match_all('/\((.*?)\)/s', $arrayStr, $itemMatches)) {
                                $pdfText .= implode('', $itemMatches[1]) . ' ';
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
                if ($trimmedLine === '') continue;

                // Support pipe-delimited PDF table: Nama | Username | NIP | Phone | Email | Pass | Walas
                if (str_contains($trimmedLine, '|')) {
                    $cols = array_map('trim', explode('|', $trimmedLine));
                    $sheetRows[] = $cols;
                    continue;
                }

                // Support numbered list e.g. "1. RAHARJO, S.IP., M.Pd" or FET timetable
                if (preg_match('/^\s*\d{1,3}[\.\,\)\s]+([A-Za-z\s\.,]+)/', $trimmedLine, $m)) {
                    $nameCand = trim($m[1]);
                    if (strlen($nameCand) >= 3 && !preg_match('/^(Daftar|Halaman|Senin|Selasa|Rabu|Kamis|Jumat|Sabtu|Minggu)\b/i', $nameCand)) {
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
            if (isset($matched['fullname']) || (isset($matched['username']) && isset($matched['nip']))) {
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
            if (mb_strtolower($fullname) === 'nama lengkap' || mb_strtolower($fullname) === 'nama') {
                continue;
            }

            $rawUsername = isset($columnIndexes['username']) && isset($row[$columnIndexes['username']]) ? trim((string) $row[$columnIndexes['username']]) : '';
            $rawNip = isset($columnIndexes['nip']) && isset($row[$columnIndexes['nip']]) ? trim((string) $row[$columnIndexes['nip']]) : '';
            $rawPhone = isset($columnIndexes['phone']) && isset($row[$columnIndexes['phone']]) ? trim((string) $row[$columnIndexes['phone']]) : '';
            $rawEmail = isset($columnIndexes['email']) && isset($row[$columnIndexes['email']]) ? strtolower(trim((string) $row[$columnIndexes['email']])) : '';
            $rawPassword = isset($columnIndexes['password']) && isset($row[$columnIndexes['password']]) ? trim((string) $row[$columnIndexes['password']]) : '';
            $rawWaliKelas = isset($columnIndexes['wali_kelas']) && isset($row[$columnIndexes['wali_kelas']]) ? trim((string) $row[$columnIndexes['wali_kelas']]) : '';

            // Generate username if not provided
            if ($rawUsername === '') {
                $baseUser = Str::slug($fullname, '.');
                if ($baseUser === '') {
                    $baseUser = 'guru.' . Str::random(5);
                }
                $finalUser = $baseUser;
                $counter = 1;
                while (User::where('username', $finalUser)->exists() || isset($seenUsernames[$finalUser])) {
                    $finalUser = $baseUser . $counter++;
                }
                $username = $finalUser;
            } else {
                $username = $rawUsername;
                if (User::where('username', $username)->exists() || isset($seenUsernames[$username])) {
                    $rowErrors[] = "Baris {$lineNumber}: Username '{$username}' sudah digunakan.";
                    continue;
                }
            }

            $nip = $rawNip !== '' ? $rawNip : null;
            if ($nip !== null) {
                if (User::where('nip', $nip)->exists() || isset($seenNips[$nip])) {
                    $rowErrors[] = "Baris {$lineNumber}: NIP '{$nip}' sudah digunakan.";
                    continue;
                }
            }

            $email = $rawEmail !== '' ? $rawEmail : null;
            if ($email !== null) {
                if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $rowErrors[] = "Baris {$lineNumber}: Format email '{$email}' tidak valid.";
                    continue;
                }
                if (User::where('email', $email)->exists() || isset($seenEmails[$email])) {
                    $rowErrors[] = "Baris {$lineNumber}: Email '{$email}' sudah digunakan.";
                    continue;
                }
            }

            $password = $rawPassword !== '' ? $rawPassword : 'password123';
            if (strlen($password) < 8) {
                $rowErrors[] = "Baris {$lineNumber}: Password minimal 8 karakter.";
                continue;
            }

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
            if ($email !== null) {
                $seenEmails[$email] = true;
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

        DB::transaction(function () use ($teacherRows) {
            foreach ($teacherRows as $t) {
                $existing = User::whereIn('role_id', [2, 5])
                    ->where('fullname', $t['fullname'])
                    ->first();

                $roleId = ! empty($t['class_ids']) ? 5 : 2;

                if ($existing) {
                    $existing->update([
                        'phone' => $t['phone'] ?? $existing->phone,
                        'email' => $t['email'] ?? $existing->email,
                        'nip' => $t['nip'] ?? $existing->nip,
                        'role_id' => $roleId,
                    ]);
                    $userId = $existing->id;
                } else {
                    $user = User::create([
                        'fullname' => $t['fullname'],
                        'username' => $t['username'],
                        'email' => $t['email'],
                        'nip' => $t['nip'],
                        'phone' => $t['phone'],
                        'password' => Hash::make($t['password']),
                        'role_id' => $roleId,
                        'is_active' => true,
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
            ->with('success', count($teacherRows) . " data guru berhasil diimpor dari {$fileTypeName}.");
    }
}

