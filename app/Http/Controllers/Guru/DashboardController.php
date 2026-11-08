<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Journal;
use App\Models\Schedule;
use App\Models\Siswa;
use App\Models\SystemConfiguration;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $teacher = auth()->user();
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

        // Jadwal mengajar guru hari ini berdasarkan hari dan siklus putaran aktif
        $todaySchedules = Schedule::with(['schoolClass', 'room', 'startSlot', 'endSlot'])
            ->where('teacher_id', $teacher->id)
            ->where('day_of_week', $currentDay)
            ->where('cycle_number', $config->active_cycle)
            ->orderBy('start_time')
            ->get();

        // 5 Jurnal terbaru yang diisi guru
        $recentJournals = Journal::with(['schedule.schoolClass', 'attendances'])
            ->where('teacher_id', $teacher->id)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->take(5)
            ->get();

        // Metrik statistik guru
        $totalJournals = Journal::where('teacher_id', $teacher->id)->count();
        $totalSchedules = Schedule::where('teacher_id', $teacher->id)->count();

        $classesTaught = Schedule::where('teacher_id', $teacher->id)->distinct()->pluck('class_name');
        $totalStudents = Siswa::whereIn('kelas', $classesTaught)->where('status', 'aktif')->count();

        return Inertia::render('Guru/Dashboard', [
            'todaySchedules' => $todaySchedules,
            'recentJournals' => $recentJournals,
            'currentDay' => $currentDay,
            'stats' => [
                'total_journals' => $totalJournals,
                'total_schedules' => $totalSchedules,
                'total_students' => $totalStudents,
            ],
            'config' => [
                'active_cycle' => $config->active_cycle,
                'academic_year' => $config->academic_year,
            ],
        ]);
    }

    /**
     * Cetak jadwal mengajar guru bersangkutan (A4 Landscape)
     */
    public function exportPdf(Request $request)
    {
        $teacher = auth()->user();
        $config = SystemConfiguration::current();
        $activeCycle = $request->filled('cycle') && in_array($request->cycle, ['1', '2'])
            ? (int) $request->cycle
            : $config->active_cycle;

        $jadwals = Schedule::with(['schoolClass', 'room', 'startSlot', 'endSlot', 'teacher'])
            ->where('teacher_id', $teacher->id)
            ->where('cycle_number', $activeCycle)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return view('pdf.jadwal', [
            'documentTitle' => 'JADWAL MENGAJAR GURU',
            'pageTitle' => 'Jadwal Mengajar - ' . ($teacher->fullname ?? $teacher->username),
            'academicYear' => $config->academic_year,
            'activeCycle' => $activeCycle,
            'filterTeacherName' => $teacher->fullname ?? $teacher->username,
            'jadwals' => $jadwals,
        ]);
    }
}
