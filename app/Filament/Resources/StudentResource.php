<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StudentResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Data Siswa';

    protected static ?string $modelLabel = 'Siswa';

    protected static ?string $pluralModelLabel = 'Data Siswa';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('role', fn ($q) => $q->where('role_name', 'siswa'));
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('class_id')
                    ->label('Kelas (Rombel)')
                    ->relationship('schoolClass', 'class_name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\Select::make('parent_id')
                    ->label('Orang Tua / Wali Murid')
                    ->relationship('parent', 'fullname', fn (Builder $query) => $query->whereHas('role', fn ($q) => $q->where('role_name', 'orang_tua')))
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->helperText('Pilih akun orang tua yang terhubung dengan siswa ini.'),

                Forms\Components\TextInput::make('fullname')
                    ->label('Nama Lengkap Siswa')
                    ->required()
                    ->maxLength(100),

                Forms\Components\TextInput::make('username')
                    ->label('NISN / Username')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(50),

                Forms\Components\TextInput::make('password')
                    ->label('Password Akun')
                    ->password()
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->required(fn (string $context): bool => $context === 'create')
                    ->helperText('Biarkan kosong jika tidak ingin mengubah password.')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('username')
                    ->label('NISN / Username')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono')
                    ->badge(),

                Tables\Columns\TextColumn::make('fullname')
                    ->label('Nama Lengkap Siswa')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('schoolClass.class_name')
                    ->label('Kelas')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('parent.fullname')
                    ->label('Orang Tua / Wali')
                    ->placeholder('-')
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('class_id')
                    ->label('Filter Kelas')
                    ->relationship('schoolClass', 'class_name'),
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
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
        ];
    }
}
