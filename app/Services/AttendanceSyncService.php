<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use Carbon\Carbon;

class AttendanceSyncService
{
    /**
     * Sinkronkan pengajuan izin ke tabel attendances.
     * Jika izin disetujui (approved), buat atau perbarui entri presensi untuk setiap hari aktif belajar (status: sakit/izin).
     * Jika izin ditolak (rejected), buat atau perbarui entri presensi untuk setiap hari aktif belajar menjadi Alpa (status: alpa).
     * Jika izin berstatus selain approved/rejected (misal: pending/dibatalkan), bersihkan entri presensi unattached.
     */
    public static function syncLeaveRequest(LeaveRequest $leave): void
    {
        if (in_array($leave->status, ['approved', 'rejected'], true)) {
            $start = Carbon::parse($leave->start_date);
            $end = Carbon::parse($leave->end_date ?? $leave->start_date);
            if ($end->lt($start)) {
                $end = $start->copy();
            }

            // Batasi rentang maksimal (misal 60 hari untuk keamanan)
            $diffDays = min($start->diffInDays($end) + 1, 60);

            for ($i = 0; $i < $diffDays; $i++) {
                $curDate = $start->copy()->addDays($i);

                // Lewati hari Minggu (atau akhir pekan non-KBM)
                if ($curDate->isSunday()) {
                    continue;
                }

                $dateStr = $curDate->toDateString();
                $status = $leave->status === 'approved'
                    ? ($leave->type === 'sakit' ? 'sakit' : 'izin')
                    : 'alpa';
                $notes = $leave->status === 'approved'
                    ? 'Izin terverifikasi: '.($leave->reason ?? '')
                    : 'Izin ditolak: '.($leave->rejection_reason ?: 'Tidak disetujui wali kelas');

                // Cari apakah sudah ada presensi pada tanggal tersebut
                $existing = Attendance::where('student_id', $leave->student_id)
                    ->whereDate('date', $dateStr)
                    ->first();

                if (! $existing) {
                    Attendance::create([
                        'journal_id' => null,
                        'student_id' => $leave->student_id,
                        'schedule_id' => null,
                        'date' => $dateStr,
                        'status' => $status,
                        'notes' => $notes,
                    ]);
                } else {
                    $existing->update([
                        'status' => $status,
                        'notes' => $notes,
                    ]);
                }
            }
        } else {
            // Jika status ditolak / pending, bersihkan presensi unattached pada rentang tanggal tersebut
            $start = Carbon::parse($leave->start_date)->toDateString();
            $end = Carbon::parse($leave->end_date ?? $leave->start_date)->toDateString();

            Attendance::where('student_id', $leave->student_id)
                ->whereNull('journal_id')
                ->whereDate('date', '>=', $start)
                ->whereDate('date', '<=', $end)
                ->delete();
        }
    }

    /**
     * Sinkronkan semua data permohonan izin yang disetujui (Approved) ke tabel attendances.
     */
    public static function syncAllApprovedLeaves(?int $studentId = null): void
    {
        $query = LeaveRequest::whereIn('status', ['approved', 'rejected'])
            ->orderBy('id', 'asc');
        if ($studentId) {
            $query->where('student_id', $studentId);
        }

        $leaves = $query->get();
        foreach ($leaves as $leave) {
            self::syncLeaveRequest($leave);
        }
    }
}
