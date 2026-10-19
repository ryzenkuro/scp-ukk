<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveRequestController extends Controller
{
    /**
     * Tampilkan Portal Siswa & Orang Tua beserta riwayat pengajuan izin
     */
    public function index(): View
    {
        $user = auth()->user();
        $isParent = $user->role->role_name === 'orang_tua';

        if ($isParent) {
            // Ambil data siswa yang terhubung dengan akun orang tua ini
            $student = User::with(['schoolClass', 'parent'])
                ->where('parent_id', $user->id)
                ->first();

            $leaves = $student
                ? LeaveRequest::with(['student.schoolClass', 'applicant', 'verifier'])
                    ->where('student_id', $student->id)
                    ->latest()
                    ->get()
                : collect();
        } else {
            // Role siswa langsung
            $student = $user->load(['schoolClass', 'parent']);

            $leaves = LeaveRequest::with(['student.schoolClass', 'applicant', 'verifier'])
                ->where('student_id', $student->id)
                ->latest()
                ->get();
        }

        return view('portal.dashboard', compact('student', 'leaves', 'isParent'));
    }

    /**
     * Simpan pengajuan surat izin / sakit siswa atau orang tua
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'reason'     => 'required|string|max:1000',
            'document'   => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ], [
            'end_date.after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai izin.',
            'document.required'       => 'Wajib mengunggah bukti surat resmi atau surat keterangan dokter.',
            'document.mimes'          => 'Format berkas bukti harus berupa gambar (JPG, JPEG, PNG) atau dokumen PDF.',
            'document.max'            => 'Ukuran berkas bukti maksimal 2MB (2048 KB).',
        ]);

        $user = auth()->user();

        if ($user->role->role_name === 'siswa') {
            $studentId = $user->id;
        } elseif ($user->role->role_name === 'orang_tua') {
            $child = User::where('parent_id', $user->id)->first();
            if (! $child) {
                return back()->withInput()->withErrors([
                    'student' => 'Akun orang tua Anda belum terhubung dengan akun siswa anak.',
                ]);
            }
            $studentId = $child->id;
        } else {
            abort(403, 'Akses ditolak.');
        }

        // Simpan file bukti surat ke storage public
        $documentPath = $request->file('document')->store('documents/leaves', 'public');

        LeaveRequest::create([
            'student_id'      => $studentId,
            'applicant_id'    => $user->id,
            'start_date'      => $request->input('start_date'),
            'end_date'        => $request->input('end_date'),
            'reason'          => $request->input('reason'),
            'document_path'   => $documentPath,
            'status_approval' => 'Pending',
        ]);

        return back()->with('success', 'Pengajuan surat perizinan berhasil dikirim! Menunggu verifikasi tim kesiswaan.');
    }
}
