<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\SystemConfiguration;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SharedJadwalSekolahController extends Controller
{
    /**
     * Tampilkan Jadwal Sekolah Terpadu dengan format per-hari dan Jam Ke-1 s.d. Ke-10
     * Dapat diakses oleh Waka, Guru, Wali Kelas, dan Guru BK
     */
    public function index(Request $request): Response
    {
        $user = auth()->user();
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

        // Daftar seluruh kelas dan guru
        $classes = SchoolClass::orderBy('name')->get(['id', 'name', 'grade', 'jurusan']);
        $teachers = User::where(function ($q) {
            $q->whereIn('role_id', [2, 4, 5])
                ->orWhereHas('role', function ($rq) {
                    $rq->whereIn('name', ['guru', 'wali_kelas', 'guru_bk', 'kesiswaan']);
                });
        })
            ->where('is_active', true)
            ->orderBy('fullname')
            ->get(['id', 'fullname', 'username']);

        // Deteksi kelas binaan jika user adalah wali kelas
        $assignedClasses = [];
        if ($user && method_exists($user, 'getWaliClasses')) {
            $assignedClasses = $user->getWaliClasses();
        }

        // Tentukan mode tinjauan: 'class' (default) atau 'teacher'
        $mode = $request->input('mode', 'class');
        $cycle = $request->input('cycle', (string) $config->active_cycle);

        // Nilai default filter kelas
        $defaultClass = ! empty($assignedClasses) ? $assignedClasses[0] : ($classes->first()?->name ?? 'X PPLG 1');
        $selectedClass = $request->input('class_name', $defaultClass);

        // Nilai default filter guru
        $defaultTeacherId = ($user && in_array($user->role?->name, ['guru', 'wali_kelas', 'guru_bk']))
            ? (string) $user->id
            : ($teachers->first()?->id ? (string) $teachers->first()->id : '');
        $selectedTeacherId = $request->input('teacher_id', $defaultTeacherId);

        // Pengurutan hari kalender sekolah (Senin -> Jumat)
        $driver = DB::connection()->getDriverName();
        $dayOrderRaw = ($driver === 'mysql' || $driver === 'mariadb')
            ? "FIELD(day_of_week, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat')"
            : "CASE day_of_week WHEN 'Senin' THEN 1 WHEN 'Selasa' THEN 2 WHEN 'Rabu' THEN 3 WHEN 'Kamis' THEN 4 WHEN 'Jumat' THEN 5 ELSE 6 END";

        $query = Schedule::with(['teacher', 'schoolClass', 'room', 'startSlot', 'endSlot'])
            ->orderByRaw($dayOrderRaw)
            ->orderBy('start_time');

        if ($cycle !== 'all') {
            $query->where('cycle_number', (int) $cycle);
        }

        if ($mode === 'teacher' && ! empty($selectedTeacherId)) {
            $query->where('teacher_id', (int) $selectedTeacherId);
        } else {
            $query->where('class_name', $selectedClass);
        }

        $schedules = $query->get();
        $timeSlots = TimeSlot::orderBy('order')->get();

        $canManage = $user && ($user->isWaka() || (int) $user->role_id === 1);

        return Inertia::render('Jadwal/JadwalSekolahViewer', [
            'schedules' => $schedules,
            'timeSlots' => $timeSlots,
            'todayName' => $todayName,
            'classes' => $classes,
            'teachers' => $teachers,
            'assignedClasses' => $assignedClasses,
            'canManage' => $canManage,
            'filters' => [
                'mode' => $mode,
                'class_name' => $selectedClass,
                'teacher_id' => $selectedTeacherId,
                'cycle' => $cycle,
            ],
            'config' => [
                'active_cycle' => $config->active_cycle,
                'academic_year' => $config->academic_year,
                'ceremony_cycle_1' => (bool) $config->ceremony_cycle_1,
                'ceremony_cycle_2' => (bool) $config->ceremony_cycle_2,
            ],
        ]);
    }
}
