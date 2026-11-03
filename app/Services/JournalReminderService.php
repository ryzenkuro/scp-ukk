<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\SystemConfiguration;
use App\Support\IndonesianDay;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class JournalReminderService
{
    /**
     * Dapatkan query builder jadwal yang terlambat diisi jurnal (>30 menit) hari ini (BR-005)
     */
    public static function getOverdueQuery(?int $teacherId = null): Builder
    {
        $config = SystemConfiguration::current();
        $today = now()->toDateString();
        $currentDay = IndonesianDay::now();

        // Batas toleransi: 30 menit setelah jam selesai mengajar (BR-005 & KF64)
        $cutoffTime = now()->subMinutes(30)->format('H:i:s');

        return Schedule::query()
            ->with(['teacher', 'schoolClass', 'room'])
            ->where('cycle_type', $config->active_cycle)
            ->where('day_of_week', $currentDay)
            ->where('end_time', '<=', $cutoffTime)
            ->when($teacherId, fn ($q) => $q->where('teacher_id', $teacherId))
            ->whereDoesntHave('journals', function ($q) use ($today) {
                $q->where('date', $today);
            })
            ->orderBy('end_time');
    }

    /**
     * Dapatkan daftar jadwal yang terlambat diisi jurnal (>30 menit) hari ini (BR-005)
     * @param int|null $teacherId (Jika null, ambil seluruh guru untuk monitoring Waka Kurikulum)
     */
    public static function getOverdueSchedules(?int $teacherId = null): Collection
    {
        return self::getOverdueQuery($teacherId)->get();
    }
}
