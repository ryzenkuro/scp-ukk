<?php

namespace App\Http\Controllers\Kesiswaan;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Services\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class KesiswaanController extends Controller
{
    /**
     * Tampilkan antarmuka verifikasi perizinan untuk Wali Kelas & Guru BK
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        $isHomeroom = $user->role->role_name === 'wali_kelas';
        $selectedStatus = $request->query('status', 'All');

        // Query dasar berelasi
        $query = LeaveRequest::with(['student.schoolClass', 'applicant', 'verifier']);

        // Logika Filter Hak Akses Rubrik UKK:
        // - Wali Kelas: Hanya melihat siswa di kelas perwaliannya
        // - Guru BK: Memantau perizinan dari seluruh kelas sekolah
        if ($isHomeroom) {
            $query->whereHas('student', function ($q) use ($user) {
                $q->where('class_id', $user->class_id);
            });
        }

        // Hitung ringkasan statistik status
        $baseQuery = clone $query;
        $stats = [
            'total' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where('status_approval', 'Pending')->count(),
            'approved' => (clone $baseQuery)->where('status_approval', 'Approved')->count(),
            'rejected' => (clone $baseQuery)->where('status_approval', 'Rejected')->count(),
        ];

        // Filter status jika dipilih
        if (in_array($selectedStatus, ['Pending', 'Approved', 'Rejected'])) {
            $query->where('status_approval', $selectedStatus);
        }

        // Urutkan: Pending paling atas, lalu terbaru
        $leaveRequests = $query
            ->orderByRaw("FIELD(status_approval, 'Pending', 'Approved', 'Rejected')")
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('kesiswaan.dashboard', compact(
            'user',
            'isHomeroom',
            'leaveRequests',
            'stats',
            'selectedStatus'
        ));
    }

    /**
     * Proses verifikasi (Approve atau Reject) perizinan siswa
     */
    public function verify(Request $request, LeaveRequest $leaveRequest, AttendanceService $attendanceService): RedirectResponse
    {
        $user = auth()->user();
        $isHomeroom = $user->role->role_name === 'wali_kelas';

        // 1. Validasi otoritas wali kelas: Siswa wajib berasal dari kelas perwaliannya
        if ($isHomeroom && $leaveRequest->student->class_id !== $user->class_id) {
            abort(403, 'Anda hanya berhak memverifikasi perizinan siswa di kelas perwalian Anda.');
        }

        // 2. Keamanan State: Cegah verifikasi ulang jika statusnya sudah bukan Pending
        if ($leaveRequest->status_approval !== 'Pending') {
            return back()->with('error', 'Pengajuan izin ini sudah pernah diverifikasi sebelumnya.');
        }

        // 3. Normalisasi parameter status / status_approval
        $status = $request->input('status', $request->input('status_approval'));
        $request->merge(['status' => $status]);

        $validated = $request->validate([
            'status' => 'required|in:Approved,Rejected',
            'rejection_note' => 'nullable|string|max:500',
        ]);

        // 4. Update status dan presensi secara transaksional
        DB::transaction(function () use ($leaveRequest, $validated, $user, $attendanceService) {
            $updateData = [
                'status_approval' => $validated['status'],
                'verified_by' => $user->id,
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

        $statusLabel = $validated['status'] === 'Approved' ? 'disetujui' : 'ditolak';

        return back()->with('success', "Surat izin siswa {$leaveRequest->student->fullname} berhasil {$statusLabel}!");
    }
}
