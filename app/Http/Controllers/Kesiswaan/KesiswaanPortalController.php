<?php

namespace App\Http\Controllers\Kesiswaan;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Siswa;
use App\Models\SystemConfiguration;
use App\Services\AttendanceSyncService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KesiswaanPortalController extends Controller
{
    /**
     * Dashboard Tim Kesiswaan
     */
    public function dashboard(): Response
    {
        AttendanceSyncService::syncAllApprovedLeaves();

        $user = auth()->user();
        $config = SystemConfiguration::first();
        $activeCycle = $config?->active_cycle ?? 1;

        $totalSiswa = Siswa::where('status', 'aktif')->count();
        $totalRombel = SchoolClass::count();

        $totalIzin = LeaveRequest::count();
        $pendingIzin = LeaveRequest::where('status', 'pending')->count();
        $approvedIzin = LeaveRequest::where('status', 'approved')->count();
        $rejectedIzin = LeaveRequest::where('status', 'rejected')->count();

        // Rekapitulasi Presensi Hari Ini (atau total presensi terkini)
        $todayAttendances = Attendance::whereDate('date', now()->toDateString())->get();
        $todayHadir = $todayAttendances->where('status', 'hadir')->count();
        $todaySakit = $todayAttendances->where('status', 'sakit')->count();
        $todayIzin = $todayAttendances->where('status', 'izin')->count();
        $todayAlpa = $todayAttendances->where('status', 'alpa')->count();

        // Jadwal mengajar jika petugas kesiswaan juga mengajar KBM
        $jadwals = Schedule::with(['schoolClass', 'room'])
            ->where('teacher_id', $user->id)
            ->where('cycle_number', $activeCycle)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        // 5 data izin siswa terbaru untuk pemantauan
        $recentLeaves = LeaveRequest::with(['student', 'verifier'])
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        return Inertia::render('Kesiswaan/Dashboard', [
            'stats' => [
                'total_siswa' => $totalSiswa,
                'total_rombel' => $totalRombel,
                'total_izin' => $totalIzin,
                'pending_izin' => $pendingIzin,
                'approved_izin' => $approvedIzin,
                'rejected_izin' => $rejectedIzin,
                'today_hadir' => $todayHadir,
                'today_sakit' => $todaySakit,
                'today_izin' => $todayIzin,
                'today_alpa' => $todayAlpa,
                'total_jadwal_mengajar' => $jadwals->count(),
            ],
            'config' => [
                'active_cycle' => $activeCycle,
                'academic_year' => $config?->academic_year ?? '2026/2027',
            ],
            'jadwals' => $jadwals,
            'recentLeaves' => $recentLeaves,
        ]);
    }

    /**
     * Rekap Presensi Siswa per Rombel Sekolah
     */
    public function presensi(Request $request): Response
    {
        AttendanceSyncService::syncAllApprovedLeaves();

        $kelasList = SchoolClass::orderBy('name')->pluck('name')->toArray();
        $selectedClass = $request->input('kelas', $kelasList[0] ?? '');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        if ($startDate && $endDate && $endDate < $startDate) {
            $endDate = $startDate;
        }
        $search = $request->input('search');

        $studentsQuery = Siswa::where('status', 'aktif')
            ->orderBy('name');

        if ($selectedClass && $selectedClass !== 'all') {
            $studentsQuery->where('kelas', $selectedClass);
        }

        if ($search) {
            $studentsQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%");
            });
        }

        $students = $studentsQuery->get();

        // Query presensi untuk siswa yang terfilter
        $attQuery = Attendance::whereIn('student_id', $students->pluck('id'));

        if ($startDate) {
            $attQuery->whereDate('date', '>=', $startDate);
        }

        if ($endDate) {
            $attQuery->whereDate('date', '<=', $endDate);
        }

        $attendances = $attQuery->get();

        $rekapData = [];
        $totalHadir = 0;
        $totalSakit = 0;
        $totalIzin = 0;
        $totalAlpa = 0;

        foreach ($students as $student) {
            $atts = $attendances->where('student_id', $student->id);
            $h = $atts->where('status', 'hadir')->count();
            $s = $atts->where('status', 'sakit')->count();
            $i = $atts->where('status', 'izin')->count();
            $a = $atts->where('status', 'alpa')->count();
            $total = $atts->count();
            $pct = $total > 0 ? round(($h / $total) * 100, 1) : 100.0;

            $totalHadir += $h;
            $totalSakit += $s;
            $totalIzin += $i;
            $totalAlpa += $a;

            $rekapData[] = [
                'id' => $student->id,
                'nis' => $student->nis,
                'name' => $student->name,
                'kelas' => $student->kelas,
                'gender' => $student->gender,
                'hadir' => $h,
                'sakit' => $s,
                'izin' => $i,
                'alpa' => $a,
                'total' => $total,
                'persentase' => $pct,
            ];
        }

        $totalSesi = $totalHadir + $totalSakit + $totalIzin + $totalAlpa;
        $avgAttendance = $totalSesi > 0 ? round(($totalHadir / $totalSesi) * 100, 1) : 100.0;

        return Inertia::render('Kesiswaan/Presensi', [
            'kelasList' => $kelasList,
            'selectedClass' => $selectedClass,
            'rekapData' => $rekapData,
            'summary' => [
                'total_students' => count($students),
                'total_hadir' => $totalHadir,
                'total_sakit' => $totalSakit,
                'total_izin' => $totalIzin,
                'total_alpa' => $totalAlpa,
                'avg_attendance' => $avgAttendance,
            ],
            'filters' => [
                'kelas' => $selectedClass,
                'start_date' => $startDate ?? '',
                'end_date' => $endDate ?? '',
                'search' => $search ?? '',
            ],
        ]);
    }

    /**
     * Rekapitulasi Izin Rombel Siswa Sekolah (Monitoring Lintas Kelas)
     */
    public function izin(Request $request): Response
    {
        $status = $request->input('status', 'all');
        $type = $request->input('type', 'all');
        $kelas = $request->input('kelas', 'all');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        if ($startDate && $endDate && $endDate < $startDate) {
            $endDate = $startDate;
        }
        $search = $request->input('search');

        $query = LeaveRequest::with(['student', 'verifier'])
            ->orderByDesc('created_at');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($type !== 'all') {
            $query->where('type', $type);
        }

        if ($kelas !== 'all' && ! empty($kelas)) {
            $query->whereHas('student', function ($sq) use ($kelas) {
                $sq->where('kelas', $kelas);
            });
        }

        if ($startDate) {
            $query->whereDate('start_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('end_date', '<=', $endDate);
        }

        if ($search) {
            $query->whereHas('student', function ($sq) use ($search) {
                $sq->where('name', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%")
                    ->orWhere('kelas', 'like', "%{$search}%");
            });
        }

        $leaves = $query->paginate(20)->withQueryString();

        $kelasList = SchoolClass::orderBy('name')->pluck('name')->toArray();

        $stats = [
            'total' => LeaveRequest::count(),
            'pending' => LeaveRequest::where('status', 'pending')->count(),
            'approved' => LeaveRequest::where('status', 'approved')->count(),
            'rejected' => LeaveRequest::where('status', 'rejected')->count(),
        ];

        return Inertia::render('Kesiswaan/Izin', [
            'leaves' => $leaves,
            'kelasList' => $kelasList,
            'stats' => $stats,
            'filters' => [
                'status' => $status,
                'type' => $type,
                'kelas' => $kelas,
                'start_date' => $startDate ?? '',
                'end_date' => $endDate ?? '',
                'search' => $search ?? '',
            ],
        ]);
    }
}
