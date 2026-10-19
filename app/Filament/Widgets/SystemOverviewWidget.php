<?php

namespace App\Filament\Widgets;

use App\Models\Room;
use App\Models\SchoolClass;
use App\Models\SystemConfiguration;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SystemOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $config = SystemConfiguration::current();

        return [
            Stat::make('Siklus Blok Aktif', $config->active_cycle)
                ->description('Siklus pembelajaran 2-mingguan')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color($config->active_cycle === 'Putaran 1' ? 'info' : 'primary'),

            Stat::make('Status Kunci Jadwal', $config->is_schedule_locked ? 'Terkunci 🔒' : 'Terbuka 🔓')
                ->description($config->is_schedule_locked ? 'Input jadwal guru dinonaktifkan' : 'Masa input mandiri guru aktif')
                ->descriptionIcon($config->is_schedule_locked ? 'heroicon-m-lock-closed' : 'heroicon-m-lock-open')
                ->color($config->is_schedule_locked ? 'danger' : 'success'),

            Stat::make('Total Kelas', SchoolClass::count())
                ->description('Kelas terdaftar di sekolah')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('gray'),

            Stat::make('Total Ruangan', Room::count())
                ->description('Laboratorium & Teori')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('gray'),
        ];
    }
}
