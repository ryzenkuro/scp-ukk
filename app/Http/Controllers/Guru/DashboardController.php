<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use App\Models\Schedule;
use App\Models\Siswa;
use App\Models\SystemConfiguration;
use App\Models\TimeSlot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $rawTodaySchedules = Schedule::with(['schoolClass', 'room', 'startSlot', 'endSlot'])
            ->where('teacher_id', $teacher->id)
            ->where('day_of_week', $currentDay)
            ->where('cycle_number', $config->active_cycle)
            ->orderBy('start_time')
            ->get();

        $todayDate = now()->toDateString();
        $now = now();

        $todaySchedules = $rawTodaySchedules->map(function ($s) use ($todayDate, $now) {
            $journal = Journal::where('schedule_id', $s->id)
                ->whereDate('date', $todayDate)
                ->first();

            $startTime = \Carbon\Carbon::createFromTimeString($s->start_time);
            $endTime = \Carbon\Carbon::createFromTimeString($s->end_time);
            $graceEnd = $endTime->copy()->addMinutes(30);

            $timeStatus = 'upcoming';
            $remainingGrace = null;

            $canEdit = false;
            if ($journal) {
                $timeStatus = 'filled';
                $canEdit = $now->lte($graceEnd);
            } elseif ($now->lt($startTime)) {
                $timeStatus = 'upcoming';
            } elseif ($now->lte($endTime)) {
                $timeStatus = 'ongoing';
            } elseif ($now->lte($graceEnd)) {
                $timeStatus = 'grace_period';
                $remainingGrace = max(1, (int) round($now->diffInSeconds($graceEnd, false) / 60));
            } else {
                $timeStatus = 'missed';
            }

            return array_merge($s->toArray(), [
                'is_filled_today' => (bool) $journal,
                'journal_id' => $journal?->id,
                'time_status' => $timeStatus,
                'remaining_grace_minutes' => $remainingGrace,
                'can_edit' => $canEdit,
            ]);
        });

        // Deteksi apakah ada jadwal yang sedang aktif (ongoing / grace_period) dan belum diisi
        $activeSchedule = $todaySchedules->first(fn ($s) => in_array($s['time_status'], ['ongoing', 'grace_period']));

        $isWaka = $teacher->isWaka() || $teacher->role?->name === 'waka';

        // 5 Jurnal terbaru yang diisi guru
        $recentJournals = Journal::with(['schedule.schoolClass', 'schedule.room', 'attendances.student'])
            ->where('teacher_id', $teacher->id)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->take(5)
            ->get()
            ->map(function ($j) use ($todayDate, $now, $isWaka) {
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

        // Metrik statistik guru
        $totalJournals = Journal::where('teacher_id', $teacher->id)->count();
        $totalSchedules = Schedule::where('teacher_id', $teacher->id)->count();

        $classesTaught = Schedule::where('teacher_id', $teacher->id)->distinct()->pluck('class_name');
        $totalStudents = Siswa::whereIn('kelas', $classesTaught)->where('status', 'aktif')->count();

        return Inertia::render('Guru/Dashboard', [
            'todaySchedules' => $todaySchedules,
            'activeSchedule' => $activeSchedule,
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
                'ceremony_cycle_1' => (bool) $config->ceremony_cycle_1,
                'ceremony_cycle_2' => (bool) $config->ceremony_cycle_2,
            ],
        ]);
    }

    /**
     * Tampilkan jadwal mengajar guru bersangkutan (berbasis hari & Jam Ke-1 s.d. Ke-10)
     */
    public function jadwal(Request $request): Response
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
        $todayName = $dayMap[date('l')] ?? 'Senin';

        $cycle = $request->input('cycle', (string) $config->active_cycle);

        $driver = DB::connection()->getDriverName();
        $dayOrderRaw = ($driver === 'mysql' || $driver === 'mariadb')
            ? "FIELD(day_of_week, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat')"
            : "CASE day_of_week WHEN 'Senin' THEN 1 WHEN 'Selasa' THEN 2 WHEN 'Rabu' THEN 3 WHEN 'Kamis' THEN 4 WHEN 'Jumat' THEN 5 ELSE 6 END";

        $query = Schedule::with(['schoolClass', 'room', 'startSlot', 'endSlot'])
            ->where('teacher_id', $teacher->id)
            ->orderByRaw($dayOrderRaw)
            ->orderBy('start_time');

        if ($cycle !== 'all') {
            $query->where('cycle_number', (int) $cycle);
        }

        $schedules = $query->get();
        $timeSlots = TimeSlot::orderBy('order')->get();

        return Inertia::render('Guru/Jadwal', [
            'teacher' => [
                'id' => $teacher->id,
                'name' => $teacher->fullname ?: $teacher->name ?: $teacher->username,
                'nip' => $teacher->nip,
            ],
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

        $hasCeremony = ($activeCycle === 2) ? (bool) $config->ceremony_cycle_2 : (bool) $config->ceremony_cycle_1;

        return view('pdf.jadwal', [
            'documentTitle' => 'JADWAL MENGAJAR GURU',
            'pageTitle' => 'Jadwal Mengajar - '.($teacher->fullname ?? $teacher->username),
            'academicYear' => $config->academic_year,
            'activeCycle' => $activeCycle,
            'filterTeacherName' => $teacher->fullname ?? $teacher->username,
            'jadwals' => $jadwals,
            'hasCeremony' => $hasCeremony,
        ]);
    }
}
