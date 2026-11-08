<?php

namespace App\Http\Controllers\Waka;

use App\Http\Controllers\Controller;
use App\Models\Alumni;
use App\Models\Attendance;
use App\Models\Schedule;
use App\Models\Siswa;
use App\Models\SystemConfiguration;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $config = SystemConfiguration::current();

        $stats = [
            'total_siswa' => Siswa::where('status', 'aktif')->count(),
            'total_guru' => User::where('role_id', 2)->count(),
            'total_alumni' => Alumni::count(),
            'total_jadwal' => Schedule::where('cycle_number', $config->active_cycle)->count(),
        ];

        // Deteksi hari ini dalam bahasa Indonesia
        $dayMap = [
            'Sunday' => 'Minggu',
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
        ];
        $todayName = $dayMap[now()->format('l')] ?? 'Senin';

        // Ambil jadwal mengajar sesuai hari ini dan putaran aktif (atau default hari Senin untuk demo)
        $todaySchedules = Schedule::with(['teacher', 'room', 'schoolClass'])
            ->where('cycle_number', $config->active_cycle)
            ->where(function ($q) use ($todayName) {
                $q->where('day_of_week', $todayName)
                  ->orWhere('day_of_week', 'Senin'); // Pastikan selalu ada jadwal yang bisa dilihat saat demo
            })
            ->orderBy('start_time')
            ->take(5)
            ->get();

        // Ringkasan presensi hari ini
        $todayDate = now()->toDateString();
        $attendanceSummary = [
            'hadir' => Attendance::whereDate('date', $todayDate)->where('status', 'hadir')->count(),
            'sakit' => Attendance::whereDate('date', $todayDate)->where('status', 'sakit')->count(),
            'izin' => Attendance::whereDate('date', $todayDate)->where('status', 'izin')->count(),
            'alpa' => Attendance::whereDate('date', $todayDate)->where('status', 'alpa')->count(),
        ];

        return Inertia::render('Waka/Dashboard', [
            'stats' => $stats,
            'todaySchedules' => $todaySchedules,
            'attendanceSummary' => $attendanceSummary,
            'currentDay' => $todayName,
            'config' => [
                'active_cycle' => (int) $config->active_cycle,
                'is_scheduled_locked' => (bool) $config->is_scheduled_locked,
                'academic_year' => $config->academic_year,
                'needs_year_update' => $config->isAcademicYearUpdateNeeded(),
                'real_academic_year' => SystemConfiguration::determineCurrentAcademicYear(),
            ],
        ]);
    }

    public function toggleCycle(): RedirectResponse
    {
        $config = SystemConfiguration::current();
        $config->active_cycle = $config->active_cycle === 1 ? 2 : 1;
        $config->save();

        return back()->with('success', "Siklus berhasil diubah ke Putaran Ke-{$config->active_cycle}!");
    }

    public function toggleLock(): RedirectResponse
    {
        $config = SystemConfiguration::current();
        $config->is_scheduled_locked = ! $config->is_scheduled_locked;
        $config->save();

        $statusText = $config->is_scheduled_locked ? 'Dikuci (Tidak dapat menambah/mengubah jadwal)' : 'Dibuka (Jadwal dapat diubah)';

        return back()->with('success', "Status proteksi jadwal: {$statusText}!");
    }

    /**
     * Memajukan tahun ajaran ke periode berikutnya (misal: 2026/2027 -> 2027/2028)
     */
    public function advanceAcademicYear(Request $request): RedirectResponse
    {
        $config = SystemConfiguration::current();
        $currentYear = $config->academic_year ?? '2026/2027';

        if ($request->filled('target_year')) {
            $newYear = $request->input('target_year');
        } else {
            if (preg_match('/(\d{4})\/(\d{4})/', $currentYear, $m)) {
                $start = (int)$m[1] + 1;
                $end = (int)$m[2] + 1;
                $newYear = "{$start}/{$end}";
            } else {
                $newYear = '2027/2028';
            }
        }

        $config->academic_year = $newYear;
        $config->save();

        return back()->with('success', "Tahun ajaran berhasil diperbarui ke {$newYear}!");
    }
}
