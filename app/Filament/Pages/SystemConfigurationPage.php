<?php

namespace App\Filament\Pages;

use App\Models\SystemConfiguration;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SystemConfigurationPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationLabel = 'Siklus & Kunci Jadwal';

    protected static ?string $title = 'Kontrol Siklus Blok & Kunci Jadwal';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.system-configuration-page';

    public function toggleCycle(): void
    {
        $config = SystemConfiguration::current();
        $newCycle = $config->active_cycle === 'Putaran 1' ? 'Putaran 2' : 'Putaran 1';

        $config->update([
            'active_cycle' => $newCycle,
            'updated_by' => auth()->id(),
        ]);

        Notification::make()
            ->title('Siklus Berhasil Diubah')
            ->body("Siklus aktif sekolah sekarang: {$newCycle}")
            ->success()
            ->send();
    }

    public function toggleLock(): void
    {
        $config = SystemConfiguration::current();
        $newStatus = ! $config->is_schedule_locked;

        $config->update([
            'is_schedule_locked' => $newStatus,
            'updated_by' => auth()->id(),
        ]);

        $statusText = $newStatus ? 'TERKUNCI' : 'TERBUKA';

        Notification::make()
            ->title("Status Jadwal: {$statusText}")
            ->body($newStatus ? 'Guru tidak dapat lagi memodifikasi jadwal.' : 'Masa pengisian jadwal mandiri guru telah dibuka.')
            ->color($newStatus ? 'danger' : 'success')
            ->send();
    }

    protected function getViewData(): array
    {
        return [
            'config' => SystemConfiguration::current(),
        ];
    }
}
