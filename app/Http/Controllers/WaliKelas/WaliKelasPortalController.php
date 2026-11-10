<?php

namespace App\Http\Controllers\WaliKelas;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\HomeroomAssignment;
use App\Models\LeaveRequest;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Siswa;
use App\Models\SystemConfiguration;
use App\Models\TimeSlot;
use App\Services\AttendanceSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class WaliKelasPortalController extends Controller
{
    /**
     * Dapatkan daftar nama kelas yang dibina oleh wali kelas yang sedang login
     */
    protected function getAssignedClassNames(): array
    {
        $user = auth()->user();
        if (! $user) {
            return [];
        }

        // 1. Dari homeroom_assignments
        $classNames = HomeroomAssignment::where('user_id', $user->id)
            ->pluck('class_name')
            ->toArray();

        // 2. Dari pivot user_school_classes
        if (empty($classNames)) {
            $classNames = DB::table('user_school_classes')
                ->join('school_classes', 'user_school_classes.school_class_id', '=', 'school_classes.id')
                ->where('user_school_classes.user_id', $user->id)
                ->pluck('school_classes.name')
                ->toArray();
        }

        // 3. Fallback jika ada relasi schoolClass atau class_id
        if (empty($classNames) && $user->schoolClass?->name) {
            $classNames[] = $user->schoolClass->name;
        }

        if (empty($classNames) && $user->class_id) {
            $c = SchoolClass::find($user->class_id);
            if ($c) {
                $classNames[] = $c->name;
            }
        }

        // 4. Default ke XII RPL 2 untuk wali kelas jika belum ada penugasan eksplisit
        if (empty($classNames)) {
            $classNames[] = 'XII RPL 2';
        }

        $assigned = array_values(array_unique(array_filter($classNames)));

        // Wali Kelas maksimal hanya membina 1 kelas binaan
        return [end($assigned) ?: 'XII RPL 2'];
    }

    /**
     * Dashboard Wali Kelas
     */
    public function dashboard(): Response
    {
        $classNames = $this->getAssignedClassNames();
        $config = SystemConfiguration::first();

        $totalSiswa = Siswa::whereIn('kelas', $classNames)
            ->where('status', 'aktif')
            ->count();

        $pendingIzin = LeaveRequest::whereHas('student', function ($sq) use ($classNames) {
            $sq->whereIn('kelas', $classNames);
        })->where('status', 'pending')->count();

        $approvedIzin = LeaveRequest::whereHas('student', function ($sq) use ($classNames) {
            $sq->whereIn('kelas', $classNames);
        })->where('status', 'approved')->count();

        $recentLeaves = LeaveRequest::with(['student'])
            ->whereHas('student', function ($sq) use ($classNames) {
                $sq->whereIn('kelas', $classNames);
            })
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        return Inertia::render('WaliKelas/Dashboard', [
            'classNames' => $classNames,
            'stats' => [
                'total_siswa' => $totalSiswa,
                'pending_izin' => $pendingIzin,
                'approved_izin' => $approvedIzin,
            ],
            'config' => [
                'academic_year' => $config?->academic_year ?? '2026/2027',
                'active_cycle' => $config?->active_cycle ?? 1,
            ],
            'recentLeaves' => $recentLeaves,
        ]);
    }

    /**
     * Tampilkan jadwal pembelajaran kelas binaan wali kelas (berbasis hari & Jam Ke-1 s.d. Ke-10)
     */
    public function jadwal(Request $request): Response
    {
        $classNames = $this->getAssignedClassNames();
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

        // Kelas yang dipilih (default ke kelas pertama binaan)
        $defaultClass = $classNames[0] ?? 'XI RPL 1';
        $selectedClass = $request->input('kelas', $defaultClass);
        $cycle = $request->input('cycle', (string) $config->active_cycle);

        $driver = DB::connection()->getDriverName();
        $dayOrderRaw = ($driver === 'mysql' || $driver === 'mariadb')
            ? "FIELD(day_of_week, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat')"
            : "CASE day_of_week WHEN 'Senin' THEN 1 WHEN 'Selasa' THEN 2 WHEN 'Rabu' THEN 3 WHEN 'Kamis' THEN 4 WHEN 'Jumat' THEN 5 ELSE 6 END";

        $query = Schedule::with(['teacher', 'schoolClass', 'room', 'startSlot', 'endSlot'])
            ->where('class_name', $selectedClass)
            ->orderByRaw($dayOrderRaw)
            ->orderBy('start_time');

        if ($cycle !== 'all') {
            $query->where('cycle_number', (int) $cycle);
        }

        $schedules = $query->get();
        $timeSlots = TimeSlot::orderBy('order')->get();

        return Inertia::render('WaliKelas/Jadwal', [
            'classNames' => $classNames,
            'selectedClass' => $selectedClass,
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
     * Daftar Siswa Kelas Binaan (Read-only, tanpa aksi reset kata sandi)
     */
    public function siswa(Request $request): Response
    {
        $classNames = $this->getAssignedClassNames();
        $search = $request->input('search');

        $query = Siswa::whereIn('kelas', $classNames)
            ->where('status', 'aktif')
            ->with(['user', 'parentUser'])
            ->orderBy('name');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%");
            });
        }

        $students = $query->paginate(20)->withQueryString();

        return Inertia::render('WaliKelas/Siswa', [
            'classNames' => $classNames,
            'students' => $students,
            'filters' => [
                'search' => $search ?? '',
            ],
        ]);
    }

    /**
     * Verifikasi Permohonan Izin Khusus Kelas Binaan
     */
    public function izin(Request $request): Response
    {
        $classNames = $this->getAssignedClassNames();
        $status = $request->input('status', 'all');
        $type = $request->input('type', 'all');
        $search = $request->input('search');

        $query = LeaveRequest::with(['student', 'verifier'])
            ->whereHas('student', function ($sq) use ($classNames) {
                $sq->whereIn('kelas', $classNames);
            })
            ->orderByDesc('created_at');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($type !== 'all') {
            $query->where('type', $type);
        }

        if ($search) {
            $query->whereHas('student', function ($sq) use ($search) {
                $sq->where('name', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%");
            });
        }

        $leaves = $query->paginate(15)->withQueryString();

        $stats = [
            'pending' => LeaveRequest::whereHas('student', fn ($sq) => $sq->whereIn('kelas', $classNames))->where('status', 'pending')->count(),
            'approved' => LeaveRequest::whereHas('student', fn ($sq) => $sq->whereIn('kelas', $classNames))->where('status', 'approved')->count(),
            'rejected' => LeaveRequest::whereHas('student', fn ($sq) => $sq->whereIn('kelas', $classNames))->where('status', 'rejected')->count(),
        ];

        return Inertia::render('WaliKelas/Izin', [
            'classNames' => $classNames,
            'leaves' => $leaves,
            'stats' => $stats,
            'filters' => [
                'status' => $status,
                'type' => $type,
                'search' => $search ?? '',
            ],
        ]);
    }

    /**
     * Proses Persetujuan/Penolakan Izin Siswa Kelas Binaan
     */
    public function verifyIzin(Request $request, LeaveRequest $leave): RedirectResponse
    {
        $classNames = $this->getAssignedClassNames();

        if (! in_array($leave->student?->kelas, $classNames, true)) {
            abort(403, 'Akses Ditolak: Anda hanya berwenang memverifikasi izin siswa di kelas binaan Anda.');
        }

        $validated = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'rejection_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $leave->update([
            'status' => $validated['status'],
            'verified_by' => auth()->id(),
            'verified_at' => now(),
            'rejection_reason' => $validated['rejection_reason'] ?? null,
        ]);

        // Sinkronkan ke tabel presensi (attendances)
        AttendanceSyncService::syncLeaveRequest($leave);

        $statusText = $validated['status'] === 'approved' ? 'disetujui' : 'ditolak';

        return back()->with('success', "Permohonan izin siswa {$leave->student?->name} ({$leave->student?->kelas}) berhasil {$statusText}.");
    }

    /**
     * Rekap Presensi Siswa Kelas Binaan
     */
    public function presensi(Request $request): Response
    {
        // Pastikan seluruh izin yang disetujui tersinkron ke tabel presensi
        AttendanceSyncService::syncAllApprovedLeaves();

        $classNames = $this->getAssignedClassNames();
        $selectedClass = $request->input('kelas', $classNames[0] ?? '');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        if ($startDate && $endDate && $endDate < $startDate) {
            $endDate = $startDate;
        }

        $students = Siswa::where('kelas', $selectedClass)
            ->where('status', 'aktif')
            ->orderBy('name')
            ->get();

        $query = Attendance::whereIn('student_id', $students->pluck('id'));

        if ($startDate) {
            $query->whereDate('date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('date', '<=', $endDate);
        }

        $attendances = $query->get();

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
                'gender' => $student->gender,
                'hadir' => $h,
                'sakit' => $s,
                'izin' => $i,
                'alpa' => $a,
                'total' => $total,
                'persentase' => $pct,
            ];
        }

        return Inertia::render('WaliKelas/Presensi', [
            'classNames' => $classNames,
            'selectedClass' => $selectedClass,
            'rekapData' => $rekapData,
            'summary' => [
                'total_students' => count($students),
                'total_hadir' => $totalHadir,
                'total_sakit' => $totalSakit,
                'total_izin' => $totalIzin,
                'total_alpa' => $totalAlpa,
            ],
            'filters' => [
                'kelas' => $selectedClass,
                'start_date' => $startDate ?? '',
                'end_date' => $endDate ?? '',
            ],
        ]);
    }
}
