<?php

namespace App\Filament\Superadmin\Resources;

use App\Filament\Superadmin\Resources\TenantRegistrationResource\Pages;
use App\Models\TenantRegistration;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Self-service sign-ups, confirmed or still waiting for the e-mail confirmation.
 */
class TenantRegistrationResource extends Resource
{
    protected static ?string $model = TenantRegistration::class;

    protected static ?string $slug = 'registrierungen';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'Registrierung';

    protected static ?string $pluralModelLabel = 'Registrierungen';

    protected static ?string $navigationLabel = 'Registrierungen';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('company_name')->label('Verwaltung')->searchable(),
                Tables\Columns\TextColumn::make('admin_name')->label('Kontakt')->searchable(),
                Tables\Columns\TextColumn::make('email')->label('E-Mail')->searchable(),
                Tables\Columns\TextColumn::make('plan')->label('Paket')->badge()
                    ->formatStateUsing(fn (?string $state): string => config("plans.plans.{$state}.name", (string) $state)),
                Tables\Columns\IconColumn::make('confirmed_at')->label('Bestätigt')->boolean()
                    ->state(fn (TenantRegistration $record): bool => $record->isConfirmed()),
                Tables\Columns\TextColumn::make('created_at')->label('Eingegangen')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('confirmed_at')->label('Bestätigt')->nullable(),
            ])
            ->actions([
                DeleteAction::make()->visible(fn (TenantRegistration $record): bool => ! $record->isConfirmed()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenantRegistrations::route('/'),
        ];
    }
}
