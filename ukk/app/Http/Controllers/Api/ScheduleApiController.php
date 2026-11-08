<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\Siswa;
use Illuminate\Http\Request;

class ScheduleApiController extends Controller
{
    public function index(Request $request)
    {
        $query = Schedule::with(['teacher', 'schoolClass', 'room', 'startSlot', 'endSlot']);

        $user = $request->user();
        if ($user && in_array((int) $user->role_id, [3, 6], true) && $user->student_id && ! $request->has('class_name') && ! $request->has('class_id')) {
            $student = Siswa::find($user->student_id);
            if ($student && ! empty($student->kelas)) {
                $query->where('class_name', $student->kelas);
            }
        }

        if ($request->has('day_of_week')) {
            $query->where('day_of_week', $request->day_of_week);
        }

        if ($request->has('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        if ($request->has('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->has('class_name')) {
            $query->where('class_name', $request->class_name);
        }

        if ($request->has('cycle_number')) {
            $query->where('cycle_number', (int) $request->cycle_number);
        }

        $schedules = $query->orderBy('start_time', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'total' => $schedules->count(),
            'data' => $schedules,
        ], 200);
    }

    public function show($id)
    {
        $schedule = Schedule::with(['teacher', 'schoolClass', 'room', 'startSlot', 'endSlot'])->find($id);

        if (! $schedule) {
            return response()->json([
                'status' => 'error',
                'message' => 'Jadwal tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $schedule,
        ], 200);
    }
}

