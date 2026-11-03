<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Schedule;
use App\Models\SystemConfiguration;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentPortalApiController extends Controller
{
    /**
     * Dapatkan siswa terkait berdasarkan peran (siswa, orang tua, wali kelas, guru bk, waka kurikulum)
     */
    private function resolveStudent(Request $request): ?User
    {
        $user = $request->user();

        if ($user->role?->role_name === 'orang_tua') {
            // Jika ada query student_id dan anak tersebut memang milik ortu ini
            if ($request->has('student_id')) {
                $child = User::with('schoolClass')
                    ->where('parent_id', $user->id)
                    ->where('id', $request->query('student_id'))
                    ->first();
                if ($child) return $child;
            }

            return User::with('schoolClass')->where('parent_id', $user->id)->first();
        }

        // Untuk peran pengawas (Wali Kelas, Guru BK, Waka Kurikulum)
        if (in_array($user->role?->role_name, ['wali_kelas', 'guru_bk', 'waka_kurikulum'])) {
            if ($request->has('student_id')) {
                $target = User::with('schoolClass')->find($request->query('student_id'));
                if ($target) return $target;
            }

            // Jika Wali Kelas punya class_id binaan, prioritaskan siswa di kelas binaan
            if ($user->role?->role_name === 'wali_kelas' && $user->class_id) {
                $studentInClass = User::with('schoolClass')
                    ->where('class_id', $user->class_id)
                    ->whereHas('role', fn($q) => $q->where('role_name', 'siswa'))
                    ->first();
                if ($studentInClass) return $studentInClass;
            }

            // Fallback ke siswa pertama yang terdaftar di sekolah
            return User::with('schoolClass')
                ->whereHas('role', fn($q) => $q->where('role_name', 'siswa'))
                ->first();
        }

        return User::with('schoolClass')->find($user->id);
    }

    /**
     * Jadwal blok kejuruan kelas siswa
     */
    public function schedules(Request $request): JsonResponse
    {
        $student = $this->resolveStudent($request);

        if (! $student) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data profil siswa tidak ditemukan atau belum terhubung dengan akun ini.',
            ], 404);
        }

        $config = SystemConfiguration::current();
        $daysMap = [
            'Sunday'    => 'Minggu',
            'Monday'    => 'Senin',
            'Tuesday'   => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday'  => 'Kamis',
            'Friday'    => 'Jumat',
            'Saturday'  => 'Sabtu',
        ];
        $currentDay = $daysMap[now()->format('l')] ?? 'Senin';

        $selectedCycle = $request->query('cycle', $config->active_cycle ?? 'Putaran 1');
        $selectedDay = $request->query('day', $currentDay);

        $targetClassId = $request->query('class_id', $student->class_id);

        $query = Schedule::with(['teacher', 'room', 'schoolClass'])
            ->where('cycle_type', $selectedCycle);

        if ($targetClassId) {
            $query->where('class_id', $targetClassId);
        }

        if ($selectedDay && $selectedDay !== 'Semua') {
            $query->where('day_of_week', $selectedDay);
        }

        $schedules = $query->orderBy('start_time')->get()->map(function ($s) {
            return [
                'id'          => $s->id,
                'subject'     => $s->subject,
                'teacher_name'=> $s->teacher?->fullname ?? 'Guru Pengampu',
                'room_name'   => $s->room?->room_name ?? 'Ruang Belum Ditentukan',
                'class_name'  => $s->schoolClass?->class_name ?? '',
                'day_of_week' => $s->day_of_week,
                'start_time'  => substr($s->start_time, 0, 5),
                'end_time'    => substr($s->end_time, 0, 5),
                'cycle_type'  => $s->cycle_type,
            ];
        });

        return response()->json([
            'status'         => 'success',
            'active_cycle'   => $config->active_cycle ?? 'Putaran 1',
            'selected_cycle' => $selectedCycle,
            'current_day'    => $currentDay,
            'selected_day'   => $selectedDay,
            'student'        => [
                'id'         => $student->id,
                'fullname'   => $student->fullname,
                'nisn'       => $student->nisn_nip,
                'class_name' => $student->schoolClass?->class_name ?? 'Tanpa Rombel',
            ],
            'data'           => $schedules,
        ]);
    }

    /**
     * Rekapitulasi persentase kehadiran dan riwayat presensi personal siswa
     */
    public function attendanceSummary(Request $request): JsonResponse
    {
        $student = $this->resolveStudent($request);

        if (! $student) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data profil siswa tidak ditemukan atau belum terhubung.',
            ], 404);
        }

        $attendances = Attendance::with(['journal.schedule.teacher', 'journal.schedule.schoolClass'])
            ->where('student_id', $student->id)
            ->latest('date')
            ->latest('id')
            ->get();

        $total = $attendances->count();
        $hadir = $attendances->where('status', 'Hadir')->count();
        $sakit = $attendances->where('status', 'Sakit')->count();
        $izin = $attendances->where('status', 'Izin')->count();
        $alpha = $attendances->where('status', 'Alpha')->count();

        $percentage = $total > 0 ? round(($hadir / $total) * 100, 1) : 100.0;

        $history = $attendances->take(20)->map(function ($att) {
            return [
                'id'          => $att->id,
                'date'        => $att->date,
                'status'      => $att->status,
                'subject'     => $att->journal?->schedule?->subject ?? 'Mata Pelajaran KBM',
                'topic'       => $att->journal?->topic_description ?? '',
                'teacher_name'=> $att->journal?->schedule?->teacher?->fullname ?? 'Guru Pengampu',
            ];
        });

        return response()->json([
            'status'  => 'success',
            'student' => [
                'id'         => $student->id,
                'fullname'   => $student->fullname,
                'nisn'       => $student->nisn_nip,
                'class_name' => $student->schoolClass?->class_name ?? '-',
            ],
            'stats'   => [
                'total'                 => $total,
                'hadir'                 => $hadir,
                'sakit'                 => $sakit,
                'izin'                  => $izin,
                'alpha'                 => $alpha,
                'attendance_percentage' => $percentage,
            ],
            'history' => $history,
        ]);
    }

    /**
     * Riwayat surat izin permohonan siswa
     */
    public function leaveRequests(Request $request): JsonResponse
    {
        $student = $this->resolveStudent($request);

        if (! $student) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data profil siswa tidak ditemukan.',
            ], 404);
        }

        $requests = LeaveRequest::with(['applicant', 'verifier', 'student.schoolClass'])
            ->where('student_id', $student->id)
            ->latest()
            ->get()
            ->map(function ($lr) {
                return [
                    'id'              => $lr->id,
                    'leave_type'      => $lr->leave_type,
                    'start_date'      => $lr->start_date,
                    'end_date'        => $lr->end_date,
                    'leave_time'      => $lr->leave_time ? substr($lr->leave_time, 0, 5) : null,
                    'reason'          => $lr->reason,
                    'document_url'    => asset('storage/' . $lr->document_path),
                    'status_approval' => $lr->status_approval,
                    'applicant_name'  => $lr->applicant?->fullname ?? '-',
                    'verifier_name'   => $lr->verifier?->fullname ?? '-',
                    'created_at'      => $lr->created_at?->format('Y-m-d H:i') ?? '',
                ];
            });

        return response()->json([
            'status'  => 'success',
            'student' => [
                'id'         => $student->id,
                'fullname'   => $student->fullname,
                'nisn'       => $student->nisn_nip,
                'class_name' => $student->schoolClass?->class_name ?? '-',
            ],
            'data'    => $requests,
        ]);
    }

    /**
     * Ajukan surat permohonan izin baru dengan unggah berkas foto lampiran
     */
    public function storeLeaveRequest(Request $request): JsonResponse
    {
        $user = $request->user();
        $student = $this->resolveStudent($request);

        if (! $student) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Akun Anda belum terhubung dengan data profil siswa.',
            ], 422);
        }

        $validated = $request->validate([
            'leave_type' => 'required|in:Sakit,Izin,Pulang Cepat,Kegiatan Luar',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'leave_time' => 'nullable|date_format:H:i',
            'reason'     => 'required|string|max:1000',
            'document'   => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ], [
            'leave_type.required' => 'Jenis perizinan wajib dipilih.',
            'leave_type.in'       => 'Jenis perizinan harus salah satu dari: Sakit, Izin, Pulang Cepat, atau Kegiatan Luar.',
            'start_date.required' => 'Tanggal mulai izin wajib diisi.',
            'end_date.required'   => 'Tanggal selesai izin wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal selesai izin tidak boleh sebelum tanggal mulai.',
            'reason.required'     => 'Keterangan alasan izin wajib diisi.',
            'document.required'   => 'Surat bukti (surat dokter / keterangan) wajib diunggah.',
            'document.mimes'      => 'Format berkas bukti harus berupa gambar (JPG, PNG) atau PDF.',
            'document.max'        => 'Ukuran berkas bukti maksimal adalah 2MB.',
        ]);

        // Proteksi Anti-Overlap Tanggal Izin Aktif Siswa (KF53-KF56 / FASE 08)
        $hasOverlap = LeaveRequest::where('student_id', $student->id)
            ->whereIn('status_approval', ['Pending', 'Approved'])
            ->where('start_date', '<=', $validated['end_date'])
            ->where('end_date', '>=', $validated['start_date'])
            ->exists();

        if ($hasOverlap) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Siswa sudah memiliki pengajuan izin aktif atau disetujui pada rentang tanggal tersebut.',
            ], 422);
        }

        $documentPath = $request->file('document')->store('leave_documents', 'public');

        $leave = LeaveRequest::create([
            'student_id'      => $student->id,
            'applicant_id'    => $user->id,
            'leave_type'      => $validated['leave_type'],
            'leave_time'      => $validated['leave_time'] ?? null,
            'start_date'      => $validated['start_date'],
            'end_date'        => $validated['end_date'],
            'reason'          => $validated['reason'],
            'document_path'   => $documentPath,
            'status_approval' => 'Pending',
        ]);

        $leave->load(['applicant', 'student.schoolClass']);

        return response()->json([
            'status'  => 'success',
            'message' => 'Surat permohonan izin berhasil diajukan dan sedang menunggu verifikasi Wali Kelas / Guru BK.',
            'data'    => [
                'id'              => $leave->id,
                'leave_type'      => $leave->leave_type,
                'start_date'      => $leave->start_date,
                'end_date'        => $leave->end_date,
                'leave_time'      => $leave->leave_time ? substr($leave->leave_time, 0, 5) : null,
                'reason'          => $leave->reason,
                'document_url'    => asset('storage/' . $leave->document_path),
                'status_approval' => $leave->status_approval,
                'created_at'      => $leave->created_at?->format('Y-m-d H:i'),
            ],
        ], 201);
    }

    /**
     * Dapatkan daftar anak terhubung (Khusus Akun Orang Tua)
     */
    public function children(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role?->role_name !== 'orang_tua') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Endpoint ini khusus untuk peran Orang Tua.',
            ], 403);
        }

        $children = User::with('schoolClass')
            ->where('parent_id', $user->id)
            ->get()
            ->map(function ($c) {
                return [
                    'id'         => $c->id,
                    'fullname'   => $c->fullname,
                    'username'   => $c->username,
                    'nisn'       => $c->nisn_nip,
                    'class_name' => $c->schoolClass?->class_name ?? 'Tanpa Rombel',
                ];
            });

        return response()->json([
            'status' => 'success',
            'data'   => $children,
        ]);
    }
}

