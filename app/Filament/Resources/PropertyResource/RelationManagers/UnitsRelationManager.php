<?php

namespace App\Filament\Resources\PropertyResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class UnitsRelationManager extends RelationManager
{
    protected static string $relationship = 'units';

    protected static ?string $recordTitleAttribute = 'number';

    protected static ?string $title = 'Zugehörige Einheiten / Wohnungen';

    protected static ?string $modelLabel = 'Einheit';

    protected static ?string $pluralModelLabel = 'Einheiten';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('number')
                ->label('Einheit- / Wohnungsnummer')
                ->placeholder('z.B. Top 12 oder Whg. 3.OG')
                ->required()
                ->maxLength(50),

            Forms\Components\TextInput::make('floor')
                ->label('Etage / Stockwerk')
                ->placeholder('z.B. 2. OG')
                ->maxLength(50),

            Forms\Components\TextInput::make('area')
                ->label('Wohnfläche (m²)')
                ->numeric(),

            Forms\Components\TextInput::make('rooms')
                ->label('Zimmeranzahl')
                ->numeric(),

            Forms\Components\Select::make('status')
                ->label('Status')
                ->options([
                    'vacant' => 'Leerstehend',
                    'occupied' => 'Vermietet',
                    'maintenance' => 'In Renovierung',
                    'inactive' => 'Inaktiv',
                ])
                ->default('vacant')
                ->required(),

            Forms\Components\Select::make('current_tenant_id')
                ->relationship('currentTenant', 'first_name')
                ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name}".($record->email ? " ({$record->email})" : ''))
                ->searchable()
                ->preload()
                ->nullable()
                ->label('Aktueller Mieter (optional)'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')->label('Einheit / Whg.')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('floor')->label('Etage')->default('-'),
                Tables\Columns\TextColumn::make('area')->label('Wohnfläche (m²)')->default('-'),
                Tables\Columns\TextColumn::make('rooms')->label('Zimmer')->default('-'),
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
                Tables\Columns\TextColumn::make('currentTenant.first_name')
                    ->label('Mieter')
                    ->formatStateUsing(fn ($record) => $record->currentTenant ? "{$record->currentTenant->first_name} {$record->currentTenant->last_name}" : '-')
                    ->default('-'),
            ])
            ->filters([])
            ->headerActions([
                CreateAction::make()->label('Neue Einheit anlegen'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }
}
