<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Journal;
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
        $teacherId = auth()->id();
        $search = $request->input('search');
        $date = $request->input('date');

        $query = Journal::with(['schedule.schoolClass', 'schedule.room', 'attendances'])
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

        return Inertia::render('Guru/Journal/Index', [
            'journals' => $journals,
            'filters' => [
                'search' => $search,
                'date' => $date,
            ],
        ]);
    }

    /**
     * Form pengisian jurnal KBM baru & checklist presensi
     */
    public function create(Request $request): Response
    {
        $teacherId = auth()->id();
        $config = SystemConfiguration::current();

        $selectedScheduleId = $request->input('schedule_id');

        // Ambil seluruh jadwal yang diampu oleh guru ini
        $schedules = Schedule::with(['schoolClass', 'room', 'startSlot', 'endSlot'])
            ->where('teacher_id', $teacherId)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        $selectedSchedule = null;
        $students = [];

        if ($selectedScheduleId) {
            $selectedSchedule = $schedules->firstWhere('id', (int) $selectedScheduleId);
        }

        if (!$selectedSchedule && $schedules->isNotEmpty()) {
            $selectedSchedule = $schedules->first();
        }

        if ($selectedSchedule) {
            // Ambil seluruh siswa aktif di kelas tersebut
            $students = Siswa::where('kelas', $selectedSchedule->class_name)
                ->where('status', 'aktif')
                ->orderBy('name')
                ->get();
        }

        return Inertia::render('Guru/Journal/Create', [
            'schedules' => $schedules,
            'selectedSchedule' => $selectedSchedule,
            'students' => $students,
            'todayDate' => now()->toDateString(),
            'activeCycle' => $config->active_cycle,
        ]);
    }

    /**
     * Simpan jurnal KBM dan seluruh checklist presensi siswa
     */
    public function store(Request $request): RedirectResponse
    {
        $teacherId = auth()->id();

        $validated = $request->validate([
            'schedule_id' => ['required', 'exists:schedules,id'],
            'date' => ['required', 'date'],
            'topic' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'attendances' => ['required', 'array', 'min:1'],
            'attendances.*.student_id' => ['required', 'exists:siswas,id'],
            'attendances.*.status' => ['required', 'in:hadir,sakit,izin,alpa'],
            'attendances.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        $schedule = Schedule::findOrFail($validated['schedule_id']);

        if ($schedule->teacher_id !== $teacherId && auth()->user()->role?->name !== 'waka') {
            abort(403, 'Anda tidak berhak mengisi jurnal untuk jadwal guru lain.');
        }

        DB::transaction(function () use ($validated, $teacherId, $schedule) {
            $journal = Journal::create([
                'schedule_id' => $schedule->id,
                'teacher_id' => $teacherId,
                'date' => $validated['date'],
                'topic' => $validated['topic'],
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['attendances'] as $att) {
                Attendance::create([
                    'journal_id' => $journal->id,
                    'student_id' => $att['student_id'],
                    'schedule_id' => $schedule->id,
                    'date' => $validated['date'],
                    'status' => $att['status'],
                    'notes' => $att['notes'] ?? null,
                ]);
            }
        });

        return redirect()->route('guru.journal.index')
            ->with('success', "Jurnal KBM \"{$validated['topic']}\" dan checklist presensi berhasil disimpan.");
    }

    /**
     * Form edit jurnal KBM dan pembaruan absensi siswa
     */
    public function edit(Journal $journal): Response
    {
        if ($journal->teacher_id !== auth()->id() && auth()->user()->role?->name !== 'waka') {
            abort(403, 'Akses ditolak.');
        }

        $journal->load([
            'schedule.schoolClass',
            'schedule.room',
            'schedule.startSlot',
            'schedule.endSlot',
            'attendances.student'
        ]);

        return Inertia::render('Guru/Journal/Edit', [
            'journal' => $journal,
        ]);
    }

    /**
     * Perbarui jurnal KBM dan data kehadiran
     */
    public function update(Request $request, Journal $journal): RedirectResponse
    {
        if ($journal->teacher_id !== auth()->id() && auth()->user()->role?->name !== 'waka') {
            abort(403, 'Akses ditolak.');
        }

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'topic' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'attendances' => ['required', 'array', 'min:1'],
            'attendances.*.student_id' => ['required', 'exists:siswas,id'],
            'attendances.*.status' => ['required', 'in:hadir,sakit,izin,alpa'],
            'attendances.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($journal, $validated) {
            $journal->update([
                'date' => $validated['date'],
                'topic' => $validated['topic'],
                'notes' => $validated['notes'] ?? null,
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
        if ($journal->teacher_id !== auth()->id() && auth()->user()->role?->name !== 'waka') {
            abort(403, 'Akses ditolak.');
        }

        $topic = $journal->topic;
        $journal->delete();

        return back()->with('success', "Jurnal KBM \"{$topic}\" dan rekap presensi terkait berhasil dihapus.");
    }
}

