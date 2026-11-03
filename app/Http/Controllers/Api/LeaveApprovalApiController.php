<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaveApprovalApiController extends Controller
{
    /**
     * Dapatkan antrean dan riwayat verifikasi surat izin siswa
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $isHomeroom = $user->role?->role_name === 'wali_kelas';

        $statusFilter = $request->query('status', 'Pending');
        $search = $request->query('search');

        $query = LeaveRequest::with(['student.schoolClass', 'applicant', 'verifier'])
            ->latest();

        // Filter otoritas wali kelas
        if ($isHomeroom) {
            $classId = $user->class_id;
            $query->whereHas('student', fn($q) => $q->where('class_id', $classId));
        }

        // Filter status persetujuan
        if ($statusFilter && $statusFilter !== 'all') {
            $query->where('status_approval', $statusFilter);
        }

        // Pencarian nama siswa atau NISN
        if ($search) {
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('fullname', 'like', "%{$search}%")
                  ->orWhere('nisn_nip', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $items = $query->paginate(20)->through(function ($lr) {
            return [
                'id'              => $lr->id,
                'student_id'      => $lr->student_id,
                'student_name'    => $lr->student?->fullname ?? 'Siswa',
                'nisn'            => $lr->student?->nisn_nip ?? '-',
                'class_name'      => $lr->student?->schoolClass?->class_name ?? '-',
                'applicant_name'  => $lr->applicant?->fullname ?? '-',
                'leave_type'      => $lr->leave_type,
                'start_date'      => $lr->start_date,
                'end_date'        => $lr->end_date,
                'leave_time'      => $lr->leave_time ? substr($lr->leave_time, 0, 5) : null,
                'reason'          => $lr->reason,
                'document_url'    => asset('storage/' . $lr->document_path),
                'status_approval' => $lr->status_approval,
                'verifier_name'   => $lr->verifier?->fullname ?? '-',
                'created_at'      => $lr->created_at?->format('Y-m-d H:i') ?? '',
            ];
        });

        // Hitung count pending saat ini untuk badge counter
        $pendingQuery = LeaveRequest::where('status_approval', 'Pending');
        if ($isHomeroom) {
            $pendingQuery->whereHas('student', fn($q) => $q->where('class_id', $user->class_id));
        }
        $pendingCount = $pendingQuery->count();

        return response()->json([
            'status'        => 'success',
            'pending_count' => $pendingCount,
            'data'          => $items->items(),
            'pagination'    => [
                'total'        => $items->total(),
                'current_page' => $items->currentPage(),
                'last_page'    => $items->lastPage(),
            ],
        ]);
    }

    /**
     * Eksekusi Verifikasi: Setujui (Approve) atau Tolak (Reject)
     */
    public function verify(Request $request, LeaveRequest $leaveRequest, AttendanceService $attendanceService): JsonResponse
    {
        $user = $request->user();
        $isHomeroom = $user->role?->role_name === 'wali_kelas';

        // 1. Validasi otoritas wali kelas: Siswa wajib berasal dari kelas perwaliannya
        if ($isHomeroom && $leaveRequest->student?->class_id !== $user->class_id) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Anda hanya berhak memverifikasi perizinan siswa di kelas perwalian Anda.',
            ], 403);
        }

        // 2. Keamanan State: Cegah verifikasi ganda
        if ($leaveRequest->status_approval !== 'Pending') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Pengajuan surat izin ini sudah pernah diverifikasi sebelumnya.',
            ], 422);
        }

        $validated = $request->validate([
            'status'         => 'required|in:Approved,Rejected',
            'rejection_note' => 'nullable|string|max:500',
        ], [
            'status.required' => 'Status keputusan verifikasi wajib ditentukan (Approved / Rejected).',
            'status.in'       => 'Status verifikasi harus Approved atau Rejected.',
        ]);

        DB::transaction(function () use ($leaveRequest, $validated, $user, $attendanceService) {
            $updateData = [
                'status_approval' => $validated['status'],
                'verified_by'     => $user->id,
            ];

            if ($validated['status'] === 'Rejected' && !empty($validated['rejection_note'])) {
                $sanitizedNote = strip_tags(trim($validated['rejection_note']));
                $updateData['reason'] = $leaveRequest->reason . ' [Catatan Penolakan: ' . $sanitizedNote . ']';
            }

            $leaveRequest->update($updateData);

            // Arah 2: Retroactive Sync Presensi saat Izin Disetujui (BR-004)
            if ($validated['status'] === 'Approved') {
                $attendanceService->syncRetroactiveApprovedLeave($leaveRequest);
            }
        });

        $leaveRequest->load(['student.schoolClass', 'applicant', 'verifier']);
        $statusLabel = $validated['status'] === 'Approved' ? 'disetujui' : 'ditolak';

        return response()->json([
            'status'  => 'success',
            'message' => "Surat permohonan izin siswa {$leaveRequest->student?->fullname} berhasil {$statusLabel}!",
            'data'    => [
                'id'              => $leaveRequest->id,
                'status_approval' => $leaveRequest->status_approval,
                'verifier_name'   => $leaveRequest->verifier?->fullname ?? $user->fullname,
                'updated_at'      => $leaveRequest->updated_at?->format('Y-m-d H:i'),
            ],
        ]);
    }
}

