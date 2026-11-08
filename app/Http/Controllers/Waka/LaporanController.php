<?php

namespace App\Http\Controllers\Waka;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Journal;
use App\Models\LeaveRequest;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Siswa;
use App\Models\SystemConfiguration;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LaporanController extends Controller
{
    /**
     * Tampilkan rekapitulasi presensi siswa per kelas & filter tanggal (Format Cetak A4)
     */
    public function presensi(Request $request): Response
    {
        $config = SystemConfiguration::current();

        $kelasList = SchoolClass::orderBy('name')->pluck('name');
        if ($kelasList->isEmpty()) {
            $kelasList = Siswa::distinct()->pluck('kelas')->filter();
        }

        $selectedKelas = $request->input('kelas', $kelasList->first() ?? 'X RPL 1');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Ambil seluruh siswa aktif di kelas yang dipilih
        $students = Siswa::where('kelas', $selectedKelas)
            ->where('status', 'aktif')
            ->orderBy('name')
            ->get();

        // Ambil data presensi untuk siswa-siswa tersebut
        $attQuery = Attendance::whereIn('student_id', $students->pluck('id'));

        if ($startDate) {
            $attQuery->whereDate('date', '>=', $startDate);
        }
        if ($endDate) {
            $attQuery->whereDate('date', '<=', $endDate);
        }

        $allAttendances = $attQuery->get();

        $rekapData = [];
        $totalHadirAll = 0;
        $totalSakitAll = 0;
        $totalIzinAll = 0;
        $totalAlpaAll = 0;

        foreach ($students as $student) {
            $studentAtts = $allAttendances->where('student_id', $student->id);
            $h = $studentAtts->where('status', 'hadir')->count();
            $s = $studentAtts->where('status', 'sakit')->count();
            $i = $studentAtts->where('status', 'izin')->count();
            $a = $studentAtts->where('status', 'alpa')->count();
            $totalPertemuan = $studentAtts->count();

            $persentase = $totalPertemuan > 0 ? round(($h / $totalPertemuan) * 100, 1) : 100.0;

            $totalHadirAll += $h;
            $totalSakitAll += $s;
            $totalIzinAll += $i;
            $totalAlpaAll += $a;

            $rekapData[] = [
                'id' => $student->id,
                'nis' => $student->nis,
                'name' => $student->name,
                'gender' => $student->gender,
                'hadir' => $h,
                'sakit' => $s,
                'izin' => $i,
                'alpa' => $a,
                'total' => $totalPertemuan,
                'persentase' => $persentase,
            ];
        }

        $totalKBMAll = $totalHadirAll + $totalSakitAll + $totalIzinAll + $totalAlpaAll;
        $classRate = $totalKBMAll > 0 ? round(($totalHadirAll / $totalKBMAll) * 100, 1) : 100.0;

        return Inertia::render('Waka/Laporan/Presensi', [
            'kelasList' => $kelasList,
            'selectedKelas' => $selectedKelas,
            'filters' => [
                'kelas' => $selectedKelas,
                'start_date' => $startDate ?? '',
                'end_date' => $endDate ?? '',
            ],
            'rekapData' => $rekapData,
            'summary' => [
                'total_students' => count($students),
                'total_hadir' => $totalHadirAll,
                'total_sakit' => $totalSakitAll,
                'total_izin' => $totalIzinAll,
                'total_alpa' => $totalAlpaAll,
                'class_attendance_rate' => $classRate,
            ],
            'config' => [
                'academic_year' => $config->academic_year ?? '2026/2027',
                'active_cycle' => $config->active_cycle ?? 1,
            ],
            'todayDate' => now()->translatedFormat('d F Y'),
        ]);
    }

    /**
     * Tampilkan rekap permohonan izin & verifikasi (Waka / Walas)
     */
    public function izin(Request $request): Response
    {
        $status = $request->input('status', 'all');
        $type = $request->input('type', 'all');
        $search = $request->input('search');

        $query = LeaveRequest::with(['student', 'verifier'])
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
                   ->orWhere('nis', 'like', "%{$search}%")
                   ->orWhere('kelas', 'like', "%{$search}%");
            });
        }

        $leaves = $query->paginate(15)->withQueryString();

        return Inertia::render('Waka/Laporan/Izin', [
            'leaves' => $leaves,
            'filters' => [
                'status' => $status,
                'type' => $type,
                'search' => $search ?? '',
            ],
        ]);
    }

    /**
     * Verifikasi surat izin siswa (Setujui / Tolak)
     */
    public function verifyIzin(Request $request, LeaveRequest $leave): RedirectResponse
    {
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

        $statusText = $validated['status'] === 'approved' ? 'disetujui' : 'ditolak';

        return back()->with('success', "Permohonan izin siswa {$leave->student?->name} berhasil {$statusText}.");
    }

    /**
     * Rekapitulasi Audit Kinerja Mengajar & Jurnal Seluruh Guru
     */
    public function guru(Request $request): Response
    {
        $config = SystemConfiguration::current();
        $period = $request->input('period', 'bulan'); // hari, minggu, bulan, all
        $referenceDateStr = $request->input('date', now()->toDateString());
        $referenceDate = Carbon::parse($referenceDateStr);

        $reportStart = match ($period) {
            'hari' => $referenceDate->copy()->startOfDay(),
            'minggu' => $referenceDate->copy()->startOfWeek(),
            'bulan' => $referenceDate->copy()->startOfMonth(),
            default => Carbon::create(2026, 1, 1)->startOfDay(),
        };

        $reportEnd = match ($period) {
            'hari' => $referenceDate->copy()->endOfDay(),
            'minggu' => $referenceDate->copy()->endOfWeek(),
            'bulan' => $referenceDate->copy()->endOfMonth(),
            default => now()->endOfDay(),
        };

        $gurus = User::whereIn('role_id', [2, 5])
            ->where('is_active', true)
            ->orderBy('fullname')
            ->get();

        $activeCycle = $config->active_cycle;

        $rekapGuru = [];
        $totalJadwalAll = 0;
        $totalJurnalAll = 0;

        foreach ($gurus as $g) {
            $totalJadwal = Schedule::where('teacher_id', $g->id)
                ->where('cycle_number', $activeCycle)
                ->count();

            $journalQuery = Journal::where('teacher_id', $g->id);
            if ($period !== 'all') {
                $journalQuery->whereBetween('date', [$reportStart->toDateString(), $reportEnd->toDateString()]);
            }
            $totalJurnal = $journalQuery->count();

            $totalJadwalAll += $totalJadwal;
            $totalJurnalAll += $totalJurnal;

            $persentase = $totalJadwal > 0 ? min(100.0, round(($totalJurnal / $totalJadwal) * 100, 1)) : 100.0;

            $rekapGuru[] = [
                'id' => $g->id,
                'username' => $g->username,
                'fullname' => $g->fullname ?? $g->username,
                'nip' => $g->phone ?? '-',
                'total_jadwal' => $totalJadwal,
                'total_jurnal' => $totalJurnal,
                'persentase' => $persentase,
                'status_lengkap' => $totalJurnal >= $totalJadwal,
            ];
        }

        return Inertia::render('Waka/Laporan/Guru', [
            'rekapGuru' => $rekapGuru,
            'period' => $period,
            'referenceDate' => $referenceDateStr,
            'reportStart' => $reportStart->format('d/m/Y'),
            'reportEnd' => $reportEnd->format('d/m/Y'),
            'summary' => [
                'total_guru' => count($gurus),
                'total_jadwal' => $totalJadwalAll,
                'total_jurnal' => $totalJurnalAll,
                'active_cycle' => $activeCycle,
            ],
        ]);
    }

    /**
     * Detail drill-down jurnal KBM seorang guru
     */
    public function guruDetail(Request $request, User $guru): Response
    {
        abort_unless(in_array((int) $guru->role_id, [2, 5], true), 404);

        $journals = Journal::with(['schedule.schoolClass', 'attendances'])
            ->where('teacher_id', $guru->id)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $schedules = Schedule::with(['schoolClass', 'room', 'startSlot', 'endSlot'])
            ->where('teacher_id', $guru->id)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return Inertia::render('Waka/Laporan/GuruDetail', [
            'guru' => [
                'id' => $guru->id,
                'username' => $guru->username,
                'fullname' => $guru->fullname ?? $guru->username,
                'email' => $guru->email,
                'phone' => $guru->phone,
            ],
            'journals' => $journals,
            'schedules' => $schedules,
            'totalJournals' => $journals->count(),
            'totalSchedules' => $schedules->count(),
        ]);
    }
}

