<?php

namespace App\Support;

use Carbon\CarbonInterface;

class IndonesianDay
{
    /**
     * Dapatkan nama hari saat ini dalam bahasa Indonesia
     */
    public static function now(): string
    {
        return static::from(now());
    }

    /**
     * Konversi instance Carbon ke nama hari bahasa Indonesia (1=Senin s/d 7=Minggu)
     */
    public static function from(CarbonInterface $date): string
    {
        return match ($date->dayOfWeekIso) {
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        };
    }
}
