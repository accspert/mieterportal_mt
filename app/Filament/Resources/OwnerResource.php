<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OwnerResource\Pages;
use App\Models\Owner;
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

class OwnerResource extends Resource
{
    protected static ?string $model = Owner::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?int $navigationSort = 90;

    protected static ?string $recordTitleAttribute = 'company_name';

    protected static ?string $modelLabel = 'Eigentümer';

    protected static ?string $pluralModelLabel = 'Eigentümer';

    protected static ?string $navigationLabel = 'Eigentümer';

    protected static string|UnitEnum|null $navigationGroup = 'Mieter & Kontakte';

    public static function getModelLabel(): string
    {
        return __('Eigentümer');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Eigentümer');
    }

    public static function getNavigationLabel(): string
    {
        return __('Eigentümer');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Mieter & Kontakte');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['company_name', 'first_name', 'last_name', 'email'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        /** @var Owner $record */
        return 'Eigentümer: '.($record->company_name ?: "{$record->first_name} {$record->last_name}");
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var Owner $record */
        return [
            'E-Mail' => $record->email ?? '-',
            'Ort' => $record->city ?? '-',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('company_name')
                ->label('Firma / Gesellschaft')
                ->maxLength(255),

            Forms\Components\TextInput::make('first_name')
                ->label('Vorname')
                ->maxLength(255),

            Forms\Components\TextInput::make('last_name')
                ->label('Nachname')
                ->maxLength(255),

            Forms\Components\TextInput::make('email')
                ->label('E-Mail')
                ->email()
                ->maxLength(255),

            Forms\Components\TextInput::make('phone')
                ->label('Telefon')
                ->maxLength(50),

            Forms\Components\TextInput::make('street')
                ->label('Straße & Hausnr.')
                ->maxLength(255),

            Forms\Components\TextInput::make('postal_code')
                ->label('PLZ')
                ->maxLength(20),

            Forms\Components\TextInput::make('city')
                ->label('Stadt')
                ->maxLength(100),

            Forms\Components\TextInput::make('country')
                ->label('Land')
                ->maxLength(10)
                ->default('DE'),

            Forms\Components\Select::make('properties')
                ->relationship('properties', 'name')
                ->multiple()
                ->searchable()
                ->preload()
                ->label('Zugeordnete Liegenschaften'),

            Forms\Components\Select::make('user_id')
                ->relationship('user', 'name')
                ->searchable()
                ->preload()
                ->nullable()
                ->label('Verknüpfter Benutzeraccount'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('company_name')->label('Firma / Name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('first_name')
                    ->label('Ansprechpartner')
                    ->formatStateUsing(fn ($record) => trim("{$record->first_name} {$record->last_name}") ?: '-'),
                Tables\Columns\TextColumn::make('email')->label('E-Mail')->searchable(),
                Tables\Columns\TextColumn::make('phone')->label('Telefon')->default('-'),
                Tables\Columns\TextColumn::make('city')->label('Stadt')->default('-'),
                Tables\Columns\TextColumn::make('properties_count')->counts('properties')->label('Liegenschaften'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
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
            'index' => Pages\ListOwners::route('/'),
            'create' => Pages\CreateOwner::route('/create'),
            'edit' => Pages\EditOwner::route('/{record}/edit'),
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
