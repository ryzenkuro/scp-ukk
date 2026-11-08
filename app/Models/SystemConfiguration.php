<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemConfiguration extends Model
{
    protected $fillable = [
        'active_cycle',
        'is_scheduled_locked',
        'cycle_one_locked',
        'cycle_two_locked',
        'academic_year',
    ];

    protected $casts = [
        'is_scheduled_locked' => 'boolean',
        'cycle_one_locked' => 'boolean',
        'cycle_two_locked' => 'boolean',
        'active_cycle' => 'integer',
    ];

    public static function current(): self
    {
        return self::firstOrCreate([], [
            'active_cycle' => 1,
            'is_scheduled_locked' => false,
            'cycle_one_locked' => false,
            'cycle_two_locked' => false,
            'academic_year' => '2026/2027',
        ]);
    }

    /**
     * Menghitung tahun ajaran berjalan berdasarkan kalender riil sistem.
     * Standar pendidikan di Indonesia: tahun ajaran baru dimulai bulan Juli (bulan 7).
     */
    public static function determineCurrentAcademicYear(): string
    {
        $now = now();
        $year = (int) $now->format('Y');
        $month = (int) $now->format('n');

        if ($month >= 7) {
            return "{$year}/" . ($year + 1);
        }

        return ($year - 1) . "/{$year}";
    }

    /**
     * Mengecek apakah tahun ajaran aktif di database perlu diganti (tertinggal dari tahun berjalan).
     */
    public function isAcademicYearUpdateNeeded(): bool
    {
        $realYear = self::determineCurrentAcademicYear();
        if (preg_match('/^(\d{4})/', $realYear, $mReal) && preg_match('/^(\d{4})/', (string) $this->academic_year, $mDb)) {
            return (int) $mReal[1] > (int) $mDb[1];
        }

        return false;
    }
}

