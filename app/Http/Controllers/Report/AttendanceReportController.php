<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceReportController extends Controller
{
    /**
     * Menampilkan halaman filter dan tabel rekapitulasi kehadiran siswa.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $isWaliKelas = $user && $user->role?->role_name === 'wali_kelas';

        // Logika Batas Akses:
        // - Wali Kelas HANYA melihat laporan presensi kelas perwaliannya sendiri (terkunci ketat)
        // - Guru BK, Waka Kurikulum, dan Guru dapat memantau dan memfilter seluruh kelas
        if ($isWaliKelas) {
            $classes = $user->class_id ? SchoolClass::where('id', $user->class_id)->get() : collect();
            $selectedClassId = $user->class_id;
        } else {
            $classes = SchoolClass::orderBy('class_name')->get();
            $selectedClassId = $request->query('class_id', $classes->first()?->id);
        }

        $startDate = $request->query('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', now()->endOfMonth()->toDateString());

        $selectedClass = $classes->firstWhere('id', (int) $selectedClassId);
        $reportData = $this->calculateRecap($selectedClassId ? (int) $selectedClassId : null, $startDate, $endDate);

        // Agregasi statistik rekapitulasi kelas
        $totalStudents = count($reportData);
        $totalH = array_sum(array_column($reportData, 'h'));
        $totalS = array_sum(array_column($reportData, 's'));
        $totalI = array_sum(array_column($reportData, 'i'));
        $totalA = array_sum(array_column($reportData, 'a'));
        $totalAttendances = $totalH + $totalS + $totalI + $totalA;
        $averagePercentage = $totalStudents > 0
            ? round(array_sum(array_column($reportData, 'percentage')) / $totalStudents, 1)
            : 0.0;

        $summary = [
            'total_students' => $totalStudents,
            'total_h' => $totalH,
            'total_s' => $totalS,
            'total_i' => $totalI,
            'total_a' => $totalA,
            'total_records' => $totalAttendances,
            'average_percentage' => $averagePercentage,
        ];

        return view('reports.attendance_index', compact(
            'classes',
            'selectedClassId',
            'selectedClass',
            'startDate',
            'endDate',
            'reportData',
            'summary'
        ));
    }

    /**
     * Menampilkan dokumen cetak resmi A4 lengkap dengan kop surat sekolah dan tanda tangan ganda.
     */
    public function print(Request $request): View
    {
        $user = $request->user();
        $isWaliKelas = $user && $user->role?->role_name === 'wali_kelas';

        if ($isWaliKelas) {
            $classId = $user->class_id;
            $requestedClassId = $request->query('class_id');
            if ($requestedClassId && (int) $requestedClassId !== (int) $user->class_id) {
                abort(403, 'Wali kelas hanya berhak mencetak laporan presensi kelas perwaliannya sendiri.');
            }
        } else {
            $classes = SchoolClass::orderBy('class_name')->get();
            $classId = $request->query('class_id', $classes->first()?->id);
        }

        $startDate = $request->query('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', now()->endOfMonth()->toDateString());

        $schoolClass = SchoolClass::findOrFail($classId);
        $reportData = $this->calculateRecap((int) $classId, $startDate, $endDate);

        $waka = User::whereHas('role', fn ($q) => $q->where('role_name', 'waka_kurikulum'))->first();
        $waliKelas = User::where('class_id', $schoolClass->id)
            ->whereHas('role', fn ($q) => $q->where('role_name', 'wali_kelas'))
            ->first();

        $totalStudents = count($reportData);
        $totalH = array_sum(array_column($reportData, 'h'));
        $totalS = array_sum(array_column($reportData, 's'));
        $totalI = array_sum(array_column($reportData, 'i'));
        $totalA = array_sum(array_column($reportData, 'a'));
        $totalAttendances = $totalH + $totalS + $totalI + $totalA;
        $averagePercentage = $totalStudents > 0
            ? round(array_sum(array_column($reportData, 'percentage')) / $totalStudents, 1)
            : 0.0;

        $summary = [
            'total_students' => $totalStudents,
            'total_h' => $totalH,
            'total_s' => $totalS,
            'total_i' => $totalI,
            'total_a' => $totalA,
            'total_records' => $totalAttendances,
            'average_percentage' => $averagePercentage,
        ];

        return view('reports.attendance_print', compact(
            'schoolClass',
            'startDate',
            'endDate',
            'reportData',
            'summary',
            'waka',
            'waliKelas'
        ));
    }

    /**
     * Menghitung akumulasi kehadiran siswa berdasarkan rumus baku kurikulum:
     * Persentase = (Hadir / Total Pertemuan) * 100%
     */
    public function calculateRecap(?int $classId, string $startDate, string $endDate): array
    {
        if (! $classId) {
            return [];
        }

        $students = User::where('class_id', $classId)
            ->whereHas('role', fn ($q) => $q->where('role_name', 'siswa'))
            ->orderBy('fullname')
            ->get();

        return $students->map(function ($student) use ($startDate, $endDate) {
            $attendances = Attendance::where('student_id', $student->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->get();

            $h = $attendances->where('status', 'Hadir')->count();
            $s = $attendances->where('status', 'Sakit')->count();
            $i = $attendances->where('status', 'Izin')->count();
            $a = $attendances->where('status', 'Alpha')->count();
            $total = $h + $s + $i + $a;
            $percentage = $total > 0 ? round(($h / $total) * 100, 1) : 0.0;

            return [
                'student' => $student,
                'h' => $h,
                's' => $s,
                'i' => $i,
                'a' => $a,
                'total' => $total,
                'percentage' => $percentage,
            ];
        })->toArray();
    }
}
