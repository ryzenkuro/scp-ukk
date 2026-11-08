<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Schedule;
use App\Models\Siswa;
use App\Models\SystemConfiguration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        // Rekap kehadiran siswa pribadi
        $attendances = Attendance::where('student_id', $student?->id)->get();
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

        $cycle = $request->input('cycle', (string) $config->active_cycle);

        $schedules = [];
        if ($student) {
            $query = Schedule::with(['teacher', 'room', 'startSlot', 'endSlot'])
                ->where('class_name', $student->kelas)
                ->orderBy('day_of_week')
                ->orderBy('start_time');

            if ($cycle !== 'all') {
                $query->where('cycle_number', (int) $cycle);
            }

            $schedules = $query->get();
        }

        return Inertia::render('Siswa/Jadwal', [
            'student' => $student,
            'schedules' => $schedules,
            'activeCycle' => (int) $cycle,
            'config' => [
                'active_cycle' => $config->active_cycle,
                'academic_year' => $config->academic_year,
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

        if (!$student) {
            return back()->with('error', 'Profil siswa tidak ditemukan.');
        }

        $validated = $request->validate([
            'type' => ['required', 'in:sakit,izin'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'max:500'],
            'attachment' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
        ], [
            'attachment.required' => 'Bukti foto atau surat keterangan wajib dilampirkan.',
            'end_date.after_or_equal' => 'Tanggal selesai izin harus sama atau setelah tanggal mulai.',
            'attachment.mimes' => 'Format file bukti harus berupa JPG, PNG, WEBP, atau PDF.',
            'attachment.max' => 'Ukuran file bukti maksimal 10MB.',
        ]);

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

        $jadwals = [];
        if ($student) {
            $jadwals = Schedule::with(['teacher', 'room', 'startSlot', 'endSlot', 'schoolClass'])
                ->where('class_name', $student->kelas)
                ->where('cycle_number', $activeCycle)
                ->orderBy('day_of_week')
                ->orderBy('start_time')
                ->get();
        }

        return view('pdf.jadwal', [
            'documentTitle' => 'JADWAL BELAJAR SISWA',
            'pageTitle' => 'Jadwal Belajar - ' . ($student?->name ?? 'Siswa'),
            'academicYear' => $config->academic_year,
            'activeCycle' => $activeCycle,
            'filterClass' => $student?->kelas,
            'studentName' => $student?->name,
            'jadwals' => $jadwals,
        ]);
    }
}

