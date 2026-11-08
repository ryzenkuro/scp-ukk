<?php

namespace App\Http\Controllers\Waka;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\SystemConfiguration;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class JadwalController extends Controller
{
    /**
     * Tampilkan kalender dan tabel jadwal sekolah terpadu
     */
    public function index(Request $request): Response
    {
        $config = SystemConfiguration::current();

        $selectedCycle = $request->input('cycle', (string) $config->active_cycle);
        $selectedDay = $request->input('day', 'all');
        $selectedClass = $request->input('class_id', 'all');
        $selectedTeacher = $request->input('teacher_id', 'all');

        $query = Schedule::with(['teacher', 'schoolClass', 'room', 'startSlot', 'endSlot'])
            ->orderBy('day_of_week')
            ->orderBy('start_time');

        if ($selectedCycle !== 'all') {
            $query->where('cycle_number', (int) $selectedCycle);
        }

        if ($selectedDay !== 'all') {
            $query->where('day_of_week', $selectedDay);
        }

        if ($selectedClass !== 'all') {
            $query->where('class_id', (int) $selectedClass);
        }

        if ($selectedTeacher !== 'all') {
            $query->where('teacher_id', (int) $selectedTeacher);
        }

        $schedules = $query->get();

        $classes = SchoolClass::orderBy('name')->get();
        $rooms = Room::orderBy('name')->get();
        $teachers = User::whereIn('role_id', [2, 5])
            ->where('is_active', true)
            ->orderBy('fullname')
            ->get(['id', 'fullname', 'username']);

        $timeSlots = TimeSlot::orderBy('order')->get();

        return Inertia::render('Waka/Jadwal/Index', [
            'schedules' => $schedules,
            'filters' => [
                'cycle' => $selectedCycle,
                'day' => $selectedDay,
                'class_id' => $selectedClass,
                'teacher_id' => $selectedTeacher,
            ],
            'classes' => $classes,
            'rooms' => $rooms,
            'teachers' => $teachers,
            'timeSlots' => $timeSlots,
            'config' => [
                'active_cycle' => $config->active_cycle,
                'is_scheduled_locked' => $config->is_scheduled_locked,
                'academic_year' => $config->academic_year,
            ],
        ]);
    }

    /**
     * Simpan jadwal baru dengan validasi kunci proteksi & anti-bentrok 3 arah
     */
    public function store(Request $request): RedirectResponse
    {
        // 1. Proteksi Kunci Putaran (Revisi Fitur Waka #1)
        $config = SystemConfiguration::current();
        if ($config->is_scheduled_locked) {
            return back()->with('error', 'Operasi ditolak: Penambahan jadwal tidak dapat dilakukan karena status jadwal sedang Terkunci oleh Waka Kurikulum.');
        }

        $validated = $request->validate([
            'day_of_week' => ['required', 'string', 'in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu'],
            'cycle_number' => ['required', 'integer', 'in:1,2'],
            'subject' => ['required', 'string', 'max:100'],
            'teacher_id' => ['required', 'exists:users,id'],
            'class_id' => ['required', 'exists:school_classes,id'],
            'room_id' => ['required', 'exists:rooms,id'],
            'start_slot_id' => ['required', 'exists:time_slots,id'],
            'end_slot_id' => ['required', 'exists:time_slots,id'],
        ]);

        $startSlot = TimeSlot::findOrFail($validated['start_slot_id']);
        $endSlot = TimeSlot::findOrFail($validated['end_slot_id']);

        if ($startSlot->order > $endSlot->order) {
            return back()->with('error', 'Jam selesai pembelajaran tidak boleh mendahului jam mulai.');
        }

        $startTime = $startSlot->start_time;
        $endTime = $endSlot->end_time;

        // 2. Validasi Anti-Bentrok 3 Arah
        $conflictError = $this->checkConflicts(
            day: $validated['day_of_week'],
            cycle: (int) $validated['cycle_number'],
            teacherId: (int) $validated['teacher_id'],
            classId: (int) $validated['class_id'],
            roomId: (int) $validated['room_id'],
            startTime: $startTime,
            endTime: $endTime
        );

        if ($conflictError) {
            return back()->with('error', $conflictError);
        }

        $schoolClass = SchoolClass::find($validated['class_id']);
        $room = Room::find($validated['room_id']);

        Schedule::create([
            'day_of_week' => $validated['day_of_week'],
            'cycle_number' => (int) $validated['cycle_number'],
            'subject' => $validated['subject'],
            'teacher_id' => (int) $validated['teacher_id'],
            'class_id' => (int) $validated['class_id'],
            'class_name' => $schoolClass ? $schoolClass->name : 'Kelas',
            'room_id' => (int) $validated['room_id'],
            'room_name' => $room ? $room->name : 'Ruangan',
            'start_time' => $startTime,
            'end_time' => $endTime,
            'start_slot_id' => $startSlot->id,
            'end_slot_id' => $endSlot->id,
            'academic_year' => $config->academic_year ?? '2026/2027',
        ]);

        return back()->with('success', "Jadwal {$validated['subject']} untuk kelas {$schoolClass->name} berhasil ditambahkan.");
    }

    /**
     * Perbarui jadwal pembelajaran
     */
    public function update(Request $request, Schedule $jadwal): RedirectResponse
    {
        // 1. Proteksi Kunci Putaran (Revisi Fitur Waka #1)
        $config = SystemConfiguration::current();
        if ($config->is_scheduled_locked) {
            return back()->with('error', 'Operasi ditolak: Perubahan jadwal tidak dapat dilakukan karena status jadwal sedang Terkunci oleh Waka Kurikulum.');
        }

        $validated = $request->validate([
            'day_of_week' => ['required', 'string', 'in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu'],
            'cycle_number' => ['required', 'integer', 'in:1,2'],
            'subject' => ['required', 'string', 'max:100'],
            'teacher_id' => ['required', 'exists:users,id'],
            'class_id' => ['required', 'exists:school_classes,id'],
            'room_id' => ['required', 'exists:rooms,id'],
            'start_slot_id' => ['required', 'exists:time_slots,id'],
            'end_slot_id' => ['required', 'exists:time_slots,id'],
        ]);

        $startSlot = TimeSlot::findOrFail($validated['start_slot_id']);
        $endSlot = TimeSlot::findOrFail($validated['end_slot_id']);

        if ($startSlot->order > $endSlot->order) {
            return back()->with('error', 'Jam selesai pembelajaran tidak boleh mendahului jam mulai.');
        }

        $startTime = $startSlot->start_time;
        $endTime = $endSlot->end_time;

        // 2. Validasi Anti-Bentrok 3 Arah (Kecualikan ID jadwal saat ini)
        $conflictError = $this->checkConflicts(
            day: $validated['day_of_week'],
            cycle: (int) $validated['cycle_number'],
            teacherId: (int) $validated['teacher_id'],
            classId: (int) $validated['class_id'],
            roomId: (int) $validated['room_id'],
            startTime: $startTime,
            endTime: $endTime,
            ignoreScheduleId: $jadwal->id
        );

        if ($conflictError) {
            return back()->with('error', $conflictError);
        }

        $schoolClass = SchoolClass::find($validated['class_id']);
        $room = Room::find($validated['room_id']);

        $jadwal->update([
            'day_of_week' => $validated['day_of_week'],
            'cycle_number' => (int) $validated['cycle_number'],
            'subject' => $validated['subject'],
            'teacher_id' => (int) $validated['teacher_id'],
            'class_id' => (int) $validated['class_id'],
            'class_name' => $schoolClass ? $schoolClass->name : $jadwal->class_name,
            'room_id' => (int) $validated['room_id'],
            'room_name' => $room ? $room->name : $jadwal->room_name,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'start_slot_id' => $startSlot->id,
            'end_slot_id' => $endSlot->id,
        ]);

        return back()->with('success', "Jadwal {$jadwal->subject} berhasil diperbarui.");
    }

    /**
     * Hapus jadwal pembelajaran
     */
    public function destroy(Schedule $jadwal): RedirectResponse
    {
        // Proteksi Kunci Putaran (Revisi Fitur Waka #1)
        $config = SystemConfiguration::current();
        if ($config->is_scheduled_locked) {
            return back()->with('error', 'Operasi ditolak: Penghapusan jadwal tidak dapat dilakukan karena status jadwal sedang Terkunci oleh Waka Kurikulum.');
        }

        $subject = $jadwal->subject;
        $jadwal->delete();

        return back()->with('success', "Jadwal {$subject} berhasil dihapus.");
    }

    /**
     * Reset seluruh jadwal sekolah secara keseluruhan dari 0 untuk persiapan angkatan baru
     */
    public function resetAll(): RedirectResponse
    {
        $config = SystemConfiguration::current();
        if ($config->is_scheduled_locked) {
            return back()->with('error', 'Operasi ditolak: Penghapusan jadwal tidak dapat dilakukan karena status jadwal sedang Terkunci oleh Waka Kurikulum. Silakan buka kunci jadwal terlebih dahulu.');
        }

        $count = Schedule::count();
        if ($count === 0) {
            return back()->with('info', 'Tidak ada data jadwal yang perlu di-reset.');
        }

        DB::transaction(function () {
            Schedule::query()->delete();
        });

        return back()->with('success', "Seluruh data jadwal pelajaran ({$count} jadwal) berhasil di-reset secara keseluruhan dari 0. Silakan impor berkas jadwal baru untuk angkatan berikutnya.");
    }

    /**
     * Pengecekan Bentrok 3 Arah: Guru, Ruangan, dan Kelas
     */
    private function checkConflicts(
        string $day,
        int $cycle,
        int $teacherId,
        int $classId,
        int $roomId,
        string $startTime,
        string $endTime,
        ?int $ignoreScheduleId = null
    ): ?string {
        // Rentang waktu beririsan: start_time < $endTime DAN end_time > $startTime
        $baseQuery = fn () => Schedule::where('day_of_week', $day)
            ->where('cycle_number', $cycle)
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime)
            ->when($ignoreScheduleId, fn ($q) => $q->where('id', '!=', $ignoreScheduleId));

        // 1. Bentrok Guru (Guru tidak boleh mengajar 2 tempat bersamaan)
        $teacherConflict = $baseQuery()->where('teacher_id', $teacherId)->first();
        if ($teacherConflict) {
            $teacher = User::find($teacherId);
            return "Bentrok Tenaga Pendidik: {$teacher->fullname} telah memiliki jadwal mengajar {$teacherConflict->subject} di {$teacherConflict->class_name} pada rentang waktu tersebut.";
        }

        // 2. Bentrok Ruangan (Ruang tidak boleh dipakai 2 kelas bersamaan)
        $roomConflict = $baseQuery()->where('room_id', $roomId)->first();
        if ($roomConflict) {
            $room = Room::find($roomId);
            return "Bentrok Ruangan: {$room->name} sedang digunakan untuk kelas {$roomConflict->class_name} ({$roomConflict->subject}) pada rentang waktu tersebut.";
        }

        // 3. Bentrok Kelas (Satu kelas tidak boleh menerima 2 mapel bersamaan)
        $classConflict = $baseQuery()->where('class_id', $classId)->first();
        if ($classConflict) {
            $schoolClass = SchoolClass::find($classId);
            return "Bentrok Kelas: {$schoolClass->name} sudah memiliki jadwal pelajaran {$classConflict->subject} pada rentang waktu tersebut.";
        }

        return null;
    }

    /**
     * Cetak jadwal pelajaran sekolah siap cetak / PDF (A4 Landscape)
     */
    public function exportPdf(Request $request)
    {
        $config = SystemConfiguration::current();
        $activeCycle = $request->input('cycle', (string) $config->active_cycle);
        $filterClass = $request->input('class');

        $query = Schedule::with(['teacher', 'schoolClass', 'room', 'startSlot', 'endSlot'])
            ->orderBy('day_of_week')
            ->orderBy('start_time');

        if ($activeCycle && $activeCycle !== 'all') {
            $query->where('cycle_number', (int) $activeCycle);
        }

        if ($filterClass && $filterClass !== 'all') {
            $query->where('class_name', $filterClass);
        }

        $jadwals = $query->get();

        return view('pdf.jadwal', [
            'documentTitle' => 'JADWAL PELAJARAN SEKOLAH',
            'pageTitle' => 'Jadwal Pelajaran Sekolah',
            'academicYear' => $config->academic_year,
            'activeCycle' => (int) $activeCycle,
            'filterClass' => $filterClass !== 'all' ? $filterClass : null,
            'jadwals' => $jadwals,
        ]);
    }

    /**
     * Download template import jadwal Excel
     */
    public function template(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            'Hari',
            'Guru',
            'Mata Pelajaran',
            'Kelas',
            'Ruangan',
            'Sesi Mulai',
            'Sesi Selesai',
            'Putaran',
            'Jenis',
        ], null, 'A1');
        foreach (range('A', 'I') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return response()->streamDownload(
            fn () => (new Xlsx($spreadsheet))->save('php://output'),
            'template_import_jadwal.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    /**
     * Download contoh file import jadwal 5 data
     */
    public function contoh(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            'Hari',
            'Guru',
            'Mata Pelajaran',
            'Kelas',
            'Ruangan',
            'Sesi Mulai',
            'Sesi Selesai',
            'Putaran',
            'Jenis',
        ], null, 'A1');
        $sheet->fromArray([
            ['Senin', 'ARIS PURWANTINAH, S.Pd', 'KK BR (Bisnis Retail)', 'XII BR 1', 'Lab 6', 'Jam ke-7', 'Jam ke-8', '2', 'Jurusan'],
            ['Selasa', 'SUNARSIH S.Pd', 'Projek IPAS', 'X TKJ 1', 'Lab IPA', 'Jam ke-4', 'Jam ke-4', '2', 'Normada'],
            ['Rabu', 'SRI SUNARTINI, S.Pd', 'KK BR (Bisnis Retail)', 'XII BR 1', 'Lab 8', 'Jam ke-7', 'Jam ke-8', '2', 'Jurusan'],
            ['Kamis', 'AMAT SUKARDI, S.Pd', 'Bahasa Inggris', 'XII LPS', 'OR1', 'Jam ke-1', 'Jam ke-3', '2', 'Normada'],
            ['Jumat', 'MUHAMMAD HERU PURNOMO, M.S.I', 'Bahasa Indonesia', 'XII RPL 2', '', 'Jam ke-1', 'Jam ke-3', '2', 'Normada'],
        ], null, 'A2');
        foreach (range('A', 'I') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return response()->streamDownload(
            fn () => (new Xlsx($spreadsheet))->save('php://output'),
            'contoh_import_jadwal_5_data.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    /**
     * Import jadwal pelajaran dari Excel / CSV
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:20480'],
            'cycle_number' => ['nullable', 'in:1,2,auto'],
            'replace_existing' => ['sometimes', 'boolean'],
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, ['xlsx', 'xls', 'csv'], true)) {
            throw ValidationException::withMessages([
                'file' => 'Format file tidak didukung. Harap unggah file .xlsx, .xls, atau .csv.',
            ]);
        }

        $config = SystemConfiguration::current();
        $activeCycle = (int) ($config->active_cycle ?? 1);

        $cycleInput = $request->input('cycle_number', 'auto');
        if ($cycleInput === '1' || $cycleInput === '2') {
            $targetCycle = (int) $cycleInput;
        } else {
            if (preg_match('/(put[_\-\s]*2|putaran[_\-\s]*2)/i', $originalName)) {
                $targetCycle = 2;
            } elseif (preg_match('/(put[_\-\s]*1|putaran[_\-\s]*1)/i', $originalName)) {
                $targetCycle = 1;
            } else {
                $targetCycle = $activeCycle;
            }
        }

        if ($config->is_scheduled_locked) {
            throw ValidationException::withMessages([
                'file' => "Jadwal sedang dikunci. Buka kunci jadwal sebelum mengimpor jadwal.",
            ]);
        }

        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheetRows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'file' => 'Gagal membaca file Excel/CSV: ' . $e->getMessage(),
            ]);
        }

        if (empty($sheetRows)) {
            throw ValidationException::withMessages([
                'file' => 'File Excel/CSV kosong.',
            ]);
        }

        $headerAliases = [
            'hari' => 'day',
            'day' => 'day',
            'guru' => 'teacher',
            'nama guru' => 'teacher',
            'teacher' => 'teacher',
            'pengampu' => 'teacher',
            'nama' => 'teacher',
            'mata pelajaran' => 'subject',
            'mapel' => 'subject',
            'subject' => 'subject',
            'pelajaran' => 'subject',
            'kelas' => 'class',
            'class' => 'class',
            'ruangan' => 'room',
            'ruang' => 'room',
            'room' => 'room',
            'lab' => 'room',
            'sesi mulai' => 'start_slot',
            'jam mulai' => 'start_slot',
            'mulai' => 'start_slot',
            'start' => 'start_slot',
            'sesi selesai' => 'end_slot',
            'jam selesai' => 'end_slot',
            'selesai' => 'end_slot',
            'end' => 'end_slot',
            'putaran' => 'cycle_number',
            'cycle' => 'cycle_number',
        ];

        $colIdx = [];
        $dataRows = [];
        foreach ($sheetRows as $idx => $row) {
            $matched = [];
            foreach ($row as $ci => $val) {
                $norm = mb_strtolower(trim((string) $val));
                $norm = ltrim($norm, "\xEF\xBB\xBF");
                if (isset($headerAliases[$norm])) {
                    $matched[$headerAliases[$norm]] = $ci;
                }
            }
            if (isset($matched['day']) && isset($matched['teacher'])) {
                $colIdx = $matched;
                $dataRows = array_slice($sheetRows, $idx + 1);
                break;
            }
        }

        if (empty($colIdx)) {
            $defaultOrder = ['day', 'teacher', 'subject', 'class', 'room', 'start_slot', 'end_slot', 'cycle_number'];
            foreach ($defaultOrder as $k => $f) {
                $colIdx[$f] = $k;
            }
            $dataRows = array_slice($sheetRows, 1);
        }

        $teachers = User::whereIn('role_id', [2, 5])->get();
        $timeSlots = TimeSlot::orderBy('order')->get();
        $importedCount = 0;

        $findSlot = function ($rawStr) use ($timeSlots) {
            $rawStr = trim((string) $rawStr);
            if ($rawStr === '') return $timeSlots->first();

            if (preg_match('/\b(\d{1,2})\b/', $rawStr, $m)) {
                $num = (int) $m[1];
                $matched = $timeSlots->first(function ($s) use ($num) {
                    return preg_match('/\bJam\s*(?:Ke-?)?\s*' . $num . '\b/i', $s->name);
                });
                if ($matched) return $matched;
            }

            $matched = $timeSlots->first(function ($s) use ($rawStr) {
                return strcasecmp($s->name, $rawStr) === 0;
            });
            if ($matched) return $matched;

            if (isset($num)) {
                $matched = $timeSlots->firstWhere('order', $num);
                if ($matched) return $matched;
            }

            return $timeSlots->first();
        };

        DB::transaction(function () use ($dataRows, $colIdx, $targetCycle, $teachers, $timeSlots, $config, $findSlot, &$importedCount) {
            foreach ($dataRows as $row) {
                $day = trim((string) ($row[$colIdx['day']] ?? ''));
                $teacherName = trim((string) ($row[$colIdx['teacher']] ?? ''));
                $subject = trim((string) ($row[$colIdx['subject']] ?? 'Pelajaran'));
                $className = trim((string) ($row[$colIdx['class']] ?? ''));
                $roomName = trim((string) ($row[$colIdx['room']] ?? ''));
                $startSlotRaw = trim((string) ($row[$colIdx['start_slot']] ?? ''));
                $endSlotRaw = trim((string) ($row[$colIdx['end_slot']] ?? ''));
                $cycleVal = trim((string) ($row[$colIdx['cycle_number']] ?? ''));

                if ($day === '' || $teacherName === '') {
                    continue;
                }

                $cycle = is_numeric($cycleVal) ? (int) $cycleVal : $targetCycle;

                // Match or create teacher
                $teacher = $teachers->first(function ($t) use ($teacherName) {
                    return strcasecmp($t->fullname, $teacherName) === 0
                        || strcasecmp($t->username, $teacherName) === 0
                        || (isset($t->nip) && strcasecmp($t->nip, $teacherName) === 0);
                });

                if (!$teacher) {
                    $teacher = User::create([
                        'role_id' => 2,
                        'username' => Str::slug($teacherName, '.') . rand(100, 999),
                        'fullname' => $teacherName,
                        'password' => Hash::make('smkn1bantul2026'),
                        'is_active' => true,
                    ]);
                }

                // Match or create SchoolClass
                $schoolClass = null;
                if ($className !== '') {
                    $schoolClass = SchoolClass::firstOrCreate(
                        ['name' => $className],
                        [
                            'grade' => str_starts_with($className, 'XII') ? 'XII' : (str_starts_with($className, 'XI') ? 'XI' : 'X'),
                            'jurusan' => 'RPL',
                        ]
                    );
                }

                // Match or create Room
                $room = null;
                if ($roomName !== '') {
                    $room = Room::firstOrCreate(['name' => $roomName]);
                }

                // Match time slots secara presisi
                $startSlot = $findSlot($startSlotRaw);
                $endSlot = $findSlot($endSlotRaw) ?? $startSlot;

                $startTime = $startSlot?->start_time ?? '07:00:00';
                $endTime = $endSlot?->end_time ?? '08:30:00';

                Schedule::create([
                    'day_of_week' => ucfirst(strtolower($day)),
                    'cycle_number' => $cycle,
                    'subject' => $subject,
                    'teacher_id' => $teacher->id,
                    'class_name' => $schoolClass?->name ?? $className,
                    'class_id' => $schoolClass?->id,
                    'room_name' => $room?->name ?? ($roomName ?: null),
                    'room_id' => $room?->id,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'start_slot_id' => $startSlot?->id,
                    'end_slot_id' => $endSlot?->id,
                    'academic_year' => $config->academic_year,
                ]);

                $importedCount++;
            }
        });

        return redirect()->route('waka.jadwal.index')
            ->with('success', "{$importedCount} data jadwal berhasil diimpor dari Excel.");
    }
}

