<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AnnouncementResource\Pages;
use App\Models\Announcement;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class AnnouncementResource extends Resource
{
    protected static ?string $model = Announcement::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';

    protected static ?int $navigationSort = 115;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $modelLabel = 'Aushang';

    protected static ?string $pluralModelLabel = 'Gebäude-Aushänge';

    protected static ?string $navigationLabel = 'Aushänge & Infos';

    protected static string|UnitEnum|null $navigationGroup = 'Aufgaben & Kommunikation';

    public static function getModelLabel(): string
    {
        return __('Aushang');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Gebäude-Aushänge');
    }

    public static function getNavigationLabel(): string
    {
        return __('Aushänge & Infos');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Aufgaben & Kommunikation');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'body'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        /** @var Announcement $record */
        return "Mitteilung: {$record->title}";
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('title')
                ->label('Titel / Betreff')
                ->required()
                ->maxLength(255),

            Forms\Components\Select::make('type')
                ->label('Typ / Wichtigkeit')
                ->options([
                    'info' => 'Information (Blau)',
                    'warning' => 'Wichtiger Hinweis (Gelb)',
                    'urgent' => 'Dringend / Notfall (Rot)',
                ])
                ->default('info')
                ->required(),

            Forms\Components\Select::make('property_id')
                ->relationship('property', 'name')
                ->searchable()
                ->preload()
                ->nullable()
                ->label('Betroffenes Objekt (leer = Alle Liegenschaften)'),

            Forms\Components\Textarea::make('body')
                ->label('Inhalt / Mitteilungstext')
                ->required()
                ->rows(5)
                ->maxLength(65535),

            Forms\Components\DateTimePicker::make('starts_at')
                ->label('Anzeigen ab')
                ->default(now()),

            Forms\Components\DateTimePicker::make('ends_at')
                ->label('Anzeigen bis')
                ->nullable(),

            Forms\Components\Toggle::make('is_active')
                ->label('Aktiv')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('title')->label('Titel')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Typ')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'urgent' => 'danger',
                        'warning' => 'warning',
                        'info' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('property.name')
                    ->label('Ziel-Objekt')
                    ->default('Alle Liegenschaften')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean(),
                Tables\Columns\TextColumn::make('starts_at')->dateTime('d.m.Y H:i')->label('Von')->sortable(),
                Tables\Columns\TextColumn::make('ends_at')->dateTime('d.m.Y H:i')->label('Bis')->sortable()->default('-'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'info' => 'Information',
                        'warning' => 'Wichtig',
                        'urgent' => 'Dringend',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktivitätsstatus'),
                TrashedFilter::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
                RestoreBulkAction::make(),
                ForceDeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAnnouncements::route('/'),
            'create' => Pages\CreateAnnouncement::route('/create'),
            'edit' => Pages\EditAnnouncement::route('/{record}/edit'),
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
