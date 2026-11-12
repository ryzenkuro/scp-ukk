<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Schedule;
use App\Models\Siswa;
use App\Models\SystemConfiguration;
use App\Models\TimeSlot;
use App\Services\AttendanceSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PortalController extends Controller
{
    /**
     * Dashboard Portal Siswa & Orang Tua
     */
    public function dashboard(): Response
    {
        $user = auth()->user();
        $student = $user->student ?? Siswa::where('status', 'aktif')->first();
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

        // Pastikan izin yang disetujui tersinkron ke rekapan pribadi siswa (tanpa presensi masa depan)
        AttendanceSyncService::syncAllApprovedLeaves($student?->id);

        // Rekap kehadiran siswa pribadi hanya untuk hari yang sudah/sedang berjalan atau memiliki journal_id
        $attendances = Attendance::where('student_id', $student?->id)
            ->where(function ($q) {
                $q->whereNotNull('journal_id')
                  ->orWhereDate('date', '<=', now()->toDateString());
            })
            ->get();
        $hadir = $attendances->where('status', 'hadir')->count();
        $sakit = $attendances->where('status', 'sakit')->count();
        $izin = $attendances->where('status', 'izin')->count();
        $alpa = $attendances->where('status', 'alpa')->count();
        $totalKBM = $attendances->count();

        $attendanceRate = $totalKBM > 0 ? round(($hadir / $totalKBM) * 100, 1) : 100.0;

        // Jadwal pelajaran kelas hari ini
        $todaySchedules = [];
        if ($student) {
            $todaySchedules = Schedule::with(['teacher', 'room', 'startSlot', 'endSlot'])
                ->where('class_name', $student->kelas)
                ->where('day_of_week', $currentDay)
                ->where('cycle_number', $config->active_cycle)
                ->orderBy('start_time')
                ->get();
        }

        // Permohonan izin terbaru
        $recentLeaves = LeaveRequest::where('student_id', $student?->id)
            ->orderByDesc('id')
            ->take(5)
            ->get();

        return Inertia::render('Siswa/Dashboard', [
            'student' => $student,
            'todaySchedules' => $todaySchedules,
            'recentLeaves' => $recentLeaves,
            'currentDay' => $currentDay,
            'stats' => [
                'hadir' => $hadir,
                'sakit' => $sakit,
                'izin' => $izin,
                'alpa' => $alpa,
                'total_kbm' => $totalKBM,
                'rate' => $attendanceRate,
            ],
            'config' => [
                'active_cycle' => $config->active_cycle,
                'academic_year' => $config->academic_year,
                'ceremony_cycle_1' => (bool) $config->ceremony_cycle_1,
                'ceremony_cycle_2' => (bool) $config->ceremony_cycle_2,
            ],
        ]);
    }

    /**
     * Tampilkan jadwal pelajaran kelas siswa
     */
    public function jadwal(Request $request): Response
    {
        $user = auth()->user();
        $student = $user->student ?? Siswa::where('status', 'aktif')->first();
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
        $todayName = $dayMap[date('l')] ?? 'Senin';

        $cycle = $request->input('cycle', (string) $config->active_cycle);

        $driver = DB::connection()->getDriverName();
        $dayOrderRaw = ($driver === 'mysql' || $driver === 'mariadb')
            ? "FIELD(day_of_week, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat')"
            : "CASE day_of_week WHEN 'Senin' THEN 1 WHEN 'Selasa' THEN 2 WHEN 'Rabu' THEN 3 WHEN 'Kamis' THEN 4 WHEN 'Jumat' THEN 5 ELSE 6 END";

        $schedules = [];
        if ($student) {
            $query = Schedule::with(['teacher', 'room', 'startSlot', 'endSlot'])
                ->where('class_name', $student->kelas)
                ->orderByRaw($dayOrderRaw)
                ->orderBy('start_time');

            if ($cycle !== 'all') {
                $query->where('cycle_number', (int) $cycle);
            }

            $schedules = $query->get();
        }

        $timeSlots = TimeSlot::orderBy('order')->get();

        return Inertia::render('Siswa/Jadwal', [
            'student' => $student,
            'schedules' => $schedules,
            'timeSlots' => $timeSlots,
            'todayName' => $todayName,
            'activeCycle' => (int) $cycle,
            'config' => [
                'active_cycle' => $config->active_cycle,
                'academic_year' => $config->academic_year,
                'ceremony_cycle_1' => (bool) $config->ceremony_cycle_1,
                'ceremony_cycle_2' => (bool) $config->ceremony_cycle_2,
            ],
        ]);
    }

    /**
     * Halaman pengajuan izin & riwayat izin siswa
     */
    public function izin(): Response
    {
        $user = auth()->user();
        $student = $user->student ?? Siswa::where('status', 'aktif')->first();

        $leaves = LeaveRequest::where('student_id', $student?->id)
            ->orderByDesc('id')
            ->paginate(10);

        return Inertia::render('Siswa/Izin', [
            'student' => $student,
            'leaves' => $leaves,
            'todayDate' => now()->toDateString(),
        ]);
    }

    /**
     * Kirim permohonan izin baru
     */
    public function submitIzin(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $student = $user->student ?? Siswa::where('status', 'aktif')->first();

        if (! $student) {
            return back()->with('error', 'Profil siswa tidak ditemukan.');
        }

        $validated = $request->validate([
            'type' => ['required', 'in:sakit,izin'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'max:500'],
            'selfie' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'attachment' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
        ], [
            'selfie.required' => 'Foto selfie realtime wajib diambil / dilampirkan.',
            'selfie.mimes' => 'Format foto selfie harus berupa JPG, PNG, atau WEBP.',
            'selfie.max' => 'Ukuran foto selfie maksimal 10MB.',
            'attachment.required' => 'Surat keterangan sakit/dokter atau surat izin wajib diunggah.',
            'attachment.mimes' => 'Format surat keterangan harus berupa JPG, PNG, WEBP, atau PDF.',
            'attachment.max' => 'Ukuran berkas surat keterangan maksimal 10MB.',
            'end_date.after_or_equal' => 'Sampai tanggal tidak boleh lebih awal dari mulai tanggal.',
        ]);

        if ($request->hasFile('selfie') && $request->hasFile('attachment')) {
            $selfieHash = hash_file('sha256', $request->file('selfie')->getRealPath());
            $attachmentHash = hash_file('sha256', $request->file('attachment')->getRealPath());
            if ($selfieHash === $attachmentHash) {
                return back()->withErrors([
                    'attachment' => 'Berkas surat keterangan tidak boleh sama dengan foto selfie. Harap unggah foto fisik surat keterangan atau file PDF asli.',
                ])->withInput();
            }
        }

        $selfiePath = null;
        if ($request->hasFile('selfie')) {
            $selfiePath = $request->file('selfie')->store('leave_selfies', 'public');
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('leave_attachments', 'public');
        }

        LeaveRequest::create([
            'student_id' => $student->id,
            'type' => $validated['type'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'reason' => $validated['reason'],
            'selfie_path' => $selfiePath,
            'attachment_path' => $attachmentPath,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Permohonan izin berhasil diajukan dan sedang menunggu verifikasi Wali Kelas / Kesiswaan.');
    }

    /**
     * Cetak jadwal belajar siswa untuk kelas bersangkutan (A4 Landscape)
     */
    public function exportPdf(Request $request)
    {
        $user = auth()->user();
        $student = $user->student ?? Siswa::where('status', 'aktif')->first();
        $config = SystemConfiguration::current();
        $activeCycle = $request->filled('cycle') && in_array($request->cycle, ['1', '2'])
            ? (int) $request->cycle
            : $config->active_cycle;

        $driver = DB::connection()->getDriverName();
        $dayOrderRaw = ($driver === 'mysql' || $driver === 'mariadb')
            ? "FIELD(day_of_week, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat')"
            : "CASE day_of_week WHEN 'Senin' THEN 1 WHEN 'Selasa' THEN 2 WHEN 'Rabu' THEN 3 WHEN 'Kamis' THEN 4 WHEN 'Jumat' THEN 5 ELSE 6 END";

        $jadwals = [];
        if ($student) {
            $jadwals = Schedule::with(['teacher', 'room', 'startSlot', 'endSlot', 'schoolClass'])
                ->where('class_name', $student->kelas)
                ->where('cycle_number', $activeCycle)
                ->orderByRaw($dayOrderRaw)
                ->orderBy('start_time')
                ->get();
        }

        $hasCeremony = ($activeCycle === 2) ? (bool) $config->ceremony_cycle_2 : (bool) $config->ceremony_cycle_1;

        return view('pdf.jadwal', [
            'documentTitle' => 'JADWAL BELAJAR SISWA',
            'pageTitle' => 'Jadwal Belajar - '.($student?->name ?? 'Siswa'),
            'academicYear' => $config->academic_year,
            'activeCycle' => $activeCycle,
            'filterClass' => $student?->kelas,
            'studentName' => $student?->name,
            'jadwals' => $jadwals,
            'hasCeremony' => $hasCeremony,
        ]);
    }
}
