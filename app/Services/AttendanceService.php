<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Journal;
use App\Models\LeaveRequest;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    /**
     * Arah 1: Sinkronisasi saat Jurnal Mengajar dibuat oleh Guru (BR-004)
     * Mengambil seluruh izin Approved siswa pada tanggal berjalan,
     * mengunci status kehadiran menjadi Sakit/Izin (mencegah Alpa tidak sah),
     * dan menyimpan presensi secara transaksional.
     */
    public function recordAttendance(Journal $journal, array $attendancesInput, string $date): void
    {
        DB::transaction(function () use ($journal, $attendancesInput, $date) {
            $studentIds = array_keys($attendancesInput);

            // Ambil izin Approved siswa pada tanggal ini
            $approvedLeaves = LeaveRequest::whereIn('student_id', $studentIds)
                ->where('status_approval', 'Approved')
                ->where('start_date', '<=', $date)
                ->where('end_date', '>=', $date)
                ->get()
                ->keyBy('student_id');

            foreach ($attendancesInput as $studentId => $manualStatus) {
                // Prioritaskan izin resmi yang telah disetujui
                if (isset($approvedLeaves[$studentId])) {
                    $leave = $approvedLeaves[$studentId];
                    $finalStatus = ($leave->leave_type === 'Sakit' || str_contains(strtolower($leave->reason), 'sakit'))
                        ? 'Sakit'
                        : 'Izin';
                } else {
                    $finalStatus = $manualStatus;
                }

                Attendance::updateOrCreate(
                    [
                        'journal_id' => $journal->id,
                        'student_id' => $studentId,
                    ],
                    [
                        'status' => $finalStatus,
                        'date'   => $date,
                    ]
                );
            }
        });
    }

    /**
     * Arah 2: Retroactive Sync saat Izin Disetujui (Approved) oleh Wali Kelas / Guru BK (BR-004)
     * Mengoreksi secara otomatis seluruh rekaman presensi jurnal yang sudah terbit
     * pada rentang tanggal surat izin tersebut dari Alpha/Hadir menjadi Sakit/Izin.
     */
    public function syncRetroactiveApprovedLeave(LeaveRequest $leave): int
    {
        $finalStatus = ($leave->leave_type === 'Sakit' || str_contains(strtolower($leave->reason), 'sakit'))
            ? 'Sakit'
            : 'Izin';

        // Update seluruh presensi siswa yang tanggalnya berada dalam rentang izin
        return Attendance::where('student_id', $leave->student_id)
            ->whereBetween('date', [$leave->start_date, $leave->end_date])
            ->update([
                'status' => $finalStatus
            ]);
    }
}
