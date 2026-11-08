<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JournalApiController extends Controller
{
    public function index(Request $request)
    {
        $query = Journal::with(['schedule', 'attendances']);

        if ($request->has('date')) {
            $query->where('date', $request->date);
        }

        if ($request->has('schedule_id')) {
            $query->where('schedule_id', $request->schedule_id);
        }

        if ($request->has('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        $journals = $query->latest('date')->get();

        return response()->json([
            'status' => 'success',
            'total' => $journals->count(),
            'data' => $journals,
        ], 200);
    }

    public function show($id)
    {
        $journal = Journal::with(['schedule', 'attendances.student'])->find($id);

        if (! $journal) {
            return response()->json([
                'status' => 'error',
                'message' => 'Jurnal tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $journal,
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'schedule_id' => 'required|integer|exists:schedules,id',
            'date' => 'required|date',
            'topic' => 'nullable|string',
            'topic_description' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $schedule = Schedule::find($request->schedule_id);
        $teacherId = $request->user() ? $request->user()->id : ($schedule?->teacher_id ?? 1);
        $topic = $request->topic ?? $request->topic_description ?? 'Materi Pembelajaran';

        $alreadyExists = Journal::where('schedule_id', $request->schedule_id)
            ->where('date', $request->date)
            ->exists();

        if ($alreadyExists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Jurnal untuk mata pelajaran pada jadwal ini sudah pernah diisi untuk hari ini.',
            ], 422);
        }

        $journal = Journal::create([
            'schedule_id' => $request->schedule_id,
            'teacher_id' => $teacherId,
            'date' => $request->date,
            'topic' => $topic,
            'notes' => $request->notes,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Jurnal mengajar berhasil dibuat',
            'data' => $journal->load('schedule'),
        ], 201);
    }
}

