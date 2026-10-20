<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Data Pengguna';

    protected static ?string $modelLabel = 'Pengguna';

    protected static ?string $pluralModelLabel = 'Data Pengguna';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('role_id')
                    ->label('Peran (Role)')
                    ->relationship('role', 'role_name')
                    ->required()
                    ->native(false),

                Forms\Components\Select::make('class_id')
                    ->label('Kelas (Khusus Siswa / Wali Kelas)')
                    ->relationship('schoolClass', 'class_name')
                    ->searchable()
                    ->preload()
                    ->native(false),

                Forms\Components\Select::make('parent_id')
                    ->label('Orang Tua (Khusus Siswa)')
                    ->relationship('parent', 'fullname')
                    ->searchable()
                    ->preload()
                    ->native(false),

                Forms\Components\TextInput::make('fullname')
                    ->label('Nama Lengkap')
                    ->required()
                    ->maxLength(100),

                Forms\Components\TextInput::make('username')
                    ->label('Username')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(50),

                Forms\Components\TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->required(fn (string $context): bool => $context === 'create')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('fullname')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('username')
                    ->label('Username')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono')
                    ->badge(),

                Tables\Columns\TextColumn::make('role.role_name')
                    ->label('Peran (Role)')
                    ->badge()
                    ->colors([
                        'danger' => 'super_admin',
                        'primary' => 'waka_kurikulum',
                        'info' => 'guru',
                        'warning' => 'wali_kelas',
                        'success' => 'guru_bk',
                        'gray' => fn ($state): bool => in_array($state, ['siswa', 'orang_tua']),
                    ]),

                Tables\Columns\TextColumn::make('schoolClass.class_name')
                    ->label('Kelas')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('parent.fullname')
                    ->label('Orang Tua')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role_id')
                    ->label('Filter Role')
                    ->relationship('role', 'role_name'),
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
