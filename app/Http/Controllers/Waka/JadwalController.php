<?php

namespace App\Http\Controllers\Waka;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\SystemConfiguration;
use App\Models\TimeSlot;
use App\Models\User;
use App\Services\MasterDataService;
use App\Services\SchedulePdfParserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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
                'ceremony_cycle_1' => (bool) $config->ceremony_cycle_1,
                'ceremony_cycle_2' => (bool) $config->ceremony_cycle_2,
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

        // Proteksi Integritas Relasi: Jadwal yang sudah memiliki riwayat Jurnal KBM tidak boleh dihapus
        $journalCount = Journal::where('schedule_id', $jadwal->id)->count();
        if ($journalCount > 0) {
            return back()->with('error', "Jadwal {$jadwal->subject} ({$jadwal->class_name}) tidak dapat dihapus karena sudah memiliki {$journalCount} catatan Jurnal KBM dan checklist presensi siswa. Jadwal ini menjadi bukti histori pembelajaran sekolah.");
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
        $hasCeremony = ((int) $activeCycle === 2) ? (bool) $config->ceremony_cycle_2 : (bool) $config->ceremony_cycle_1;

        return view('pdf.jadwal', [
            'documentTitle' => 'JADWAL PELAJARAN SEKOLAH',
            'pageTitle' => 'Jadwal Pelajaran Sekolah',
            'academicYear' => $config->academic_year,
            'activeCycle' => (int) $activeCycle,
            'filterClass' => $filterClass !== 'all' ? $filterClass : null,
            'jadwals' => $jadwals,
            'hasCeremony' => $hasCeremony,
        ]);
    }

    /**
     * Download template import jadwal Excel
     */
    public function template(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
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
        $spreadsheet = new Spreadsheet;
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
     * Download master data jadwal KBM dalam format Markdown (.md)
     */
    public function downloadMaster(Request $request, MasterDataService $masterDataService): StreamedResponse
    {
        $cycle = (int) $request->query('cycle', 1);
        $type = $cycle === 2 ? 'jadwal_2' : 'jadwal_1';
        $content = $masterDataService->getMasterMarkdownContent($type);

        return response()->streamDownload(
            function () use ($content) {
                echo $content;
            },
            "master_jadwal_putaran_{$cycle}.md",
            ['Content-Type' => 'text/markdown; charset=UTF-8']
        );
    }

    /**
     * Import jadwal pelajaran dari Excel / CSV / PDF / Markdown (Mendukung Multi-Upload hingga 4 Berkas)
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['nullable', 'file', 'max:20480'],
            'files' => ['nullable', 'array'],
            'files.*' => ['file', 'max:20480'],
            'cycle_number' => ['nullable', 'in:1,2,auto'],
            'replace_existing' => ['sometimes', 'boolean'],
        ]);

        $uploadedFiles = $request->file('files') ?: [];
        if ($request->hasFile('file')) {
            $uploadedFiles[] = $request->file('file');
        }

        if (empty($uploadedFiles)) {
            throw ValidationException::withMessages([
                'file' => 'Harap pilih minimal satu berkas jadwal.',
            ]);
        }

        $config = SystemConfiguration::current();
        if ($config->is_scheduled_locked) {
            throw ValidationException::withMessages([
                'file' => 'Jadwal sedang dikunci. Buka kunci jadwal sebelum mengimpor jadwal.',
            ]);
        }

        $activeCycle = (int) ($config->active_cycle ?? 1);
        $cycleInput = $request->input('cycle_number', 'auto');
        $defaultTargetCycle = in_array($cycleInput, ['1', '2'], true) ? (int) $cycleInput : $activeCycle;

        $timeSlots = TimeSlot::orderBy('order')->get();
        $pdfParser = new SchedulePdfParserService;
        $masterDataService = new MasterDataService;

        // 1. Klasifikasi Berkas yang Diunggah
        $classified = [
            'kelas_1' => null,
            'kelas_2' => null,
            'ruang_1' => null,
            'ruang_2' => null,
            'markdown' => [],
            'spreadsheet' => [],
            'other_pdf' => [],
        ];

        foreach ($uploadedFiles as $f) {
            $name = strtolower($f->getClientOriginalName());
            $ext = strtolower($f->getClientOriginalExtension());

            if ($ext === 'md') {
                $classified['markdown'][] = $f;
            } elseif (in_array($ext, ['xlsx', 'xls', 'csv'], true)) {
                $classified['spreadsheet'][] = $f;
            } elseif ($ext === 'pdf') {
                $isRuang = str_contains($name, 'ruang') || str_contains($name, 'ruangan');
                $isPut2 = str_contains($name, 'put_2') || str_contains($name, 'put 2') || str_contains($name, 'putaran 2') || str_contains($name, 'putaran_2') || str_contains($name, 'sem ganjil 26_27');
                $isPut1 = str_contains($name, 'put_1') || str_contains($name, 'put 1') || str_contains($name, 'putaran 1') || str_contains($name, 'putaran_1') || str_contains($name, 'rev_sept');

                if ($isRuang) {
                    if ($isPut2) {
                        $classified['ruang_2'] = $f;
                    } elseif ($isPut1) {
                        $classified['ruang_1'] = $f;
                    } else {
                        $classified['other_pdf'][] = ['file' => $f, 'is_ruang' => true];
                    }
                } else {
                    if ($isPut2) {
                        $classified['kelas_2'] = $f;
                    } elseif ($isPut1) {
                        $classified['kelas_1'] = $f;
                    } else {
                        $classified['other_pdf'][] = ['file' => $f, 'is_ruang' => false];
                    }
                }
            }
        }

        // 2. Cabang Eksekusi PDF (Jadwal Kelas & Jadwal Ruangan)
        $hasPdf = $classified['kelas_1'] || $classified['kelas_2'] || $classified['ruang_1'] || $classified['ruang_2'] || ! empty($classified['other_pdf']);

        if ($hasPdf) {
            $schedulesToSave = [];
            $cyclesAffected = [];

            // A. Proses Berkas Jadwal Kelas Putaran 1
            if ($classified['kelas_1']) {
                $s1 = $pdfParser->parse($classified['kelas_1']->getRealPath(), 1);
                $cyclesAffected[1] = true;
                if ($classified['ruang_1']) {
                    $r1Map = $pdfParser->parseRoomPdf($classified['ruang_1']->getRealPath(), 1);
                    $pdfParser->mergeSchedulesWithRooms($s1, $r1Map);
                } else {
                    $defaultRuangPath = base_path('tests/JADWAL_RUANG_PUT 1_REVISI_SEPT.pdf');
                    if (file_exists($defaultRuangPath)) {
                        $r1Map = $pdfParser->parseRoomPdf($defaultRuangPath, 1);
                        $pdfParser->mergeSchedulesWithRooms($s1, $r1Map);
                    }
                }
                $schedulesToSave = array_merge($schedulesToSave, $s1);
            } elseif ($classified['ruang_1']) {
                // Hanya berkas Ruang 1 yang diunggah: perbarui ruangan jadwal putaran 1 di DB
                $r1Map = $pdfParser->parseRoomPdf($classified['ruang_1']->getRealPath(), 1);
                $existingP1 = Schedule::where('cycle_number', 1)->get();
                $updatedCount = 0;
                foreach ($existingP1 as $sch) {
                    $day = $sch->day_of_week;
                    $normClass = preg_replace('/[^a-z0-9]/', '', strtolower($sch->class_name));
                    if (isset($r1Map[$day][$normClass])) {
                        foreach ($r1Map[$day][$normClass] as $slot) {
                            if ($slot['start'] < $sch->end_time && $slot['end'] > $sch->start_time) {
                                $sch->room_name = $slot['room'];
                                $roomObj = Room::firstOrCreate(['name' => $slot['room']]);
                                $sch->room_id = $roomObj->id;
                                $sch->save();
                                $updatedCount++;
                                break;
                            }
                        }
                    }
                }
                $cyclesAffected[1] = true;
            }

            // B. Proses Berkas Jadwal Kelas Putaran 2
            if ($classified['kelas_2']) {
                $s2 = $pdfParser->parse($classified['kelas_2']->getRealPath(), 2);
                $cyclesAffected[2] = true;
                if ($classified['ruang_2']) {
                    $r2Map = $pdfParser->parseRoomPdf($classified['ruang_2']->getRealPath(), 2);
                    $pdfParser->mergeSchedulesWithRooms($s2, $r2Map);
                } else {
                    $defaultRuangPath2 = base_path('tests/JADWAL RUANGAN PUT 2 GANJIL 26_27 OK.pdf');
                    if (file_exists($defaultRuangPath2)) {
                        $r2Map = $pdfParser->parseRoomPdf($defaultRuangPath2, 2);
                        $pdfParser->mergeSchedulesWithRooms($s2, $r2Map);
                    }
                }
                $schedulesToSave = array_merge($schedulesToSave, $s2);
            } elseif ($classified['ruang_2']) {
                // Hanya berkas Ruang 2 yang diunggah: perbarui ruangan jadwal putaran 2 di DB
                $r2Map = $pdfParser->parseRoomPdf($classified['ruang_2']->getRealPath(), 2);
                $existingP2 = Schedule::where('cycle_number', 2)->get();
                $updatedCount = 0;
                foreach ($existingP2 as $sch) {
                    $day = $sch->day_of_week;
                    $normClass = preg_replace('/[^a-z0-9]/', '', strtolower($sch->class_name));
                    if (isset($r2Map[$day][$normClass])) {
                        foreach ($r2Map[$day][$normClass] as $slot) {
                            if ($slot['start'] < $sch->end_time && $slot['end'] > $sch->start_time) {
                                $sch->room_name = $slot['room'];
                                $roomObj = Room::firstOrCreate(['name' => $slot['room']]);
                                $sch->room_id = $roomObj->id;
                                $sch->save();
                                $updatedCount++;
                                break;
                            }
                        }
                    }
                }
                $cyclesAffected[2] = true;
            }

            // C. Fallback untuk berkas PDF tunggal tanpa nama putaran spesifik
            foreach ($classified['other_pdf'] as $op) {
                $fObj = $op['file'];
                if ($op['is_ruang']) {
                    $rMap = $pdfParser->parseRoomPdf($fObj->getRealPath(), $defaultTargetCycle);
                    $existing = Schedule::where('cycle_number', $defaultTargetCycle)->get();
                    foreach ($existing as $sch) {
                        $day = $sch->day_of_week;
                        $normClass = preg_replace('/[^a-z0-9]/', '', strtolower($sch->class_name));
                        if (isset($rMap[$day][$normClass])) {
                            foreach ($rMap[$day][$normClass] as $slot) {
                                if ($slot['start'] < $sch->end_time && $slot['end'] > $sch->start_time) {
                                    $sch->room_name = $slot['room'];
                                    $roomObj = Room::firstOrCreate(['name' => $slot['room']]);
                                    $sch->room_id = $roomObj->id;
                                    $sch->save();
                                    break;
                                }
                            }
                        }
                    }
                    $cyclesAffected[$defaultTargetCycle] = true;
                } else {
                    $sOther = $pdfParser->parse($fObj->getRealPath(), $defaultTargetCycle);
                    $cyclesAffected[$defaultTargetCycle] = true;
                    $schedulesToSave = array_merge($schedulesToSave, $sOther);
                }
            }

            // Simpan Jadwal Kelas Baru ke Database jika ada
            $savedCount = 0;
            if (! empty($schedulesToSave)) {
                DB::transaction(function () use ($schedulesToSave, $cyclesAffected, $request, $timeSlots, &$savedCount) {
                    if ($request->boolean('replace_existing')) {
                        foreach (array_keys($cyclesAffected) as $cyc) {
                            Schedule::where('cycle_number', $cyc)->delete();
                        }
                    }

                    $roomsMap = Room::pluck('id', 'name')->toArray();
                    $classesMap = SchoolClass::pluck('id', 'name')->toArray();
                    $teachersMap = User::whereIn('role_id', [1, 2, 5])->pluck('id', 'fullname')->toArray();
                    $fallbackId = ! empty($teachersMap) ? reset($teachersMap) : 1;

                    foreach ($schedulesToSave as $item) {
                        $rName = $item['room_name'] ?: 'Ruang Kelas';
                        if (! isset($roomsMap[$rName])) {
                            $newRoom = Room::create(['name' => $rName]);
                            $roomsMap[$rName] = $newRoom->id;
                        }
                        $roomId = $roomsMap[$rName];

                        $cName = $item['class_name'];
                        if (! isset($classesMap[$cName])) {
                            $newC = SchoolClass::create([
                                'name' => $cName,
                                'grade' => str_starts_with($cName, 'XII') ? 'XII' : (str_starts_with($cName, 'XI') ? 'XI' : 'X'),
                                'jurusan' => 'RPL',
                            ]);
                            $classesMap[$cName] = $newC->id;
                        }
                        $classId = $classesMap[$cName];

                        $teacherId = $item['teacher_id'];
                        if (! $teacherId) {
                            $tName = $item['teacher_name'];
                            if (isset($teachersMap[$tName])) {
                                $teacherId = $teachersMap[$tName];
                            } else {
                                $normTName = strtoupper(preg_replace('/[^A-Z]/', '', $tName));
                                foreach ($teachersMap as $origName => $tId) {
                                    if (strtoupper(preg_replace('/[^A-Z]/', '', $origName)) === $normTName) {
                                        $teacherId = $tId;
                                        break;
                                    }
                                }
                                if (! $teacherId) {
                                    $teacherId = $fallbackId;
                                }
                            }
                        }

                        $startSlot = $timeSlots->firstWhere('start_time', $item['start_time']);
                        $endSlot = $timeSlots->firstWhere('end_time', $item['end_time']) ?? $startSlot;

                        Schedule::updateOrCreate([
                            'cycle_number' => $item['cycle_number'],
                            'day_of_week' => $item['day_of_week'],
                            'class_id' => $classId,
                            'start_time' => $item['start_time'],
                            'end_time' => $item['end_time'],
                        ], [
                            'class_name' => $cName,
                            'subject' => $item['subject'],
                            'teacher_id' => $teacherId,
                            'room_name' => $rName,
                            'room_id' => $roomId,
                            'start_slot_id' => $startSlot?->id,
                            'end_slot_id' => $endSlot?->id,
                            'academic_year' => $item['academic_year'] ?? '2026/2027',
                        ]);

                        $savedCount++;
                    }
                });

                // Perbarui Berkas Master Markdown & JSON
                try {
                    $masterDataService->compileJadwal();
                } catch (\Throwable $e) {
                    // Non-blocking log
                }
            }

            $primaryCycle = ! empty($cyclesAffected) ? array_key_first($cyclesAffected) : $defaultTargetCycle;
            $totalFiles = count($uploadedFiles);

            return redirect()->route('waka.jadwal.index', ['cycle' => $primaryCycle])
                ->with('success', "Berhasil memproses {$totalFiles} berkas jadwal pembelajaran & ruangan. Total {$savedCount} sesi KBM berhasil disinkronkan dengan data ruangan presisi.");
        }

        // 3. Cabang Impor Berkas Markdown Master (.md)
        if (! empty($classified['markdown'])) {
            $mdFile = $classified['markdown'][0];
            $content = (string) file_get_contents($mdFile->getRealPath());
            $parsedTable = $masterDataService->parseMarkdownTable($content);
            $parsedSchedules = [];

            foreach ($parsedTable as $row) {
                $day = trim($row['hari'] ?? '');
                $className = trim($row['rombel kelas'] ?? ($row['kelas'] ?? ''));
                $jam = trim($row['jam pelajaran'] ?? ($row['jam'] ?? ($row['sesi jam'] ?? '')));
                $subject = trim($row['mata pelajaran'] ?? ($row['mapel'] ?? ''));
                $teacherName = trim($row['guru pengampu'] ?? ($row['guru'] ?? ''));
                $roomName = trim($row['ruang'] ?? 'Ruang Kelas');

                if ($day === '' || $className === '' || $subject === '') {
                    continue;
                }

                $startTime = '07:15:00';
                $endTime = '09:30:00';
                if (preg_match('/(\d{1,2}[:\.]\d{2})\s*[-–]\s*(\d{1,2}[:\.]\d{2})/', $jam, $jm)) {
                    $startTime = str_replace('.', ':', $jm[1]).':00';
                    $endTime = str_replace('.', ':', $jm[2]).':00';
                    if (strlen($startTime) === 7) {
                        $startTime = '0'.$startTime;
                    }
                    if (strlen($endTime) === 7) {
                        $endTime = '0'.$endTime;
                    }
                }

                $parsedSchedules[] = [
                    'day_of_week' => $day,
                    'cycle_number' => $defaultTargetCycle,
                    'class_name' => $className,
                    'subject' => $subject,
                    'teacher_name' => $teacherName,
                    'teacher_id' => null,
                    'room_name' => $roomName !== '' ? $roomName : 'Ruang Kelas',
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'academic_year' => '2026/2027',
                ];
            }

            if (empty($parsedSchedules)) {
                throw ValidationException::withMessages([
                    'file' => 'Tidak ditemukan data jadwal yang valid dalam file Markdown.',
                ]);
            }

            $importedCount = 0;
            DB::transaction(function () use ($parsedSchedules, $defaultTargetCycle, $request, $timeSlots, &$importedCount) {
                if ($request->boolean('replace_existing')) {
                    Schedule::where('cycle_number', $defaultTargetCycle)->delete();
                }

                $roomsMap = Room::pluck('id', 'name')->toArray();
                $classesMap = SchoolClass::pluck('id', 'name')->toArray();
                $teachersMap = User::whereIn('role_id', [1, 2, 5])->pluck('id', 'fullname')->toArray();
                $fallbackId = ! empty($teachersMap) ? reset($teachersMap) : 1;

                foreach ($parsedSchedules as $item) {
                    $rName = $item['room_name'] ?? 'Ruang Kelas';
                    if (! isset($roomsMap[$rName])) {
                        $newRoom = Room::create(['name' => $rName]);
                        $roomsMap[$rName] = $newRoom->id;
                    }
                    $roomId = $roomsMap[$rName];

                    $cName = $item['class_name'];
                    if (! isset($classesMap[$cName])) {
                        $newC = SchoolClass::create([
                            'name' => $cName,
                            'grade' => str_starts_with($cName, 'XII') ? 'XII' : (str_starts_with($cName, 'XI') ? 'XI' : 'X'),
                            'jurusan' => 'RPL',
                        ]);
                        $classesMap[$cName] = $newC->id;
                    }
                    $classId = $classesMap[$cName];

                    $teacherId = null;
                    $tName = $item['teacher_name'];
                    if (! empty($tName) && isset($teachersMap[$tName])) {
                        $teacherId = $teachersMap[$tName];
                    } else {
                        $normTName = strtoupper(preg_replace('/[^A-Z]/', '', $tName));
                        foreach ($teachersMap as $origName => $tId) {
                            if (strtoupper(preg_replace('/[^A-Z]/', '', $origName)) === $normTName) {
                                $teacherId = $tId;
                                break;
                            }
                        }
                    }
                    if (! $teacherId) {
                        $teacherId = $fallbackId;
                    }

                    $startSlot = $timeSlots->firstWhere('start_time', $item['start_time']);
                    $endSlot = $timeSlots->firstWhere('end_time', $item['end_time']) ?? $startSlot;

                    Schedule::create([
                        'day_of_week' => $item['day_of_week'],
                        'cycle_number' => $defaultTargetCycle,
                        'subject' => $item['subject'],
                        'teacher_id' => $teacherId,
                        'class_name' => $cName,
                        'class_id' => $classId,
                        'room_name' => $rName,
                        'room_id' => $roomId,
                        'start_time' => $item['start_time'],
                        'end_time' => $item['end_time'],
                        'start_slot_id' => $startSlot?->id,
                        'end_slot_id' => $endSlot?->id,
                        'academic_year' => $item['academic_year'] ?? '2026/2027',
                    ]);

                    $importedCount++;
                }
            });

            return redirect()->route('waka.jadwal.index', ['cycle' => $defaultTargetCycle])
                ->with('success', "{$importedCount} data jadwal Putaran {$defaultTargetCycle} berhasil diimpor dari file Markdown (.md).");
        }

        // 4. Cabang Impor Berkas Excel / CSV Spreadsheet Cadangan
        $spreadsheetFile = ! empty($classified['spreadsheet']) ? $classified['spreadsheet'][0] : $uploadedFiles[0];
        try {
            $spreadsheet = IOFactory::load($spreadsheetFile->getRealPath());
            $sheetRows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'file' => 'Gagal membaca file Excel/CSV: '.$e->getMessage(),
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
            if ($rawStr === '') {
                return $timeSlots->first();
            }

            if (preg_match('/\b(\d{1,2})\b/', $rawStr, $m)) {
                $num = (int) $m[1];
                $matched = $timeSlots->first(function ($s) use ($num) {
                    return preg_match('/\bJam\s*(?:Ke-?)?\s*'.$num.'\b/i', $s->name);
                });
                if ($matched) {
                    return $matched;
                }
            }

            $matched = $timeSlots->first(function ($s) use ($rawStr) {
                return strcasecmp($s->name, $rawStr) === 0;
            });
            if ($matched) {
                return $matched;
            }

            if (isset($num)) {
                $matched = $timeSlots->firstWhere('order', $num);
                if ($matched) {
                    return $matched;
                }
            }

            return $timeSlots->first();
        };

        $targetCycle = $defaultTargetCycle;

        DB::transaction(function () use ($dataRows, $colIdx, $targetCycle, $teachers, $config, $findSlot, $request, &$importedCount) {
            if ($request->boolean('replace_existing')) {
                Schedule::where('cycle_number', $targetCycle)->delete();
            }

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

                if (! $teacher) {
                    $teacher = User::create([
                        'role_id' => 2,
                        'username' => Str::slug($teacherName, '.').rand(100, 999),
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
