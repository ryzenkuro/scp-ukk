<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LeaveRequestApiController extends Controller
{
    public function index(Request $request)
    {
        $query = LeaveRequest::with(['student', 'verifier']);

        $user = $request->user();
        if ($user && in_array((int) $user->role_id, [2, 5], true)) {
            $isWali = $user->isWaliKelas();
            $waliClasses = array_map(fn($c) => trim(strtolower($c)), $user->getWaliClasses());

            if ($isWali && !empty($waliClasses)) {
                $query->whereHas('student', function ($q) use ($waliClasses) {
                    $q->where(function ($sub) use ($waliClasses) {
                        foreach ($waliClasses as $wc) {
                            $sub->orWhereRaw('LOWER(TRIM(kelas)) = ?', [$wc]);
                        }
                    });
                });
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if ($user && in_array((int) $user->role_id, [3, 6], true) && $user->student_id) {
            $query->where('student_id', $user->student_id);
        }

        if ($request->has('status')) {
            $query->where('status', strtolower($request->status));
        }

        if ($request->has('type')) {
            $query->where('type', strtolower($request->type));
        }

        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        $leaveRequests = $query->latest('id')->get();

        return response()->json([
            'status' => 'success',
            'total' => $leaveRequests->count(),
            'data' => $leaveRequests,
        ], 200);
    }

    public function show($id)
    {
        $leaveRequest = LeaveRequest::with(['student', 'verifier'])->find($id);

        if (! $leaveRequest) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pengajuan izin tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $leaveRequest,
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|integer|exists:siswas,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string',
            'type' => 'nullable|in:sakit,izin',
            'attachment' => 'nullable|file|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('leave_attachments', 'public');
        }

        $leaveRequest = LeaveRequest::create([
            'student_id' => $request->student_id,
            'type' => $request->type ?? 'sakit',
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'reason' => $request->reason,
            'attachment_path' => $attachmentPath,
            'status' => 'pending',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Pengajuan izin berhasil diajukan',
            'data' => $leaveRequest->load('student'),
        ], 201);
    }

    public function updateStatus(Request $request, $id)
    {
        $leaveRequest = LeaveRequest::find($id);

        if (! $leaveRequest) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pengajuan izin tidak ditemukan',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:approved,rejected,pending',
            'rejection_reason' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $leaveRequest->update([
            'status' => $request->status,
            'verified_by' => $request->user()?->id,
            'verified_at' => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        if ($request->status === 'approved') {
            AttendanceApiController::syncApprovedLeaves($leaveRequest->student_id);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Status pengajuan izin berhasil diperbarui',
            'data' => $leaveRequest->load(['student', 'verifier']),
        ], 200);
    }
}

