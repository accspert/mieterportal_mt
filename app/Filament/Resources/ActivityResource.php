<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityResource\Pages;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;
use UnitEnum;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 200;

    protected static ?string $modelLabel = 'Aktivität';

    protected static ?string $pluralModelLabel = 'Aktivitäten';

    protected static ?string $navigationLabel = 'Aktivitätsprotokoll';

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    public static function getModelLabel(): string
    {
        return __('Aktivität');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Aktivitäten');
    }

    public static function getNavigationLabel(): string
    {
        return __('Aktivitätsprotokoll');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Administration');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('description')
                ->label('Beschreibung')
                ->disabled(),

            Forms\Components\TextInput::make('event')
                ->label('Ereignis')
                ->disabled(),

            Forms\Components\TextInput::make('causer.name')
                ->label('Verursacher')
                ->disabled(),

            Forms\Components\TextInput::make('subject_type')
                ->label('Betroffenes Objekt')
                ->disabled(),

            Forms\Components\TextInput::make('subject_id')
                ->label('Objekt-ID')
                ->disabled(),

            Forms\Components\KeyValue::make('properties')
                ->label('Änderungsdetails / Attribute')
                ->disabled(),

            Forms\Components\DateTimePicker::make('created_at')
                ->label('Zeitpunkt')
                ->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('description')->label('Aktivität')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('event')
                    ->label('Ereignis')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('causer.name')
                    ->label('Benutzer')
                    ->searchable()
                    ->default('System / Gast'),
                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Bereich')
                    ->formatStateUsing(fn (?string $state) => class_basename($state ?? ''))
                    ->sortable(),
                Tables\Columns\TextColumn::make('subject_id')->label('Objekt-ID'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Zeitstempel')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('event')
                    ->label('Ereignis')
                    ->options([
                        'created' => 'Erstellt',
                        'updated' => 'Aktualisiert',
                        'deleted' => 'Gelöscht',
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivities::route('/'),
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
