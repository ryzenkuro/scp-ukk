<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalController extends Controller
{
    /**
     * Tampilkan Dashboard Portal Siswa & Orang Tua beserta riwayat perizinan
     */
    public function index(): View
    {
        $user = auth()->user();
        $isParent = $user->role->role_name === 'orang_tua';

        // Deteksi Siswa terkait
        if ($isParent) {
            $student = User::with('schoolClass')->where('parent_id', $user->id)->first();
        } else {
            $student = User::with('schoolClass')->find($user->id);
        }

        $leaveRequests = collect();
        if ($student) {
            $leaveRequests = LeaveRequest::with(['student.schoolClass', 'applicant', 'verifier'])
                ->where('student_id', $student->id)
                ->latest()
                ->get();
        }

        return view('portal.dashboard', compact('user', 'isParent', 'student', 'leaveRequests'));
    }

    /**
     * Simpan pengajuan izin/sakit baru beserta upload surat dokter/resmi
     */
    public function storeLeave(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $isParent = $user->role->role_name === 'orang_tua';

        // Tentukan student_id
        if ($isParent) {
            $student = User::where('parent_id', $user->id)->first();
            if (! $student) {
                return back()->withErrors(['student' => 'Data profil siswa untuk akun orang tua ini belum terhubung.']);
            }
            $studentId = $student->id;
        } else {
            $studentId = $user->id;
        }

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:1000',
            'document' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ], [
            'end_date.after_or_equal' => 'Tanggal selesai izin tidak boleh sebelum tanggal mulai.',
            'document.required' => 'Surat bukti (surat dokter/keterangan) wajib diunggah.',
            'document.mimes' => 'Format berkas bukti harus berupa gambar (JPG, PNG) atau PDF.',
            'document.max' => 'Ukuran berkas bukti maksimal adalah 2MB.',
        ]);

        // Simpan file bukti ke storage public disk
        $documentPath = $request->file('document')->store('leave_documents', 'public');

        LeaveRequest::create([
            'student_id' => $studentId,
            'applicant_id' => $user->id,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'reason' => $validated['reason'],
            'document_path' => $documentPath,
            'status_approval' => 'Pending',
        ]);

        return back()->with('success', 'Pengajuan surat izin berhasil dikirim! Menunggu verifikasi dari Wali Kelas atau Guru BK.');
    }
}
