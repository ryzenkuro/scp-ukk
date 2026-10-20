<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\SystemConfiguration;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Dashboard Guru dengan Deteksi Keterlambatan Jurnal Realtime (>30 Menit, Tanpa Cron Job)
     */
    public function index(): View
    {
        $teacher = auth()->user();
        $config = SystemConfiguration::current();
        $today = now()->toDateString();

        // Nama hari ini dalam bahasa Indonesia via Helper Terpusat
        $currentDayName = \App\Support\IndonesianDay::now();

        // ⏱️ Batas waktu keterlambatan: waktu saat ini dikurangi 30 menit
        $cutoffTime = now()->subMinutes(30)->format('H:i:s');

        // 🔍 Cari jadwal hari ini pada siklus aktif yang jam selesainya telah lewat >30 menit
        // DAN belum memiliki entri jurnal untuk tanggal hari ini
        $overdueSchedules = Schedule::with(['schoolClass', 'room'])
            ->where('teacher_id', $teacher->id)
            ->where('cycle_type', $config->active_cycle)
            ->where('day_of_week', $currentDayName)
            ->where('end_time', '<=', $cutoffTime)
            ->whereDoesntHave('journals', function ($q) use ($today) {
                $q->where('date', $today);
            })
            ->orderBy('end_time')
            ->get();

        // Total seluruh jadwal guru pada siklus aktif
        $mySchedulesCount = Schedule::where('teacher_id', $teacher->id)
            ->where('cycle_type', $config->active_cycle)
            ->count();

        // Jadwal hari ini beserta relasi status jurnal hari ini
        $todaySchedules = Schedule::with(['schoolClass', 'room', 'journals' => function ($q) use ($today) {
            $q->where('date', $today);
        }])
            ->where('teacher_id', $teacher->id)
            ->where('cycle_type', $config->active_cycle)
            ->where('day_of_week', $currentDayName)
            ->orderBy('start_time')
            ->get();

        return view('guru.dashboard', compact(
            'teacher',
            'config',
            'currentDayName',
            'today',
            'cutoffTime',
            'overdueSchedules',
            'mySchedulesCount',
            'todaySchedules'
        ));
    }
}
