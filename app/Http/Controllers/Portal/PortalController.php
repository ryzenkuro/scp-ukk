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
            'leave_type' => 'required|in:Sakit,Izin,Pulang Cepat,Kegiatan Luar',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'leave_time' => 'nullable|date_format:H:i',
            'reason' => 'required|string|max:1000',
            'document' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ], [
            'leave_type.required' => 'Jenis perizinan wajib dipilih.',
            'leave_type.in' => 'Jenis perizinan harus salah satu dari: Sakit, Izin, Pulang Cepat, atau Kegiatan Luar.',
            'start_date.required' => 'Tanggal mulai izin wajib diisi.',
            'end_date.required' => 'Tanggal selesai izin wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal selesai izin tidak boleh sebelum tanggal mulai.',
            'leave_time.date_format' => 'Format jam izin harus berupa JJ:MM (contoh: 10:30).',
            'reason.required' => 'Alasan atau keterangan izin wajib diisi.',
            'document.required' => 'Surat bukti (surat dokter/keterangan) wajib diunggah.',
            'document.mimes' => 'Format berkas bukti harus berupa gambar (JPG, PNG) atau PDF.',
            'document.max' => 'Ukuran berkas bukti maksimal adalah 2MB.',
        ]);

        // Proteksi Anti-Overlap Tanggal Izin Aktif Siswa (KF53-KF56 / FASE 08)
        $hasOverlap = LeaveRequest::where('student_id', $studentId)
            ->whereIn('status_approval', ['Pending', 'Approved'])
            ->where('start_date', '<=', $validated['end_date'])
            ->where('end_date', '>=', $validated['start_date'])
            ->exists();

        if ($hasOverlap) {
            return back()->withErrors([
                'start_date' => 'Siswa sudah memiliki pengajuan izin aktif/disetujui pada rentang tanggal tersebut.'
            ])->withInput();
        }

        // Simpan file bukti ke storage public disk
        $documentPath = $request->file('document')->store('leave_documents', 'public');

        LeaveRequest::create([
            'student_id' => $studentId,
            'applicant_id' => $user->id,
            'leave_type' => $validated['leave_type'],
            'leave_time' => $validated['leave_time'] ?? null,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'reason' => $validated['reason'],
            'document_path' => $documentPath,
            'status_approval' => 'Pending',
        ]);

        return back()->with('success', 'Pengajuan surat izin berhasil dikirim! Menunggu verifikasi dari Wali Kelas atau Guru BK.');
    }
}
