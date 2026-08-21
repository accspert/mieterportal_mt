<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UnitResource\Pages;
use App\Models\Unit;
use BackedEnum;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class UnitResource extends Resource
{
    protected static ?string $model = Unit::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static ?int $navigationSort = 110;

    protected static ?string $modelLabel = 'Einheit';

    protected static ?string $pluralModelLabel = 'Einheiten';

    protected static ?string $navigationLabel = 'Einheiten';

    protected static string|UnitEnum|null $navigationGroup = 'Objekte';

    public static function getModelLabel(): string
    {
        return __('Einheit');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Einheiten');
    }

    public static function getNavigationLabel(): string
    {
        return __('Einheiten');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Objekte');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Select::make('property_id')->relationship('property', 'name')->searchable()->required()->label('Liegenschaft'),
            Forms\Components\TextInput::make('number')->required()->maxLength(50)->label('Einheit / Wohnungsnummer'),
            Forms\Components\TextInput::make('floor')->maxLength(50)->label('Etage / Stockwerk'),
            Forms\Components\TextInput::make('area')->numeric()->label('Wohnfläche (m²)'),
            Forms\Components\TextInput::make('rooms')->numeric()->label('Zimmeranzahl'),
            Forms\Components\Select::make('status')->options([
                'vacant' => 'Leerstehend',
                'occupied' => 'Vermietet',
                'maintenance' => 'In Renovierung',
                'inactive' => 'Inaktiv',
            ])->default('vacant')->label('Status'),
            Forms\Components\Select::make('current_tenant_id')->relationship('currentTenant', 'first_name')->searchable()->nullable()->label('Aktueller Mieter'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')->label('Einheit / Whg.')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('property.name')->label('Liegenschaft')->searchable(),
                Tables\Columns\TextColumn::make('area')->label('Wohnfläche (m²)'),
                Tables\Columns\TextColumn::make('rooms')->label('Zimmer'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'vacant' => 'Leerstehend',
                        'occupied' => 'Vermietet',
                        'maintenance' => 'In Renovierung',
                        'inactive' => 'Inaktiv',
                        default => $state ?? '-',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'vacant' => 'warning',
                        'occupied' => 'success',
                        'maintenance' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('currentTenant.first_name')->label('Mieter')->default('-'),
            ])
            ->filters([])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUnits::route('/'),
            'create' => Pages\CreateUnit::route('/create'),
            'edit' => Pages\EditUnit::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return $user->hasAnyRole(['Administrator', 'Sachbearbeiter']);
    }
}
