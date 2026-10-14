<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OwnerResource\Pages;
use App\Models\Owner;
use App\Services\PortalAccessService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;
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
                ->placeholder('+41 79 123 45 67')
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
                ->default('CH'),

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
                Tables\Columns\IconColumn::make('user_id')
                    ->label('Portal-Zugang')
                    ->boolean()
                    ->state(fn (Owner $record): bool => $record->user_id !== null),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->actions([
                Action::make('invitePortalAccess')
                    ->label('Zugang einladen')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->visible(fn (Owner $record): bool => $record->user_id === null)
                    ->modalHeading(fn (Owner $record): string => 'Portal-Zugang für '.(trim("{$record->first_name} {$record->last_name}") ?: $record->company_name))
                    ->modalDescription('Der Eigentümer erhält eine Einladung per E-Mail und legt sein Passwort selbst fest. Im Portal sieht er seine Liegenschaften, Einheiten, Dokumente und Abrechnungen.')
                    ->modalSubmitActionLabel('Einladung senden')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Vollständiger Name')
                            ->default(fn (Owner $record): string => trim("{$record->first_name} {$record->last_name}") ?: (string) $record->company_name)
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('E-Mail-Adresse (Login)')
                            ->email()
                            ->default(fn (Owner $record): ?string => $record->email)
                            ->required()
                            ->maxLength(255),
                    ])
                    ->action(function (Owner $record, array $data, PortalAccessService $portalAccess): void {
                        try {
                            $portalAccess->invite($record, $data['email'], $data['name']);
                        } catch (RuntimeException $exception) {
                            Notification::make()->title('Kein Zugang angelegt')->body($exception->getMessage())->danger()->send();

                            return;
                        }

                        Notification::make()
                            ->title('Einladung gesendet')
                            ->body("{$data['name']} erhält eine E-Mail an {$data['email']} und legt das Passwort selbst fest.")
                            ->success()
                            ->send();
                    }),
                Action::make('sendPasswordLink')
                    ->label('Passwort-Link senden')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->visible(fn (Owner $record): bool => $record->user_id !== null)
                    ->requiresConfirmation()
                    ->modalDescription('Der Eigentümer erhält eine E-Mail mit einem Link, über den er ein neues Passwort festlegt.')
                    ->modalSubmitActionLabel('Link senden')
                    ->action(function (Owner $record, PortalAccessService $portalAccess): void {
                        if ($record->user) {
                            $portalAccess->sendPasswordLink($record->user);

                            Notification::make()->title('Passwort-Link gesendet')->body("E-Mail an {$record->user->email} wurde versendet.")->success()->send();
                        }
                    }),
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->bulkActions([
                BulkAction::make('inviteSelectedPortalAccess')
                    ->label('Portal-Zugänge einladen')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Portal-Zugänge einladen')
                    ->modalDescription('Alle ausgewählten Eigentümer ohne Zugang erhalten eine Einladung per E-Mail und legen ihr Passwort selbst fest.')
                    ->action(function (Collection $records, PortalAccessService $portalAccess): void {
                        $invited = 0;
                        $skipped = [];

                        foreach ($records as $owner) {
                            if ($owner->user_id !== null) {
                                continue;
                            }

                            try {
                                $portalAccess->invite($owner);
                                $invited++;
                            } catch (RuntimeException $exception) {
                                $name = trim("{$owner->first_name} {$owner->last_name}") ?: $owner->company_name;
                                $skipped[] = "{$name}: {$exception->getMessage()}";
                            }
                        }

                        Notification::make()
                            ->title($invited > 0 ? "{$invited} Einladungen gesendet" : 'Keine Einladungen gesendet')
                            ->body($skipped === [] ? null : "Nicht eingeladen:\n".implode("\n", array_slice($skipped, 0, 5)).(count($skipped) > 5 ? "\n… und weitere" : ''))
                            ->status($skipped === [] ? 'success' : 'warning')
                            ->duration(20000)
                            ->send();
                    }),
                DeleteBulkAction::make(),
                RestoreBulkAction::make(),
                ForceDeleteBulkAction::make(),
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
