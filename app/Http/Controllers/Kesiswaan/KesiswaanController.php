<?php

namespace App\Http\Controllers\Kesiswaan;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
    public function verify(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $user = auth()->user();
        $isHomeroom = $user->role->role_name === 'wali_kelas';

        // Validasi otoritas wali kelas: Siswa wajib berasal dari kelas perwaliannya
        if ($isHomeroom && $leaveRequest->student->class_id !== $user->class_id) {
            abort(403, 'Anda hanya berhak memverifikasi perizinan siswa di kelas perwalian Anda.');
        }

        $validated = $request->validate([
            'status' => 'required|in:Approved,Rejected',
        ]);

        $leaveRequest->update([
            'status_approval' => $validated['status'],
            'verified_by' => $user->id,
        ]);

        $statusLabel = $validated['status'] === 'Approved' ? 'disetujui' : 'ditolak';

        return back()->with('success', "Surat izin siswa {$leaveRequest->student->fullname} berhasil {$statusLabel}!");
    }
}
