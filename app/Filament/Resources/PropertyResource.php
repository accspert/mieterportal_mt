<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PropertyResource\Pages;
use App\Filament\Resources\PropertyResource\RelationManagers\UnitsRelationManager;
use App\Models\Property;
use BackedEnum;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class PropertyResource extends Resource
{
    protected static ?string $model = Property::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office';

    protected static ?int $navigationSort = 100;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'Liegenschaft';

    protected static ?string $pluralModelLabel = 'Liegenschaften';

    protected static ?string $navigationLabel = 'Liegenschaften';

    protected static string|UnitEnum|null $navigationGroup = 'Objekte';

    public static function getModelLabel(): string
    {
        return __('Liegenschaft');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Liegenschaften');
    }

    public static function getNavigationLabel(): string
    {
        return __('Liegenschaften');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Objekte');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'street', 'city', 'postal_code'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        /** @var Property $record */
        return "Objekt: {$record->name}";
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var Property $record */
        return [
            'Adresse' => trim("{$record->street}, {$record->postal_code} {$record->city}", ', '),
            'Eigentümer' => $record->owner?->company_name ?: ($record->owner ? "{$record->owner->first_name} {$record->owner->last_name}" : '-'),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Select::make('owner_id')
                ->relationship('owner', 'first_name')
                ->getOptionLabelFromRecordUsing(function ($record) {
                    if ($record->company_name) {
                        return "{$record->company_name} ({$record->first_name} {$record->last_name})";
                    }

                    return "{$record->first_name} {$record->last_name}".($record->email ? " ({$record->email})" : '');
                })
                ->searchable(['first_name', 'last_name', 'company_name', 'email'])
                ->preload()
                ->nullable()
                ->label('Eigentümer'),

            Forms\Components\Select::make('manager_id')
                ->relationship('manager', 'name')
                ->options(fn () => \App\Models\User::role(['Sachbearbeiter', 'Administrator'])->pluck('name', 'id'))
                ->searchable()
                ->preload()
                ->label('Zuständiger Sachbearbeiter'),

            Forms\Components\TextInput::make('name')->label('Objektname')->required()->maxLength(255),
            Forms\Components\TextInput::make('street')->label('Straße & Hausnummer')->maxLength(255),
            Forms\Components\TextInput::make('postal_code')->label('Postleitzahl')->maxLength(20),
            Forms\Components\TextInput::make('city')->label('Stadt / Ort')->maxLength(100),
            Forms\Components\TextInput::make('country')->label('Land')->maxLength(10)->default('DE'),
            Forms\Components\Textarea::make('description')->label('Beschreibung / Notizen')->rows(4)->maxLength(65535),
            Forms\Components\TextInput::make('contact_person')->label('Ansprechpartner vor Ort (optional)')->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('name')->label('Objektname')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('city')->label('Stadt')->sortable(),
                Tables\Columns\TextColumn::make('owner.company_name')
                    ->label('Eigentümer')
                    ->formatStateUsing(fn ($record) => $record->owner ? ($record->owner->company_name ?: "{$record->owner->first_name} {$record->owner->last_name}") : '-')
                    ->searchable(),
                Tables\Columns\TextColumn::make('manager.name')->label('Zuständiger Sachbearbeiter')->badge()->color('info')->default('-')->searchable(),
                Tables\Columns\TextColumn::make('created_at')->label('Erstellt')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('manager_id')
                    ->label('Sachbearbeiter')
                    ->relationship('manager', 'name'),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            UnitsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProperties::route('/'),
            'create' => Pages\CreateProperty::route('/create'),
            'edit' => Pages\EditProperty::route('/{record}/edit'),
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
