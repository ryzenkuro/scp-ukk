<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\SystemConfiguration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    /**
     * Tampilkan jadwal mengajar guru dan jadwal global sekolah
     */
    public function index(Request $request): View
    {
        $config = SystemConfiguration::current();
        $selectedCycle = $request->query('cycle', $config->active_cycle);

        $mySchedules = Schedule::with(['schoolClass', 'room'])
            ->where('teacher_id', auth()->id())
            ->where('cycle_type', $selectedCycle)
            ->orderByRaw("FIELD(day_of_week, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu')")
            ->orderBy('start_time')
            ->get();

        $allSchedules = Schedule::with(['teacher', 'schoolClass', 'room'])
            ->where('cycle_type', $selectedCycle)
            ->orderByRaw("FIELD(day_of_week, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu')")
            ->orderBy('start_time')
            ->get();

        $classes = SchoolClass::all();
        $rooms = Room::all();
        $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

        return view('guru.schedules.index', compact(
            'config',
            'selectedCycle',
            'mySchedules',
            'allSchedules',
            'classes',
            'rooms',
            'days'
        ));
    }

    /**
     * Simpan jadwal baru mandiri dengan algoritma Anti-Bentrok
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'room_id' => 'required|exists:rooms,id',
            'subject' => 'required|string|max:100',
            'day_of_week' => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'cycle_type' => 'required|in:Putaran 1,Putaran 2',
        ], [
            'end_time.after' => 'Jam selesai harus lebih akhir dari jam mulai.',
        ]);

        $data = $validated;
        $data['teacher_id'] = auth()->id();

        // 🔍 Logika 1: Algoritma Pencegahan Bentrok Waktu (Conflict Prevention)
        $conflictMessage = $this->checkScheduleConflict($data);
        if ($conflictMessage) {
            return back()->withInput()->withErrors([
                'conflict' => $conflictMessage,
            ]);
        }

        Schedule::create($data);

        return back()->with('success', 'Jadwal mengajar berhasil disimpan!');
    }

    /**
     * Hapus jadwal mengajar
     */
    public function destroy(Schedule $schedule): RedirectResponse
    {
        if ($schedule->teacher_id !== auth()->id()) {
            abort(403, 'Anda tidak berhak menghapus jadwal guru lain.');
        }

        $schedule->delete();

        return back()->with('success', 'Jadwal berhasil dihapus!');
    }

    /**
     * Fungsi Algoritma Pendeteksi Bentrok Jadwal
     * Rumus Irisan Waktu: Start_DB < End_Input AND End_DB > Start_Input
     */
    private function checkScheduleConflict(array $data, ?int $ignoreScheduleId = null): ?string
    {
        $conflict = Schedule::with(['teacher', 'schoolClass', 'room'])
            ->where('cycle_type', $data['cycle_type'])
            ->where('day_of_week', $data['day_of_week'])
            ->when($ignoreScheduleId, fn($q) => $q->where('id', '!=', $ignoreScheduleId))
            // Irisan Waktu: Start_DB < End_Input AND End_DB > Start_Input
            ->where('start_time', '<', $data['end_time'])
            ->where('end_time', '>', $data['start_time'])
            ->where(function ($query) use ($data) {
                $query->where('room_id', $data['room_id'])
                      ->orWhere('class_id', $data['class_id'])
                      ->orWhere('teacher_id', $data['teacher_id']);
            })
            ->first();

        if (! $conflict) {
            return null; // Aman, tidak ada bentrok
        }

        if ($conflict->room_id == $data['room_id']) {
            return "Bentrok Ruangan! Ruang {$conflict->room->room_name} sudah dipakai oleh {$conflict->teacher->fullname} ({$conflict->schoolClass->class_name}) pada jam {$conflict->start_time} - {$conflict->end_time}.";
        }

        if ($conflict->class_id == $data['class_id']) {
            return "Bentrok Kelas! Kelas {$conflict->schoolClass->class_name} sedang diajar oleh {$conflict->teacher->fullname} pada jam {$conflict->start_time} - {$conflict->end_time}.";
        }

        return "Bentrok Jadwal Mengajar! Anda sudah memiliki jadwal di kelas {$conflict->schoolClass->class_name} pada jam {$conflict->start_time} - {$conflict->end_time}.";
    }
}
