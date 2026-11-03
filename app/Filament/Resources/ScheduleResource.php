<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScheduleResource\Pages;
use App\Models\Schedule;
use App\Models\SystemConfiguration;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ScheduleResource extends Resource
{
    protected static ?string $model = Schedule::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Manajemen Akademik';

    protected static ?string $navigationLabel = 'Jadwal Mengajar';

    protected static ?string $modelLabel = 'Jadwal';

    protected static ?string $pluralModelLabel = 'Jadwal Mengajar';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('teacher_id')
                    ->label('Guru Pengajar')
                    ->relationship('teacher', 'fullname', fn (Builder $query) => $query->whereHas('role', fn ($q) => $q->where('role_name', 'guru')))
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\Select::make('class_id')
                    ->label('Kelas (Rombel)')
                    ->relationship('schoolClass', 'class_name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\Select::make('room_id')
                    ->label('Ruangan')
                    ->relationship('room', 'room_name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\TextInput::make('subject')
                    ->label('Mata Pelajaran')
                    ->required()
                    ->maxLength(100),

                Forms\Components\Select::make('cycle_type')
                    ->label('Siklus Blok')
                    ->options([
                        'Putaran 1' => 'Putaran 1',
                        'Putaran 2' => 'Putaran 2',
                    ])
                    ->default(fn () => SystemConfiguration::current()->active_cycle)
                    ->required(),

                Forms\Components\Select::make('day_of_week')
                    ->label('Hari')
                    ->options([
                        'Senin' => 'Senin',
                        'Selasa' => 'Selasa',
                        'Rabu' => 'Rabu',
                        'Kamis' => 'Kamis',
                        'Jumat' => 'Jumat',
                        'Sabtu' => 'Sabtu',
                    ])
                    ->required(),

                Forms\Components\TimePicker::make('start_time')
                    ->label('Jam Mulai')
                    ->seconds(false)
                    ->required(),

                Forms\Components\TimePicker::make('end_time')
                    ->label('Jam Selesai')
                    ->seconds(false)
                    ->after('start_time')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cycle_type')
                    ->label('Siklus')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Putaran 1' => 'info',
                        'Putaran 2' => 'primary',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('day_of_week')
                    ->label('Hari')
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('time_range')
                    ->label('Jam Pelajaran')
                    ->getStateUsing(fn (Schedule $record): string => substr($record->start_time, 0, 5) . ' - ' . substr($record->end_time, 0, 5) . ' WIB')
                    ->fontFamily('mono')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('subject')
                    ->label('Mata Pelajaran')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('teacher.fullname')
                    ->label('Guru Pengajar')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('schoolClass.class_name')
                    ->label('Kelas')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('room.room_name')
                    ->label('Ruangan')
                    ->placeholder('-'),
            ])
            ->defaultSort('day_of_week')
            ->filters([
                Tables\Filters\SelectFilter::make('cycle_type')
                    ->label('Filter Siklus')
                    ->options([
                        'Putaran 1' => 'Putaran 1',
                        'Putaran 2' => 'Putaran 2',
                    ]),

                Tables\Filters\SelectFilter::make('day_of_week')
                    ->label('Filter Hari')
                    ->options([
                        'Senin' => 'Senin',
                        'Selasa' => 'Selasa',
                        'Rabu' => 'Rabu',
                        'Kamis' => 'Kamis',
                        'Jumat' => 'Jumat',
                        'Sabtu' => 'Sabtu',
                    ]),

                Tables\Filters\SelectFilter::make('class_id')
                    ->label('Filter Kelas')
                    ->relationship('schoolClass', 'class_name'),

                Tables\Filters\SelectFilter::make('room_id')
                    ->label('Filter Ruangan')
                    ->relationship('room', 'room_name'),

                Tables\Filters\SelectFilter::make('teacher_id')
                    ->label('Filter Guru')
                    ->relationship('teacher', 'fullname'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSchedules::route('/'),
            'create' => Pages\CreateSchedule::route('/create'),
            'edit' => Pages\EditSchedule::route('/{record}/edit'),
        ];
    }
}
