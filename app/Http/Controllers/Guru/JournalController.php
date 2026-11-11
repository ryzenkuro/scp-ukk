<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Journal;
use App\Models\LeaveRequest;
use App\Models\Schedule;
use App\Models\Siswa;
use App\Models\SystemConfiguration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class JournalController extends Controller
{
    /**
     * Tampilkan riwayat pengisian jurnal KBM guru
     */
    public function index(Request $request): Response
    {
        $teacher = auth()->user();
        $teacherId = $teacher->id;
        $isWaka = $teacher->isWaka() || $teacher->role?->name === 'waka';
        $config = SystemConfiguration::current();

        $dayMap = [
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
            'Sunday' => 'Minggu',
        ];
        $currentDay = $dayMap[date('l')] ?? 'Senin';
        $todayDate = now()->toDateString();
        $now = now();

        // Cari jadwal hari ini untuk guru bersangkutan pada siklus aktif
        $todaySchedules = Schedule::where('teacher_id', $teacherId)
            ->where('day_of_week', $currentDay)
            ->where('cycle_number', $config->active_cycle)
            ->orderBy('start_time')
            ->get();

        $activeScheduleForJournal = null;
        foreach ($todaySchedules as $sched) {
            $isFilled = Journal::where('schedule_id', $sched->id)->whereDate('date', $todayDate)->exists();
            if ($isFilled) {
                continue;
            }

            $startTime = \Carbon\Carbon::createFromTimeString($sched->start_time);
            $endTime = \Carbon\Carbon::createFromTimeString($sched->end_time);
            $graceEnd = $endTime->copy()->addMinutes(30);

            // Boleh diisi jika sedang berlangsung atau dalam masa toleransi 30 menit
            if ($now->gte($startTime) && $now->lte($graceEnd)) {
                $activeScheduleForJournal = $sched->id;
                break;
            }
        }

        $canCreateJournal = $isWaka || ($activeScheduleForJournal !== null);

        $search = $request->input('search');
        $date = $request->input('date');

        $query = Journal::with(['schedule.schoolClass', 'schedule.room', 'attendances.student'])
            ->where('teacher_id', $teacherId)
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('topic', 'like', "%{$search}%")
                    ->orWhereHas('schedule', function ($sq) use ($search) {
                        $sq->where('subject', 'like', "%{$search}%")
                            ->orWhere('class_name', 'like', "%{$search}%");
                    });
            });
        }

        if ($date) {
            $query->whereDate('date', $date);
        }

        $journals = $query->paginate(15)->withQueryString();

        $journals->getCollection()->transform(function ($j) use ($todayDate, $now, $isWaka) {
            $isToday = $j->date === $todayDate;
            $canEdit = false;
            if ($isWaka) {
                $canEdit = true;
            } elseif ($isToday && $j->schedule) {
                $endTime = \Carbon\Carbon::createFromTimeString($j->schedule->end_time);
                $cutoff = $endTime->copy()->addMinutes(30);
                $canEdit = $now->lte($cutoff);
            }
            $j->can_edit = $canEdit;
            return $j;
        });

        return Inertia::render('Guru/Journal/Index', [
            'journals' => $journals,
            'canCreateJournal' => $canCreateJournal,
            'activeScheduleId' => $activeScheduleForJournal,
            'filters' => [
                'search' => $search,
                'date' => $date,
            ],
        ]);
    }

    /**
     * Form pengisian jurnal KBM baru & checklist presensi
     */
    public function create(Request $request): Response|RedirectResponse
    {
        $teacher = auth()->user();
        $teacherId = $teacher->id;
        $isWaka = $teacher->isWaka() || $teacher->role?->name === 'waka';
        $config = SystemConfiguration::current();

        $dayMap = [
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
            'Sunday' => 'Minggu',
        ];
        $currentDay = $dayMap[date('l')] ?? 'Senin';
        $todayDate = now()->toDateString();
        $now = now();

        // Ambil seluruh jadwal yang diampu oleh guru ini
        $schedules = Schedule::with(['schoolClass', 'room', 'startSlot', 'endSlot'])
            ->where('teacher_id', $teacherId)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        // Cari sesi yang aktif saat ini (ongoing atau toleransi 30 menit) dan belum diisi hari ini
        $activeTodaySched = null;
        $todaySchedules = $schedules->filter(fn ($s) => $s->day_of_week === $currentDay && (int) $s->cycle_number === (int) $config->active_cycle);

        foreach ($todaySchedules as $sched) {
            $isFilled = Journal::where('schedule_id', $sched->id)->whereDate('date', $todayDate)->exists();
            if ($isFilled) {
                continue;
            }

            $startTime = \Carbon\Carbon::createFromTimeString($sched->start_time);
            $endTime = \Carbon\Carbon::createFromTimeString($sched->end_time);
            $graceEnd = $endTime->copy()->addMinutes(30);

            if ($now->gte($startTime) && $now->lte($graceEnd)) {
                $activeTodaySched = $sched;
                break;
            }
        }

        $selectedScheduleId = $request->input('schedule_id');
        $selectedSchedule = null;

        if ($selectedScheduleId) {
            $selectedSchedule = $schedules->firstWhere('id', (int) $selectedScheduleId);
        }

        if (! $selectedSchedule && $activeTodaySched) {
            $selectedSchedule = $activeTodaySched;
        }

        if (! $selectedSchedule && $schedules->isNotEmpty()) {
            $selectedSchedule = $schedules->first();
        }

        // Hitung status penguncian dan batas waktu toleransi
        $isLocked = false;
        $graceMinutesRemaining = null;
        $timeStatus = 'upcoming';

        if ($selectedSchedule) {
            if ($selectedSchedule->day_of_week === $currentDay && (int) $selectedSchedule->cycle_number === (int) $config->active_cycle) {
                $startTime = \Carbon\Carbon::createFromTimeString($selectedSchedule->start_time);
                $endTime = \Carbon\Carbon::createFromTimeString($selectedSchedule->end_time);
                $graceEnd = $endTime->copy()->addMinutes(30);

                if ($now->lt($startTime)) {
                    $timeStatus = 'upcoming';
                    if (! $isWaka) {
                        return redirect()->route('guru.journal.index')
                            ->with('error', 'Sesi KBM untuk jadwal ini belum dimulai. Jurnal baru dapat diisi saat jam pelajaran berlangsung.');
                    }
                } elseif ($now->lte($endTime)) {
                    $timeStatus = 'ongoing';
                    $isLocked = true;
                } elseif ($now->lte($graceEnd)) {
                    $timeStatus = 'grace_period';
                    $isLocked = true;
                    $graceMinutesRemaining = max(1, (int) round($now->diffInSeconds($graceEnd, false) / 60));
                } else {
                    $timeStatus = 'missed';
                    if (! $isWaka) {
                        return redirect()->route('guru.journal.index')
                            ->with('error', 'Batas toleransi pengisian jurnal KBM (30 menit setelah jam pelajaran selesai) telah berakhir. Sesi ini ditandai sebagai tidak mengisi jurnal.');
                    }
                }
            } else {
                // Jika jadwal bukan hari ini atau putaran lain, guru biasa tidak diizinkan membuka pengisian acak
                if (! $isWaka) {
                    return redirect()->route('guru.journal.index')
                        ->with('error', 'Pengisian jurnal hanya diperkenankan untuk jadwal KBM yang sedang aktif hari ini.');
                }
            }
        }

        $students = [];
        $approvedLeaves = [];

        if ($selectedSchedule) {
            // Ambil seluruh siswa aktif di kelas tersebut
            $students = Siswa::where('kelas', $selectedSchedule->class_name)
                ->where('status', 'aktif')
                ->orderBy('name')
                ->get();

            // Ambil izin yang disetujui atau ditolak untuk siswa di kelas ini
            $approvedLeaves = LeaveRequest::whereIn('status', ['approved', 'rejected'])
                ->whereIn('student_id', $students->pluck('id'))
                ->get(['id', 'student_id', 'type', 'status', 'start_date', 'end_date', 'reason', 'rejection_reason'])
                ->toArray();
        }

        return Inertia::render('Guru/Journal/Create', [
            'schedules' => $schedules,
            'selectedSchedule' => $selectedSchedule,
            'students' => $students,
            'approvedLeaves' => $approvedLeaves,
            'todayDate' => $todayDate,
            'activeCycle' => $config->active_cycle,
            'isLocked' => $isLocked,
            'graceMinutesRemaining' => $graceMinutesRemaining,
            'timeStatus' => $timeStatus,
        ]);
    }

    /**
     * Simpan jurnal KBM dan seluruh checklist presensi siswa
     */
    public function store(Request $request): RedirectResponse
    {
        $teacher = auth()->user();
        $teacherId = $teacher->id;
        $isWaka = $teacher->isWaka() || $teacher->role?->name === 'waka';
        $config = SystemConfiguration::current();

        $validated = $request->validate([
            'schedule_id' => ['required', 'exists:schedules,id'],
            'date' => ['required', 'date'],
            'topic' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'documentation' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'attendances' => ['required', 'array', 'min:1'],
            'attendances.*.student_id' => ['required', 'exists:siswas,id'],
            'attendances.*.status' => ['required', 'in:hadir,sakit,izin,alpa'],
            'attendances.*.notes' => ['nullable', 'string', 'max:255'],
        ], [
            'documentation.required' => 'Dokumentasi foto kegiatan KBM wajib dilampirkan (bisa foto langsung atau unggah file).',
            'documentation.file' => 'Dokumentasi harus berupa berkas gambar.',
            'documentation.mimes' => 'Format foto dokumentasi harus JPG, JPEG, PNG, atau WEBP.',
            'documentation.max' => 'Ukuran foto dokumentasi maksimal 10MB.',
        ]);

        $schedule = Schedule::findOrFail($validated['schedule_id']);

        if ($schedule->teacher_id !== $teacherId && ! $isWaka) {
            abort(403, 'Anda tidak berhak mengisi jurnal untuk jadwal guru lain.');
        }

        // Cek duplikasi pengisian pada tanggal yang sama
        $alreadyExists = Journal::where('schedule_id', $schedule->id)
            ->whereDate('date', $validated['date'])
            ->exists();

        if ($alreadyExists) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['schedule_id' => "Jurnal KBM untuk jadwal ini pada tanggal {$validated['date']} sudah pernah diisi."]);
        }

        // Validasi batas waktu 30 menit jika bukan waka
        if (! $isWaka) {
            $dayMap = [
                'Monday' => 'Senin',
                'Tuesday' => 'Selasa',
                'Wednesday' => 'Rabu',
                'Thursday' => 'Kamis',
                'Friday' => 'Jumat',
                'Saturday' => 'Sabtu',
                'Sunday' => 'Minggu',
            ];
            $currentDay = $dayMap[date('l')] ?? 'Senin';

            if ($schedule->day_of_week === $currentDay && (int) $schedule->cycle_number === (int) $config->active_cycle) {
                $endTime = \Carbon\Carbon::createFromTimeString($schedule->end_time);
                $graceEnd = $endTime->copy()->addMinutes(30);

                if (now()->gt($graceEnd)) {
                    return redirect()->route('guru.journal.index')
                        ->with('error', 'Batas toleransi pengisian jurnal KBM (30 menit setelah jam pelajaran selesai) telah berakhir.');
                }
            }
        }

        $documentationPath = null;
        if ($request->hasFile('documentation')) {
            $documentationPath = $request->file('documentation')->store('journal_documentations', 'public');
        }

        DB::transaction(function () use ($validated, $teacherId, $schedule, $documentationPath) {
            $journal = Journal::create([
                'schedule_id' => $schedule->id,
                'teacher_id' => $teacherId,
                'date' => $validated['date'],
                'topic' => $validated['topic'],
                'notes' => $validated['notes'] ?? null,
                'documentation_path' => $documentationPath,
            ]);

            foreach ($validated['attendances'] as $att) {
                // Periksa apakah sudah ada presensi unattached (dari izin terverifikasi) pada tanggal tersebut
                $unattached = Attendance::where('student_id', $att['student_id'])
                    ->whereDate('date', $validated['date'])
                    ->whereNull('journal_id')
                    ->first();

                if ($unattached) {
                    $unattached->update([
                        'journal_id' => $journal->id,
                        'schedule_id' => $schedule->id,
                        'status' => $att['status'],
                        'notes' => $att['notes'] ?? $unattached->notes,
                    ]);
                } else {
                    Attendance::create([
                        'journal_id' => $journal->id,
                        'student_id' => $att['student_id'],
                        'schedule_id' => $schedule->id,
                        'date' => $validated['date'],
                        'status' => $att['status'],
                        'notes' => $att['notes'] ?? null,
                    ]);
                }
            }
        });

        return redirect()->route('guru.journal.index')
            ->with('success', "Jurnal KBM \"{$validated['topic']}\" dan checklist presensi berhasil disimpan.");
    }

    /**
     * Form edit jurnal KBM dan pembaruan absensi siswa
     */
    public function edit(Journal $journal): Response|RedirectResponse
    {
        $teacher = auth()->user();
        $isWaka = $teacher->isWaka() || $teacher->role?->name === 'waka';

        if ($journal->teacher_id !== $teacher->id && ! $isWaka) {
            abort(403, 'Akses ditolak.');
        }

        $journal->load([
            'schedule.schoolClass',
            'schedule.room',
            'schedule.startSlot',
            'schedule.endSlot',
            'attendances.student',
        ]);

        // Validasi apakah jam pembelajaran masih memungkinkan untuk diedit
        $isToday = $journal->date === now()->toDateString();
        $canEdit = false;
        if ($isWaka) {
            $canEdit = true;
        } elseif ($isToday && $journal->schedule) {
            $endTime = \Carbon\Carbon::createFromTimeString($journal->schedule->end_time);
            $cutoff = $endTime->copy()->addMinutes(30);
            $canEdit = now()->lte($cutoff);
        }

        if (! $canEdit) {
            return redirect()->route('guru.journal.index')
                ->with('error', 'Jurnal KBM ini sudah terkunci permanen dan tidak dapat diedit karena jam pembelajaran telah berakhir.');
        }

        $studentIds = $journal->attendances->pluck('student_id');
        $approvedLeaves = LeaveRequest::whereIn('status', ['approved', 'rejected'])
            ->whereIn('student_id', $studentIds)
            ->get(['id', 'student_id', 'type', 'status', 'start_date', 'end_date', 'reason', 'rejection_reason'])
            ->toArray();

        return Inertia::render('Guru/Journal/Edit', [
            'journal' => $journal,
            'approvedLeaves' => $approvedLeaves,
        ]);
    }

    /**
     * Perbarui jurnal KBM dan data kehadiran
     */
    public function update(Request $request, Journal $journal): RedirectResponse
    {
        $teacher = auth()->user();
        $isWaka = $teacher->isWaka() || $teacher->role?->name === 'waka';

        if ($journal->teacher_id !== $teacher->id && ! $isWaka) {
            abort(403, 'Akses ditolak.');
        }

        $isToday = $journal->date === now()->toDateString();
        $canEdit = false;
        if ($isWaka) {
            $canEdit = true;
        } elseif ($isToday && $journal->schedule) {
            $endTime = \Carbon\Carbon::createFromTimeString($journal->schedule->end_time);
            $cutoff = $endTime->copy()->addMinutes(30);
            $canEdit = now()->lte($cutoff);
        }

        if (! $canEdit) {
            return redirect()->route('guru.journal.index')
                ->with('error', 'Batas waktu pengeditan jurnal KBM telah berakhir. Perubahan tidak dapat disimpan.');
        }

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'topic' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'documentation' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'attendances' => ['required', 'array', 'min:1'],
            'attendances.*.student_id' => ['required', 'exists:siswas,id'],
            'attendances.*.status' => ['required', 'in:hadir,sakit,izin,alpa'],
            'attendances.*.notes' => ['nullable', 'string', 'max:255'],
        ], [
            'documentation.file' => 'Dokumentasi harus berupa berkas gambar.',
            'documentation.mimes' => 'Format foto dokumentasi harus JPG, JPEG, PNG, atau WEBP.',
            'documentation.max' => 'Ukuran foto dokumentasi maksimal 10MB.',
        ]);

        $documentationPath = $journal->documentation_path;
        if ($request->hasFile('documentation')) {
            $documentationPath = $request->file('documentation')->store('journal_documentations', 'public');
        }

        DB::transaction(function () use ($journal, $validated, $documentationPath) {
            $journal->update([
                'date' => $validated['date'],
                'topic' => $validated['topic'],
                'notes' => $validated['notes'] ?? null,
                'documentation_path' => $documentationPath,
            ]);

            foreach ($validated['attendances'] as $att) {
                Attendance::updateOrCreate(
                    [
                        'journal_id' => $journal->id,
                        'student_id' => $att['student_id'],
                    ],
                    [
                        'schedule_id' => $journal->schedule_id,
                        'date' => $validated['date'],
                        'status' => $att['status'],
                        'notes' => $att['notes'] ?? null,
                    ]
                );
            }
        });

        return redirect()->route('guru.journal.index')
            ->with('success', "Jurnal KBM \"{$journal->topic}\" berhasil diperbarui.");
    }

    /**
     * Hapus jurnal KBM (presensi terkait otomatis terhapus)
     */
    public function destroy(Journal $journal): RedirectResponse
    {
        $teacher = auth()->user();
        $isWaka = $teacher->isWaka() || $teacher->role?->name === 'waka';

        if (! $isWaka) {
            return redirect()->route('guru.journal.index')
                ->with('error', 'Jurnal KBM yang sudah terisi tidak dapat dihapus karena menjadi arsip resmi catatan kehadiran sekolah.');
        }

        $topic = $journal->topic;
        $journal->delete();

        return back()->with('success', "Jurnal KBM \"{$topic}\" dan rekap presensi terkait berhasil dihapus.");
    }
}
