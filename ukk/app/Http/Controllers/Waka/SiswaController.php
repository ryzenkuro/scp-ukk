<?php

namespace App\Http\Controllers\Waka;

use App\Http\Controllers\Controller;
use App\Models\Alumni;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\SchoolClass;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SiswaController extends Controller
{
    /**
     * Tampilkan data master siswa & kelas
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $kelas = $request->input('kelas');
        $status = $request->input('status', 'aktif');
        $angkatan = $request->input('angkatan');

        $query = Siswa::with(['user', 'parentUser'])->orderBy('kelas')->orderBy('name');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%");
            });
        }

        if ($kelas && $kelas !== 'all') {
            $query->where('kelas', $kelas);
        }

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($angkatan && $angkatan !== 'all') {
            $query->where('angkatan', (int) $angkatan);
        }

        $siswas = $query->paginate(20)->withQueryString();

        $kelasList = SchoolClass::orderBy('name')->pluck('name');
        if ($kelasList->isEmpty()) {
            $kelasList = Siswa::distinct()->pluck('kelas')->filter();
        }

        $angkatanList = Siswa::distinct()->orderByDesc('angkatan')->pluck('angkatan')->filter();

        return Inertia::render('Waka/Siswa/Index', [
            'siswas' => $siswas,
            'filters' => [
                'search' => $search,
                'kelas' => $kelas,
                'status' => $status,
                'angkatan' => $angkatan,
            ],
            'kelasList' => $kelasList,
            'angkatanList' => $angkatanList,
        ]);
    }

    /**
     * Tambah data siswa baru beserta akun siswa & akun orang tua otomatis
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nis' => ['required', 'string', 'max:20', 'unique:siswas,nis', 'unique:users,username'],
            'name' => ['required', 'string', 'max:100'],
            'kelas' => ['required', 'string', 'max:30'],
            'jurusan' => ['nullable', 'string', 'max:50'],
            'angkatan' => ['required', 'integer', 'min:2020', 'max:2040'],
            'gender' => ['required', 'in:L,P'],
            'alamat' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:25'],
            'email' => ['nullable', 'email', 'max:100'],
        ]);

        $defaultPassword = Hash::make('smkn1bantul2026');

        DB::transaction(function () use ($validated, $defaultPassword) {
            $siswa = Siswa::create([
                'nis' => $validated['nis'],
                'name' => strtoupper($validated['name']),
                'kelas' => $validated['kelas'],
                'jurusan' => $validated['jurusan'] ?? 'RPL',
                'angkatan' => $validated['angkatan'],
                'gender' => $validated['gender'],
                'alamat' => $validated['alamat'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'email' => $validated['email'] ?? null,
                'status' => 'aktif',
            ]);

            // 1. Akun Siswa (Username = NIS, Password Default = smkn1bantul2026)
            User::create([
                'role_id' => 3, // Siswa
                'student_id' => $siswa->id,
                'username' => $siswa->nis,
                'fullname' => $siswa->name,
                'email' => $siswa->email,
                'phone' => $siswa->phone,
                'password' => $defaultPassword,
                'must_change_credentials' => true,
                'is_active' => true,
            ]);

            // 2. Akun Orang Tua (Username = ortu_[NIS], Password Default = smkn1bantul2026)
            User::create([
                'role_id' => 6, // Orang Tua
                'student_id' => $siswa->id,
                'username' => 'ortu_' . $siswa->nis,
                'fullname' => 'Orang Tua ' . $siswa->name,
                'phone' => $siswa->phone,
                'password' => $defaultPassword,
                'must_change_credentials' => true,
                'is_active' => true,
            ]);
        });

        return back()->with('success', "Data siswa {$validated['name']} berhasil ditambahkan beserta akun Siswa dan Orang Tua.");
    }

    /**
     * Perbarui data siswa
     */
    public function update(Request $request, Siswa $siswa): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'kelas' => ['required', 'string', 'max:30'],
            'jurusan' => ['nullable', 'string', 'max:50'],
            'angkatan' => ['required', 'integer', 'min:2020', 'max:2040'],
            'gender' => ['required', 'in:L,P'],
            'alamat' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:25'],
            'email' => ['nullable', 'email', 'max:100'],
            'status' => ['required', 'in:aktif,lulus,mutasi,keluar'],
        ]);

        $siswa->update([
            'name' => strtoupper($validated['name']),
            'kelas' => $validated['kelas'],
            'jurusan' => $validated['jurusan'] ?? $siswa->jurusan,
            'angkatan' => $validated['angkatan'],
            'gender' => $validated['gender'],
            'alamat' => $validated['alamat'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'status' => $validated['status'],
        ]);

        // Sinkronisasi nama ke akun user jika ada perubahan nama
        if ($siswa->user) {
            $siswa->user->update(['fullname' => $siswa->name, 'email' => $siswa->email, 'phone' => $siswa->phone]);
        }
        if ($siswa->parentUser) {
            $siswa->parentUser->update(['fullname' => 'Orang Tua ' . $siswa->name]);
        }

        return back()->with('success', "Data siswa {$siswa->name} berhasil diperbarui.");
    }

    /**
     * Hapus data siswa dan akun yang tertaut
     */
    public function destroy(Siswa $siswa): RedirectResponse
    {
        $name = $siswa->name;

        DB::transaction(function () use ($siswa) {
            // Hapus akun user siswa & ortu
            User::where('student_id', $siswa->id)->delete();
            $siswa->delete();
        });

        return back()->with('success', "Data siswa {$name} dan akun tertaut berhasil dihapus.");
    }

    /**
     * Fitur Naik Kelas per Angkatan & Naik Kelulusan untuk Kelas XII
     */
    public function naikKelas(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'angkatan' => ['required', 'integer'],
        ]);

        $angkatan = (int) $validated['angkatan'];

        $siswas = Siswa::where('angkatan', $angkatan)
            ->where('status', 'aktif')
            ->get();

        if ($siswas->isEmpty()) {
            return back()->with('error', "Tidak ada siswa aktif ditemukan pada Angkatan {$angkatan}.");
        }

        $naikCount = 0;
        $lulusCount = 0;
        $currentYear = (string) date('Y');
        $today = now()->toDateString();

        DB::transaction(function () use ($siswas, $currentYear, $today, $angkatan, &$naikCount, &$lulusCount) {
            foreach ($siswas as $siswa) {
                $trimmedKelas = strtoupper(trim($siswa->kelas));

                // 1. Khusus Kelas XII: Naik Kelulusan ke Alumni & Nonaktifkan Akun
                if (str_starts_with($trimmedKelas, 'XII')) {
                    $siswa->update([
                        'status' => 'lulus',
                        'tahun_lulus' => $currentYear,
                        'tanggal_lulus' => $today,
                    ]);

                    // Salin ke data Alumni
                    Alumni::create([
                        'student_id' => $siswa->id,
                        'nis' => $siswa->nis,
                        'name' => $siswa->name,
                        'jurusan' => $siswa->jurusan,
                        'kelas_terakhir' => $siswa->kelas,
                        'tahun_lulus' => $currentYear,
                        'tanggal_lulus' => $today,
                        'alamat' => $siswa->alamat,
                        'phone' => $siswa->phone,
                        'email' => $siswa->email,
                        'keterangan' => "Lulus Tingkat XII Angkatan {$angkatan}",
                    ]);

                    // Nonaktifkan akun login siswa karena sudah lulus
                    User::where('student_id', $siswa->id)
                        ->where('role_id', 3)
                        ->update(['is_active' => false]);

                    $lulusCount++;
                }
                // 2. Kelas XI naik ke Kelas XII (misal XI RPL 1 -> XII RPL 1)
                elseif (str_starts_with($trimmedKelas, 'XI')) {
                    $newKelas = preg_replace('/^XI\b/i', 'XII', $siswa->kelas);
                    $siswa->update(['kelas' => $newKelas]);
                    $naikCount++;
                }
                // 3. Kelas X naik ke Kelas XI (misal X RPL 1 -> XI RPL 1)
                elseif (str_starts_with($trimmedKelas, 'X')) {
                    $newKelas = preg_replace('/^X\b/i', 'XI', $siswa->kelas);
                    $siswa->update(['kelas' => $newKelas]);
                    $naikCount++;
                }
            }
        });

        return back()->with(
            'success',
            "Proses selesai untuk Angkatan {$angkatan}: {$naikCount} siswa berhasil Naik Kelas, dan {$lulusCount} siswa Kelas XII diproses Naik Kelulusan ke Arsip Alumni."
        );
    }

    /**
     * Download template import siswa Excel
     */
    public function template(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(['Nama', 'NIS', 'Kelas', 'Jurusan', 'No HP', 'Email', 'Alamat'], null, 'A1');
        foreach (range('A', 'G') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return response()->streamDownload(
            fn () => (new Xlsx($spreadsheet))->save('php://output'),
            'template_import_siswa.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    /**
     * Download contoh file import siswa 5 data
     */
    public function contoh(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(['Nama', 'NIS', 'Kelas', 'Jurusan', 'No HP', 'Email', 'Alamat'], null, 'A1');
        $sheet->fromArray([
            ['Dinda Safitri', 'IMP26001', 'X RPL 1', 'RPL', '081200000001', '', 'Jl. Melati No. 1'],
            ['Rizky Maulana', 'IMP26002', 'X TKJ 1', 'TKJ', '081200000002', '', 'Jl. Kenanga No. 2'],
            ['Aulia Rahma', 'IMP26003', 'XI DKV 1', 'DKV', '081200000003', '', 'Jl. Mawar No. 3'],
            ['Fajar Ramadhan', 'IMP26004', 'XI AKL 1', 'AKL', '081200000004', '', 'Jl. Anggrek No. 4'],
            ['Nabila Putri', 'IMP26005', 'XII MP 1', 'MP', '081200000005', '', 'Jl. Cempaka No. 5'],
        ], null, 'A2');
        foreach (range('A', 'G') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return response()->streamDownload(
            fn () => (new Xlsx($spreadsheet))->save('php://output'),
            'contoh_import_siswa_5_data.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    /**
     * Import data siswa dari Excel / CSV / Multi-sheet Daftar Hadir
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:20480'],
        ]);

        $allowedExtensions = ['xlsx', 'xls', 'csv', 'txt'];
        $extension = strtolower($request->file('file')->getClientOriginalExtension());
        if (! in_array($extension, $allowedExtensions, true)) {
            throw ValidationException::withMessages([
                'file' => 'Format file tidak didukung. Harap gunakan file .xlsx, .xls, atau .csv.',
            ]);
        }

        @set_time_limit(180);

        try {
            $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages([
                'file' => 'File tidak dapat dibaca. Gunakan file .xlsx, .xls, atau .csv yang valid.',
            ]);
        }

        $headerAliases = [
            'nama' => 'name',
            'nama siswa' => 'name',
            'name' => 'name',
            'nis' => 'nis',
            'kelas' => 'kelas',
            'jurusan' => 'jurusan',
            'alamat' => 'alamat',
            'no hp' => 'phone',
            'no_hp' => 'phone',
            'nomor hp' => 'phone',
            'phone' => 'phone',
            'email' => 'email',
            'email pemulihan' => 'email',
        ];

        $firstSheet = $spreadsheet->getSheet(0);
        $firstSheetRows = $firstSheet->toArray(null, true, true, false);

        $isStandardFormat = false;
        $standardHeaderIndex = null;
        $columnIndexes = [];

        foreach (array_slice($firstSheetRows, 0, 10) as $rowIndex => $row) {
            $tempIndexes = [];
            foreach ($row as $colIdx => $header) {
                $norm = mb_strtolower(trim((string) $header));
                $norm = ltrim($norm, "\xEF\xBB\xBF");
                if (isset($headerAliases[$norm])) {
                    $tempIndexes[$headerAliases[$norm]] = $colIdx;
                }
            }
            if (isset($tempIndexes['name']) && (isset($tempIndexes['kelas']) || isset($tempIndexes['nis']))) {
                $isStandardFormat = true;
                $standardHeaderIndex = $rowIndex;
                $columnIndexes = $tempIndexes;
                break;
            }
        }

        $studentRows = [];
        $rowErrors = [];

        if ($isStandardFormat) {
            $rows = array_slice($firstSheetRows, $standardHeaderIndex + 1);
            if (count($rows) > 5000) {
                throw ValidationException::withMessages([
                    'file' => 'File maksimal berisi 5.000 baris data siswa.',
                ]);
            }

            $seenNis = [];
            foreach ($rows as $rowIndex => $row) {
                $lineNumber = $rowIndex + $standardHeaderIndex + 2;
                $isEmptyRow = collect($row)->every(fn ($value) => trim((string) $value) === '');
                if ($isEmptyRow) {
                    continue;
                }

                $student = [];
                foreach (['name', 'nis', 'kelas', 'jurusan', 'alamat', 'phone', 'email'] as $field) {
                    $value = isset($columnIndexes[$field]) ? ($row[$columnIndexes[$field]] ?? null) : null;
                    $student[$field] = is_scalar($value) ? trim((string) $value) : '';
                    if ($student[$field] === '') {
                        $student[$field] = null;
                    }
                }

                if (empty($student['alamat'])) {
                    $student['alamat'] = 'Bantul';
                }

                $validator = Validator::make($student, [
                    'name' => ['required', 'string', 'max:255'],
                    'nis' => ['nullable', 'string', 'max:30'],
                    'kelas' => ['required', 'string', 'max:255'],
                    'jurusan' => ['required', 'string', Rule::in(['RPL', 'TKJ', 'DKV', 'AKL', 'MP', 'BR', 'BD', 'LPS'])],
                    'alamat' => ['required', 'string', 'max:2000'],
                    'phone' => ['nullable', 'string', 'max:20'],
                    'email' => ['nullable', 'string', 'max:255'],
                ]);

                if ($student['nis'] !== null && isset($seenNis[$student['nis']])) {
                    $validator->errors()->add('nis', 'NIS duplikat di dalam file.');
                }

                if ($validator->fails()) {
                    foreach ($validator->errors()->all() as $error) {
                        $rowErrors[] = "Baris {$lineNumber}: {$error}";
                    }
                    continue;
                }

                if ($student['nis'] !== null) {
                    $seenNis[$student['nis']] = true;
                }
                $studentRows[] = $student;
            }

            if ($studentRows === [] && $rowErrors === []) {
                $rowErrors[] = 'File tidak berisi baris data siswa.';
            }

            if ($rowErrors !== []) {
                throw ValidationException::withMessages(['file' => $rowErrors]);
            }
        } else {
            $determineJurusan = function (string $className): string {
                $upper = strtoupper($className);
                if (str_contains($upper, 'RPL')) return 'RPL';
                if (str_contains($upper, 'TKJ')) return 'TKJ';
                if (str_contains($upper, 'DKV')) return 'DKV';
                if (str_contains($upper, 'AKL') || str_contains($upper, 'AK')) return 'AKL';
                if (str_contains($upper, 'LPS')) return 'LPS';
                if (str_contains($upper, 'MP')) return 'MP';
                if (str_contains($upper, 'BR')) return 'BR';
                if (str_contains($upper, 'BD')) return 'BD';
                return 'RPL';
            };

            foreach ($spreadsheet->getAllSheets() as $sheet) {
                $rows = $sheet->toArray(null, true, true, false);
                $currentClass = null;
                $currentJurusan = null;

                $sheetTitle = trim($sheet->getTitle());
                if (preg_match('/^(X|XI|XII)\b/i', $sheetTitle)) {
                    $candJurusan = $determineJurusan($sheetTitle);
                    if ($candJurusan !== '') {
                        $currentClass = $sheetTitle;
                        $currentJurusan = $candJurusan;
                    }
                }

                foreach ($rows as $row) {
                    $rowValues = array_values(array_filter(array_map('trim', array_map('strval', $row)), fn($v) => $v !== ''));
                    $rowText = implode(' ', $rowValues);

                    if (preg_match('/\bKELAS\s*:\s*([A-Za-z0-9\s\-_]+)/i', $rowText, $matches)
                        || preg_match('/^\s*KELAS\b[\s:]*([A-Za-z0-9\s\-_]+)/i', $rowText, $matches)) {
                        $rawClass = trim($matches[1]);
                        $rawClass = trim($rawClass, " \t\n\r\0\x0B,:.-");
                        $rawClass = preg_replace('/\bTAHUN\b.*$/i', '', $rawClass);
                        $rawClass = trim($rawClass);
                        if ($rawClass !== '') {
                            $currentClass = $rawClass;
                            $currentJurusan = $determineJurusan($currentClass);
                            continue;
                        }
                    }

                    if (! $currentClass || ! $currentJurusan) {
                        continue;
                    }

                    if (preg_match('/\b(DAFTAR HADIR|TAHUN PELAJARAN|MATA PELAJARAN|GURU MATA|JUMLAH SISWA|PERTEMUAN KE|Bantul|Guru|NIP|NOMOR)\b/i', $rowText)) {
                        continue;
                    }

                    $num = isset($row[0]) ? trim((string)$row[0]) : '';
                    $nis = isset($row[1]) ? trim((string)$row[1]) : '';
                    $name = isset($row[2]) ? trim((string)$row[2]) : '';

                    $cleanName = null;
                    $cleanNis = null;

                    if (is_numeric($num) && (int)$num >= 1 && (int)$num <= 100) {
                        $cleanNis = preg_match('/\b(\d{4,12})\b/', $nis, $m) ? $m[1] : null;
                        if ($name !== '' && !is_numeric($name) && !preg_match('/^(L|P|Urt|NIS|NOMOR)$/i', $name)) {
                            $cleanName = trim($name, " \t\n\r\0\x0B*");
                        } elseif ($cleanNis === null && $nis !== '' && !is_numeric($nis) && !preg_match('/^(L|P|Urt|NIS|NOMOR)$/i', $nis)) {
                            $cleanName = trim($nis, " \t\n\r\0\x0B*");
                        } else {
                            for ($c = 2; $c < count($row); $c++) {
                                $val = trim((string)$row[$c]);
                                if (strlen($val) >= 3 && !is_numeric($val) && !preg_match('/^(L|P|S|I|A|Urt|NIS|NOMOR)$/i', $val)) {
                                    $cleanName = trim($val, " \t\n\r\0\x0B*");
                                    break;
                                }
                            }
                        }
                    } elseif (preg_match('/\b(\d{4,12})\b/', $nis, $m)) {
                        $cleanNis = $m[1];
                        if ($name !== '' && !is_numeric($name)) {
                            $cleanName = trim($name, " \t\n\r\0\x0B*");
                        }
                    }

                    if ($cleanName !== null && $cleanName !== '') {
                        $studentRows[] = [
                            'name' => $cleanName,
                            'nis' => $cleanNis,
                            'kelas' => $currentClass,
                            'jurusan' => $currentJurusan,
                            'alamat' => 'Bantul',
                            'phone' => null,
                            'email' => null,
                        ];
                    }
                }
            }

            if ($studentRows === []) {
                throw ValidationException::withMessages([
                    'file' => 'Tidak ditemukan data siswa atau kelas yang valid dalam file Excel. Pastikan format tabel memiliki header KELAS atau menggunakan template standar.',
                ]);
            }
        }

        $defaultPassword = Hash::make('smkn1bantul2026');

        DB::transaction(function () use ($studentRows, $defaultPassword) {
            foreach ($studentRows as $student) {
                // Ensure SchoolClass exists
                SchoolClass::firstOrCreate(
                    ['name' => $student['kelas']],
                    [
                        'grade' => str_starts_with($student['kelas'], 'XII') ? 'XII' : (str_starts_with($student['kelas'], 'XI') ? 'XI' : 'X'),
                        'jurusan' => $student['jurusan'] ?? 'RPL',
                    ]
                );

                $existing = !empty($student['nis']) ? Siswa::where('nis', $student['nis'])->first() : null;

                if ($existing) {
                    $existing->update([
                        'name' => $student['name'],
                        'kelas' => $student['kelas'],
                        'jurusan' => $student['jurusan'],
                        'alamat' => $student['alamat'],
                        'phone' => $student['phone'] ?? $existing->phone,
                        'email' => $student['email'] ?? $existing->email,
                    ]);
                    $siswaId = $existing->id;
                } else {
                    $newSiswa = Siswa::create([
                        'name' => $student['name'],
                        'nis' => $student['nis'],
                        'kelas' => $student['kelas'],
                        'jurusan' => $student['jurusan'],
                        'alamat' => $student['alamat'],
                        'phone' => $student['phone'],
                        'email' => $student['email'],
                        'gender' => 'L',
                        'angkatan' => (int) date('Y'),
                        'status' => 'aktif',
                    ]);
                    $siswaId = $newSiswa->id;
                }

                // If NIS exists, ensure User accounts exist
                if (!empty($student['nis'])) {
                    User::updateOrCreate(
                        ['username' => $student['nis']],
                        [
                            'role_id' => 3,
                            'student_id' => $siswaId,
                            'fullname' => $student['name'],
                            'email' => $student['email'],
                            'phone' => $student['phone'],
                            'password' => $defaultPassword,
                            'is_active' => true,
                        ]
                    );

                    User::updateOrCreate(
                        ['username' => 'ortu_' . $student['nis']],
                        [
                            'role_id' => 6,
                            'student_id' => $siswaId,
                            'fullname' => 'Orang Tua ' . $student['name'],
                            'password' => $defaultPassword,
                            'is_active' => true,
                        ]
                    );
                }
            }
        });

        return redirect()->route('waka.siswa.index')
            ->with('success', count($studentRows) . ' data siswa berhasil diimpor dari Excel.');
    }

    /**
     * Hapus seluruh siswa per kelas dengan opsi simpan siswa tertentu & force delete
     */
    public function hapusPerKelas(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kelas' => ['required', 'string', 'max:255'],
            'exclude_ids' => ['sometimes', 'nullable', 'array'],
            'exclude_ids.*' => ['integer', 'exists:siswas,id'],
            'force' => ['sometimes', 'boolean'],
        ]);

        $kelas = trim($validated['kelas']);
        $force = (bool) ($request->input('force', 0));
        $excludeIds = collect($request->input('exclude_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->all();

        $isAll = strtoupper($kelas) === 'ALL';
        $allStudents = $isAll ? Siswa::all(['id', 'name']) : Siswa::where('kelas', $kelas)->get(['id', 'name']);
        if ($allStudents->isEmpty()) {
            $targetLabel = $isAll ? "seluruh kelas di sistem" : "kelas '{$kelas}'";
            return back()->withErrors(['kelas' => "Tidak ditemukan siswa pada {$targetLabel}."]);
        }

        $studentsToDelete = $allStudents->reject(fn ($s) => in_array($s->id, $excludeIds, true));
        $studentsToKeep = $allStudents->filter(fn ($s) => in_array($s->id, $excludeIds, true));

        if ($studentsToDelete->isEmpty()) {
            $targetLabel = $isAll ? "seluruh kelas" : "kelas '{$kelas}'";
            return back()->withErrors(['kelas' => "Seluruh siswa di {$targetLabel} dicentang untuk dipertahankan. Tidak ada siswa yang dihapus."]);
        }

        $studentIdsToDelete = $studentsToDelete->pluck('id')->all();
        $deleteCount = count($studentIdsToDelete);
        $keepCount = $studentsToKeep->count();

        $linkedUserIds = User::whereIn('student_id', $studentIdsToDelete)->pluck('id');
        $linkedAttendanceCount = Attendance::whereIn('student_id', $studentIdsToDelete)->count();
        $linkedLeaveCount = LeaveRequest::whereIn('student_id', $studentIdsToDelete)->count();

        $hasDependencies = $linkedUserIds->isNotEmpty() || $linkedAttendanceCount > 0 || $linkedLeaveCount > 0;

        if ($hasDependencies && ! $force) {
            $depDetails = [];
            if ($linkedUserIds->isNotEmpty()) {
                $depDetails[] = $linkedUserIds->count() . ' akun siswa';
            }
            if ($linkedAttendanceCount > 0) {
                $depDetails[] = $linkedAttendanceCount . ' data presensi';
            }
            if ($linkedLeaveCount > 0) {
                $depDetails[] = $linkedLeaveCount . ' pengajuan izin';
            }
            $detailsStr = implode(', ', $depDetails);

            $targetLabel = $isAll ? "seluruh kelas" : "kelas {$kelas}";
            return back()->withErrors([
                'kelas' => "Sebagian siswa yang akan dihapus pada {$targetLabel} masih memiliki keterkaitan data ({$detailsStr}). Silakan centang 'Hapus beserta data terkait' jika ingin menghapus seluruhnya."
            ])->withInput();
        }

        DB::transaction(function () use ($studentIdsToDelete, $linkedUserIds) {
            if ($linkedUserIds->isNotEmpty()) {
                User::whereIn('id', $linkedUserIds)->delete();
            }
            LeaveRequest::whereIn('student_id', $studentIdsToDelete)->delete();
            Attendance::whereIn('student_id', $studentIdsToDelete)->delete();
            Siswa::whereIn('id', $studentIdsToDelete)->delete();
        });

        $targetName = $isAll ? "seluruh kelas (semua siswa)" : "kelas {$kelas}";
        $message = "Semua data siswa pada {$targetName} ({$deleteCount} siswa) berhasil dihapus.";
        if ($keepCount > 0) {
            $message = "Sebanyak {$deleteCount} data siswa pada {$targetName} berhasil dihapus. ({$keepCount} siswa yang tidak lulus/naik kelas dipertahankan).";
        }
        if ($hasDependencies && $force) {
            $message .= " Data akun, presensi, dan izin untuk siswa yang dihapus telah dibersihkan.";
        }

        return redirect()->route('waka.siswa.index')->with('success', $message);
    }
}

