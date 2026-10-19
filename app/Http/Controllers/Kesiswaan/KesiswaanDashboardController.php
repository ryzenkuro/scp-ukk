<?php

namespace App\Http\Controllers\Kesiswaan;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KesiswaanDashboardController extends Controller
{
    /**
     * Tampilkan panel verifikasi perizinan untuk Wali Kelas & Guru BK
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        $isWaliKelas = $user->role->role_name === 'wali_kelas';

        $baseQuery = LeaveRequest::with(['student.schoolClass', 'applicant', 'verifier']);

        if ($isWaliKelas) {
            // Wali kelas HANYA melihat siswa di kelas perwaliannya saja
            $baseQuery->whereHas('student', function ($query) use ($user) {
                $query->where('class_id', $user->class_id);
            });
        }
        // Guru BK otomatis melihat pengajuan dari SELURUH kelas

        // Hitung statistik ringkasan
        $totalPending  = (clone $baseQuery)->where('status_approval', 'Pending')->count();
        $totalApproved = (clone $baseQuery)->where('status_approval', 'Approved')->count();
        $totalRejected = (clone $baseQuery)->where('status_approval', 'Rejected')->count();

        // Filter opsional berdasarkan tab status
        $statusFilter = $request->query('status');
        if (in_array($statusFilter, ['Pending', 'Approved', 'Rejected'])) {
            $baseQuery->where('status_approval', $statusFilter);
        }

        $leaves = $baseQuery->latest()->get();

        return view('kesiswaan.dashboard', compact(
            'leaves',
            'totalPending',
            'totalApproved',
            'totalRejected',
            'statusFilter',
            'isWaliKelas'
        ));
    }

    /**
     * Verifikasi surat izin siswa: Approve (Setujui) atau Reject (Tolak)
     */
    public function verify(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:Approved,Rejected',
        ]);

        $user = auth()->user();

        // Validasi keamanan: Wali Kelas hanya boleh memverifikasi siswa di kelas perwaliannya
        if ($user->role->role_name === 'wali_kelas') {
            if ($leaveRequest->student->class_id !== $user->class_id) {
                abort(403, 'Akses ditolak! Anda hanya berhak memverifikasi izin siswa di kelas perwalian Anda.');
            }
        }

        $leaveRequest->update([
            'status_approval' => $validated['status'],
            'verified_by'     => $user->id,
        ]);

        $label = $validated['status'] === 'Approved' ? 'disetujui' : 'ditolak';

        return back()->with('success', "Pengajuan izin atas nama {$leaveRequest->student->fullname} berhasil {$label}.");
    }
}
