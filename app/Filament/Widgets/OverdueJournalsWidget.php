<?php

namespace App\Filament\Widgets;

use App\Models\Schedule;
use App\Models\SystemConfiguration;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class OverdueJournalsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = '⚠️ Rekap Keterlambatan Pengisian Jurnal Hari Ini (>30 Menit)';

    public function table(Table $table): Table
    {
        $config = SystemConfiguration::current();
        $today = now()->toDateString();

        $currentDayName = \App\Support\IndonesianDay::now();
        $cutoffTime = now()->subMinutes(30)->format('H:i:s');

        return $table
            ->query(
                Schedule::query()
                    ->with(['teacher', 'schoolClass', 'room'])
                    ->where('cycle_type', $config->active_cycle)
                    ->where('day_of_week', $currentDayName)
                    ->where('end_time', '<=', $cutoffTime)
                    ->whereDoesntHave('journals', function ($q) use ($today) {
                        $q->where('date', $today);
                    })
            )
            ->columns([
                Tables\Columns\TextColumn::make('teacher.fullname')
                    ->label('Nama Guru')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('subject')
                    ->label('Mata Pelajaran')
                    ->searchable(),

                Tables\Columns\TextColumn::make('schoolClass.class_name')
                    ->label('Kelas')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('room.room_name')
                    ->label('Ruangan'),

                Tables\Columns\TextColumn::make('end_time')
                    ->label('Jam Berakhir')
                    ->formatStateUsing(fn ($state) => substr($state, 0, 5) . ' WIB')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status Keterlambatan')
                    ->default('Terlambat >30 Menit')
                    ->badge()
                    ->color('danger'),
            ])
            ->emptyStateHeading('Semua Jurnal Hari Ini Telah Diisi')
            ->emptyStateDescription('Tidak ada guru yang terlambat mengisi jurnal melebihi batas toleransi 30 menit.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}
