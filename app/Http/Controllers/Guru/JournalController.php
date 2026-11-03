<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Journal;
use App\Models\LeaveRequest;
use App\Models\Schedule;
use App\Models\SystemConfiguration;
use App\Models\User;
use App\Services\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class JournalController extends Controller
{
    /**
     * Tampilkan daftar sesi mengajar hari ini dan riwayat jurnal guru
     */
    public function index(): View
    {
        $teacher = auth()->user();
        $config = SystemConfiguration::current();
        $today = now()->toDateString();

        // Nama hari ini dalam bahasa Indonesia via Helper Terpusat
        $currentDayName = \App\Support\IndonesianDay::now();

        // Jadwal guru pada siklus aktif
        $allMySchedules = Schedule::with(['schoolClass', 'room', 'journals' => function ($q) use ($today) {
            $q->where('date', $today);
        }])
            ->where('teacher_id', $teacher->id)
            ->where('cycle_type', $config->active_cycle)
            ->orderBy('start_time')
            ->get();

        // Filter jadwal yang terjadwal pada hari ini
        $todaySchedules = $allMySchedules->where('day_of_week', $currentDayName);

        // Riwayat jurnal yang sudah pernah diisi oleh guru ini
        $myJournals = Journal::with(['schedule.schoolClass', 'schedule.room', 'attendances'])
            ->whereHas('schedule', fn($q) => $q->where('teacher_id', $teacher->id))
            ->latest('date')
            ->latest('id')
            ->paginate(10);

        return view('guru.journals.index', compact(
            'teacher',
            'config',
            'currentDayName',
            'today',
            'todaySchedules',
            'allMySchedules',
            'myJournals'
        ));
    }

    /**
     * Form Buka Kelas & Input Jurnal Mengajar + Sinkronisasi Presensi
     */
    public function create(Schedule $schedule): View|RedirectResponse
    {
        if ($schedule->teacher_id !== auth()->id()) {
            abort(403, 'Anda tidak berhak membuka jurnal untuk jadwal guru lain.');
        }

        $today = now()->toDateString();

        // Cek jika jurnal untuk hari ini pada jadwal ini sudah pernah dibuat
        $existingJournal = Journal::where('schedule_id', $schedule->id)
            ->where('date', $today)
            ->first();

        if ($existingJournal) {
            return redirect()->route('guru.journals.show', $existingJournal->id)
                ->with('info', 'Jurnal mengajar untuk sesi ini hari ini sudah selesai diisi.');
        }

        // Ambil seluruh siswa di kelas terkait jadwal ini
        $students = User::where('class_id', $schedule->class_id)
            ->whereHas('role', fn($q) => $q->where('role_name', 'siswa'))
            ->orderBy('fullname')
            ->get();

        // 🔍 Logika Inti UKK: Cek surat izin yang sudah berstatus 'Approved' untuk hari ini
        $approvedLeaves = LeaveRequest::whereIn('student_id', $students->pluck('id'))
            ->where('status_approval', 'Approved')
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->get()
            ->keyBy('student_id');

        return view('guru.journals.create', compact('schedule', 'students', 'approvedLeaves', 'today'));
    }

    /**
     * Simpan Jurnal Mengajar & Auto-Sync Presensi secara Transaksional
     */
    public function store(Request $request, Schedule $schedule, AttendanceService $attendanceService): RedirectResponse
    {
        if ($schedule->teacher_id !== auth()->id()) {
            abort(403, 'Anda tidak berhak mengisi jurnal untuk jadwal guru lain.');
        }

        $today = now()->toDateString();

        // Validasi input
        $validated = $request->validate([
            'topic_description' => 'required|string|min:5|max:2000',
            'photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
            'attendances' => 'required|array',
            'attendances.*' => 'required|in:Hadir,Izin,Sakit,Alpha',
        ], [
            'topic_description.required' => 'Ringkasan materi pembelajaran wajib diisi.',
            'topic_description.min' => 'Ringkasan materi minimal 5 karakter.',
            'photo.required' => 'Foto bukti kegiatan pembelajaran kelas realtime wajib diunggah.',
            'photo.image' => 'Berkas bukti harus berupa file foto gambar.',
            'photo.mimes' => 'Format foto harus berupa JPG, PNG, atau WebP.',
            'photo.max' => 'Ukuran foto maksimal adalah 2MB.',
        ]);

        // Cek duplikasi entri pada tanggal yang sama
        $alreadyCreated = Journal::where('schedule_id', $schedule->id)
            ->where('date', $today)
            ->exists();

        if ($alreadyCreated) {
            return redirect()->route('guru.journals.index')
                ->withErrors(['journal' => 'Jurnal untuk kelas dan jadwal ini sudah pernah disimpan hari ini.']);
        }

        // Jalankan Database Transaction untuk menjaga konsistensi journals dan attendances
        DB::transaction(function () use ($request, $schedule, $today, $attendanceService, &$journal) {
            // 1. Simpan foto bukti ke storage public disk
            $photoPath = $request->file('photo')->store('journals_photos', 'public');

            // 2. Simpan entri Jurnal
            $journal = Journal::create([
                'schedule_id' => $schedule->id,
                'date' => $today,
                'topic_description' => $request->topic_description,
                'photo_path' => $photoPath,
            ]);

            // 3. Rekam dan Auto-Sync Presensi via AttendanceService (Arah 1: BR-004)
            $attendanceService->recordAttendance($journal, $request->attendances, $today);
        });

        return redirect()->route('guru.journals.show', $journal->id)
            ->with('success', 'Jurnal mengajar dan sinkronisasi presensi kelas berhasil disimpan!');
    }

    /**
     * Tampilkan detail rekap jurnal mengajar dan kehadiran kelas
     */
    public function show(Journal $journal): View
    {
        if ($journal->schedule->teacher_id !== auth()->id()) {
            abort(403, 'Anda tidak berhak melihat jurnal guru lain.');
        }

        $journal->load(['schedule.schoolClass', 'schedule.room', 'attendances.student']);

        $attendances = $journal->attendances;
        $stats = [
            'total' => $attendances->count(),
            'hadir' => $attendances->where('status', 'Hadir')->count(),
            'izin' => $attendances->where('status', 'Izin')->count(),
            'sakit' => $attendances->where('status', 'Sakit')->count(),
            'alpha' => $attendances->where('status', 'Alpha')->count(),
        ];

        return view('guru.journals.show', compact('journal', 'attendances', 'stats'));
    }
}
