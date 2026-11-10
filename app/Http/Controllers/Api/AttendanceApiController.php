<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Services\AttendanceSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AttendanceApiController extends Controller
{
    /**
     * Sinkronisasi pengajuan izin yang disetujui (Approved) ke tabel presensi (attendances).
     */
    public static function syncApprovedLeaves(?int $studentId = null): void
    {
        AttendanceSyncService::syncAllApprovedLeaves($studentId);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $isChildScoped = $user && in_array((int) $user->role_id, [3, 6], true) && $user->student_id;
        $targetStudentId = $isChildScoped ? (int) $user->student_id : ($request->has('student_id') ? (int) $request->student_id : null);

        self::syncApprovedLeaves($targetStudentId);

        $query = Attendance::with(['student', 'journal']);

        if ($request->has('date')) {
            $query->where('date', $request->date);
        }

        if ($targetStudentId) {
            $query->where('student_id', $targetStudentId);
        }

        if ($request->has('status')) {
            $query->where('status', strtolower($request->status));
        }

        $attendances = $query->latest('date')->get();

        return response()->json([
            'status' => 'success',
            'total' => $attendances->count(),
            'data' => $attendances,
        ], 200);
    }

    public function show($id)
    {
        $attendance = Attendance::with(['student', 'journal'])->find($id);

        if (! $attendance) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data presensi tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $attendance,
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'journal_id' => 'nullable|integer|exists:journals,id',
            'student_id' => 'required|integer|exists:siswas,id',
            'schedule_id' => 'nullable|integer|exists:schedules,id',
            'date' => 'required|date',
            'status' => 'required|in:hadir,sakit,izin,alpa,Hadir,Sakit,Izin,Alpa',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $attendance = Attendance::updateOrCreate(
            [
                'journal_id' => $request->journal_id,
                'student_id' => $request->student_id,
                'date' => $request->date,
            ],
            [
                'schedule_id' => $request->schedule_id,
                'status' => strtolower($request->status),
                'notes' => $request->notes,
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Data presensi berhasil disimpan',
            'data' => $attendance->load('student'),
        ], 201);
    }
}
