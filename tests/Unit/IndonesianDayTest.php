<?php

namespace Tests\Unit;

use App\Support\IndonesianDay;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class IndonesianDayTest extends TestCase
{
    public function test_converts_all_seven_days_correctly(): void
    {
        // 2026-10-19 is Monday (Senin)
        $this->assertEquals('Senin', IndonesianDay::from(Carbon::parse('2026-10-19')));
        $this->assertEquals('Selasa', IndonesianDay::from(Carbon::parse('2026-10-20')));
        $this->assertEquals('Rabu', IndonesianDay::from(Carbon::parse('2026-10-21')));
        $this->assertEquals('Kamis', IndonesianDay::from(Carbon::parse('2026-10-22')));
        $this->assertEquals('Jumat', IndonesianDay::from(Carbon::parse('2026-10-23')));
        $this->assertEquals('Sabtu', IndonesianDay::from(Carbon::parse('2026-10-24')));
        $this->assertEquals('Minggu', IndonesianDay::from(Carbon::parse('2026-10-25')));
    }

    public function test_now_returns_valid_indonesian_day_name(): void
    {
        $validDays = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
        $this->assertContains(IndonesianDay::now(), $validDays);
    }
}
