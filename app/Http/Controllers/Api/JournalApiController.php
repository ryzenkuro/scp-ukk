<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use App\Models\LeaveRequest;
use App\Models\Schedule;
use App\Models\SystemConfiguration;
use App\Models\User;
use App\Services\AttendanceService;
use App\Support\IndonesianDay;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JournalApiController extends Controller
{
    /**
     * Dapatkan daftar jadwal mengajar guru pada hari ini / siklus tertentu
     */
    public function schedules(Request $request): JsonResponse
    {
        $teacher = $request->user();
        $config = SystemConfiguration::current();
        $selectedCycle = $request->query('cycle', $config->active_cycle);
        $selectedDay = $request->query('day'); // jika null, tampilkan semua hari atau hari ini
        $today = now()->toDateString();

        $query = Schedule::with(['schoolClass', 'room', 'teacher', 'journals' => function ($q) use ($today) {
            $q->where('date', $today);
        }])
            ->where('cycle_type', $selectedCycle);

        // Jika bukan waka_kurikulum, batasi jadwal hanya untuk guru bersangkutan
        if ($teacher->role?->role_name !== 'waka_kurikulum') {
            $query->where('teacher_id', $teacher->id);
        } elseif ($request->has('teacher_id')) {
            $query->where('teacher_id', $request->query('teacher_id'));
        }

        if ($selectedDay) {
            $query->where('day_of_week', $selectedDay);
        }

        $schedules = $query
            ->orderByRaw("FIELD(day_of_week, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu')")
            ->orderBy('start_time')
            ->get()
            ->map(function ($schedule) use ($today) {
                $todayJournal = $schedule->journals->firstWhere('date', $today);
                return [
                    'id'           => $schedule->id,
                    'subject'      => $schedule->subject,
                    'teacher_name' => $schedule->teacher?->fullname ?? 'Guru Pengampu',
                    'day_of_week'  => $schedule->day_of_week,
                    'start_time'   => substr($schedule->start_time, 0, 5),
                    'end_time'     => substr($schedule->end_time, 0, 5),
                    'cycle_type'   => $schedule->cycle_type,
                    'class_id'     => $schedule->class_id,
                    'class_name'   => $schedule->schoolClass?->class_name,
                    'room_id'      => $schedule->room_id,
                    'room_name'    => $schedule->room?->room_name,
                    'has_journal'  => (bool) $todayJournal,
                    'journal_id'   => $todayJournal?->id,
                ];
            });

        return response()->json([
            'status'             => 'success',
            'active_cycle'       => $config->active_cycle,
            'selected_cycle'     => $selectedCycle,
            'current_day'        => IndonesianDay::now(),
            'is_schedule_locked' => (bool) $config->is_schedule_locked,
            'data'               => $schedules,
        ]);
    }

    /**
     * Dapatkan daftar siswa rombel dan status izin approved hari ini untuk form presensi
     */
    public function scheduleStudents(Request $request, Schedule $schedule): JsonResponse
    {
        $teacher = $request->user();
        if ($schedule->teacher_id !== $teacher->id && $teacher->role?->role_name !== 'waka_kurikulum') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Anda tidak berhak mengakses jadwal guru lain.',
            ], 403);
        }

        $today = now()->toDateString();

        // Ambil seluruh siswa di rombel jadwal ini
        $students = User::where('class_id', $schedule->class_id)
            ->whereHas('role', fn($q) => $q->where('role_name', 'siswa'))
            ->orderBy('fullname')
            ->get();

        // Cek izin approved hari ini
        $approvedLeaves = LeaveRequest::whereIn('student_id', $students->pluck('id'))
            ->where('status_approval', 'Approved')
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->get()
            ->keyBy('student_id');

        $studentData = $students->map(function ($s) use ($approvedLeaves) {
            $leave = $approvedLeaves->get($s->id);
            $defaultStatus = 'Hadir';
            $leaveInfo = null;

            if ($leave) {
                $isSakit = ($leave->leave_type === 'Sakit' || str_contains(strtolower($leave->reason), 'sakit'));
                $defaultStatus = $isSakit ? 'Sakit' : 'Izin';
                $leaveInfo = [
                    'leave_type' => $leave->leave_type,
                    'reason'     => $leave->reason,
                ];
            }

            return [
                'id'             => $s->id,
                'username'       => $s->username,
                'fullname'       => $s->fullname,
                'nisn'           => $s->nisn_nip,
                'default_status' => $defaultStatus,
                'is_approved_leave' => (bool) $leave,
                'leave_info'     => $leaveInfo,
            ];
        });

        return response()->json([
            'status'     => 'success',
            'schedule'   => [
                'id'         => $schedule->id,
                'subject'    => $schedule->subject,
                'class_name' => $schedule->schoolClass?->class_name,
                'room_name'  => $schedule->room?->room_name,
                'day_of_week'=> $schedule->day_of_week,
                'start_time' => substr($schedule->start_time, 0, 5),
                'end_time'   => substr($schedule->end_time, 0, 5),
            ],
            'date'       => $today,
            'total'      => $studentData->count(),
            'students'   => $studentData,
        ]);
    }

    /**
     * Simpan jurnal mengajar KBM TeFa dan rekam presensi kelas secara atomik
     */
    public function store(Request $request, AttendanceService $attendanceService): JsonResponse
    {
        $teacher = $request->user();

        $validated = $request->validate([
            'schedule_id'       => 'required|exists:schedules,id',
            'topic_description' => 'required|string|min:5|max:2000',
            'photo'             => 'required|image|mimes:jpg,jpeg,png,webp|max:3072',
            'attendances'       => 'required|array',
            'attendances.*'     => 'required|in:Hadir,Izin,Sakit,Alpha',
        ], [
            'topic_description.required' => 'Materi pembelajaran KBM TeFa wajib diisi.',
            'topic_description.min'      => 'Materi pembelajaran minimal 5 karakter.',
            'photo.required'             => 'Foto bukti kegiatan kelas TeFa wajib diunggah.',
            'photo.image'                => 'Berkas bukti harus berupa gambar.',
        ]);

        $schedule = Schedule::findOrFail($validated['schedule_id']);

        if ($schedule->teacher_id !== $teacher->id && $teacher->role?->role_name !== 'waka_kurikulum') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Anda tidak memiliki hak akses untuk jadwal ini.',
            ], 403);
        }

        $today = now()->toDateString();

        // Cek duplikasi entri
        $alreadyCreated = Journal::where('schedule_id', $schedule->id)
            ->where('date', $today)
            ->exists();

        if ($alreadyCreated) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Jurnal untuk kelas dan jadwal ini sudah pernah disimpan hari ini.',
            ], 422);
        }

        $journal = null;

        DB::transaction(function () use ($request, $schedule, $today, $attendanceService, &$journal) {
            // 1. Simpan foto bukti ke public disk
            $photoPath = $request->file('photo')->store('journals_photos', 'public');

            // 2. Simpan entri Jurnal
            $journal = Journal::create([
                'schedule_id'       => $schedule->id,
                'date'              => $today,
                'topic_description' => $request->topic_description,
                'photo_path'        => $photoPath,
            ]);

            // 3. Rekam presensi via service
            $attendanceService->recordAttendance($journal, $request->attendances, $today);
        });

        $journal->load(['schedule.schoolClass', 'schedule.room', 'attendances.student']);

        return response()->json([
            'status'  => 'success',
            'message' => 'Jurnal mengajar dan presensi kelas berhasil disimpan!',
            'data'    => [
                'id'                => $journal->id,
                'date'              => $journal->date,
                'topic_description' => $journal->topic_description,
                'photo_url'         => asset('storage/' . $journal->photo_path),
                'class_name'        => $journal->schedule?->schoolClass?->class_name,
                'subject'           => $journal->schedule?->subject,
                'total_students'    => $journal->attendances->count(),
            ],
        ], 201);
    }

    /**
     * Dapatkan riwayat jurnal mengajar guru
     */
    public function index(Request $request): JsonResponse
    {
        $teacher = $request->user();

        $query = Journal::with(['schedule.schoolClass', 'schedule.room', 'schedule.teacher', 'attendances']);

        // Jika bukan waka_kurikulum, batasi riwayat jurnal hanya untuk guru bersangkutan
        if ($teacher->role?->role_name !== 'waka_kurikulum') {
            $query->whereHas('schedule', fn($q) => $q->where('teacher_id', $teacher->id));
        }

        $journals = $query->latest('date')
            ->latest('id')
            ->paginate(15);

        return response()->json([
            'status' => 'success',
            'data'   => $journals,
        ]);
    }

    /**
     * Detail jurnal beserta rekapan presensi siswa
     */
    public function show(Request $request, Journal $journal): JsonResponse
    {
        $teacher = $request->user();
        if ($journal->schedule->teacher_id !== $teacher->id && $teacher->role?->role_name !== 'waka_kurikulum') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Anda tidak berhak melihat jurnal guru lain.',
            ], 403);
        }

        $journal->load(['schedule.schoolClass', 'schedule.room', 'attendances.student']);

        $attendances = $journal->attendances->map(function ($att) {
            return [
                'id'         => $att->id,
                'student_id' => $att->student_id,
                'fullname'   => $att->student?->fullname,
                'username'   => $att->student?->username,
                'status'     => $att->status,
            ];
        });

        $stats = [
            'total' => $attendances->count(),
            'hadir' => $attendances->where('status', 'Hadir')->count(),
            'izin'  => $attendances->where('status', 'Izin')->count(),
            'sakit' => $attendances->where('status', 'Sakit')->count(),
            'alpha' => $attendances->where('status', 'Alpha')->count(),
        ];

        return response()->json([
            'status' => 'success',
            'data'   => [
                'id'                => $journal->id,
                'date'              => $journal->date,
                'topic_description' => $journal->topic_description,
                'photo_url'         => asset('storage/' . $journal->photo_path),
                'subject'           => $journal->schedule?->subject,
                'class_name'        => $journal->schedule?->schoolClass?->class_name,
                'room_name'         => $journal->schedule?->room?->room_name,
                'stats'             => $stats,
                'attendances'       => $attendances,
            ],
        ]);
    }
}

