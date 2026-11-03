<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JournalResource\Pages;
use App\Models\Journal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class JournalResource extends Resource
{
    protected static ?string $model = Journal::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Manajemen Akademik';

    protected static ?string $navigationLabel = 'Monitoring Jurnal';

    protected static ?string $modelLabel = 'Jurnal Mengajar';

    protected static ?string $pluralModelLabel = 'Monitoring Jurnal KBM';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Kegiatan Belajar Mengajar (KBM)')
                    ->schema([
                        Forms\Components\Placeholder::make('teacher')
                            ->label('Guru Pengajar')
                            ->content(fn (?Journal $record): string => $record?->schedule?->teacher?->fullname ?? '-'),

                        Forms\Components\Placeholder::make('subject')
                            ->label('Mata Pelajaran')
                            ->content(fn (?Journal $record): string => $record?->schedule?->subject ?? '-'),

                        Forms\Components\Placeholder::make('class')
                            ->label('Kelas')
                            ->content(fn (?Journal $record): string => $record?->schedule?->schoolClass?->class_name ?? '-'),

                        Forms\Components\Placeholder::make('room')
                            ->label('Ruangan')
                            ->content(fn (?Journal $record): string => $record?->schedule?->room?->room_name ?? '-'),

                        Forms\Components\Placeholder::make('date')
                            ->label('Tanggal KBM')
                            ->content(fn (?Journal $record): string => $record?->date ? date('d F Y', strtotime($record->date)) : '-'),

                        Forms\Components\Textarea::make('topic_description')
                            ->label('Topik / Uraian Pembelajaran')
                            ->disabled()
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('photo_path')
                            ->label('Foto Bukti KBM Real-time')
                            ->disk('public')
                            ->image()
                            ->disabled()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Rekapitulasi Presensi Siswa pada Sesi Ini')
                    ->schema([
                        Forms\Components\Repeater::make('attendances')
                            ->relationship('attendances')
                            ->schema([
                                Forms\Components\Placeholder::make('student_name')
                                    ->label('Nama Siswa')
                                    ->content(fn ($record) => $record?->student?->fullname ?? '-'),

                                Forms\Components\Placeholder::make('status')
                                    ->label('Status Kehadiran')
                                    ->content(fn ($record) => $record?->status ?? '-'),
                            ])
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->columns(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal KBM')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('schedule.teacher.fullname')
                    ->label('Guru Pengajar')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('schedule.subject')
                    ->label('Mata Pelajaran')
                    ->searchable(),

                Tables\Columns\TextColumn::make('schedule.schoolClass.class_name')
                    ->label('Kelas')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('schedule.room.room_name')
                    ->label('Ruangan')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('topic_description')
                    ->label('Topik Pembelajaran')
                    ->limit(35)
                    ->tooltip(fn (Journal $record): string => $record->topic_description),

                Tables\Columns\ImageColumn::make('photo_path')
                    ->label('Foto KBM')
                    ->disk('public')
                    ->square()
                    ->height(40)
                    ->width(40),

                Tables\Columns\TextColumn::make('attendances_summary')
                    ->label('Presensi Siswa')
                    ->getStateUsing(function (Journal $record): string {
                        $attendances = $record->attendances;
                        $h = $attendances->where('status', 'Hadir')->count();
                        $s = $attendances->where('status', 'Sakit')->count();
                        $i = $attendances->where('status', 'Izin')->count();
                        $a = $attendances->where('status', 'Alpha')->count();
                        return "H: {$h} | S: {$s} | I: {$i} | A: {$a}";
                    })
                    ->badge()
                    ->color('gray'),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('date_until')->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['date_from'], fn ($q, $date) => $q->whereDate('date', '>=', $date))
                            ->when($data['date_until'], fn ($q, $date) => $q->whereDate('date', '<=', $date));
                    }),

                Tables\Filters\SelectFilter::make('class_id')
                    ->label('Filter Kelas')
                    ->relationship('schedule.schoolClass', 'class_name'),

                Tables\Filters\SelectFilter::make('teacher_id')
                    ->label('Filter Guru')
                    ->relationship('schedule.teacher', 'fullname'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Detail KBM'),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJournals::route('/'),
            'view' => Pages\ViewJournal::route('/{record}'),
        ];
    }
}
