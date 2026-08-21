<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 100;

    protected static ?string $modelLabel = 'Benutzer';

    protected static ?string $pluralModelLabel = 'Benutzer';

    protected static ?string $navigationLabel = 'Benutzerkonten';

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    public static function getModelLabel(): string
    {
        return __('Benutzer');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Benutzer');
    }

    public static function getNavigationLabel(): string
    {
        return __('Benutzerkonten');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Administration');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('name')
                ->label('Vollständiger Name')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('email')
                ->label('E-Mail-Adresse')
                ->email()
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),

            Forms\Components\TextInput::make('password')
                ->label('Passwort')
                ->password()
                ->revealable()
                ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                ->dehydrated(fn ($state) => filled($state))
                ->required(fn (string $context): bool => $context === 'create')
                ->minLength(8)
                ->maxLength(255)
                ->helperText('Mindestens 8 Zeichen. Beim Bearbeiten leer lassen, um das bisherige Passwort beizubehalten.'),

            Forms\Components\Select::make('roles')
                ->label('Zugeordnete Rollen')
                ->relationship('roles', 'name')
                ->multiple()
                ->preload()
                ->searchable()
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('name')->label('Name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->label('E-Mail')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Rollen')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Administrator' => 'danger',
                        'Sachbearbeiter' => 'info',
                        'Eigentümer' => 'warning',
                        'Mieter' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')->label('Registriert am')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->label('Rolle filtern')
                    ->relationship('roles', 'name'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return $user->hasRole('Administrator');
    }
}
