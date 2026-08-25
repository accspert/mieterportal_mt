<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RenterResource\Pages;
use App\Filament\Resources\RenterResource\RelationManagers\InvoicesRelationManager;
use App\Models\Renter;
use App\Models\Unit;
use App\Models\User;
use App\Services\CsvExportService;
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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use UnitEnum;

class RenterResource extends Resource
{
    protected static ?string $model = Renter::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 200;

    protected static ?string $recordTitleAttribute = 'last_name';

    protected static ?string $modelLabel = 'Mieter';

    protected static ?string $pluralModelLabel = 'Mieter';

    protected static ?string $navigationLabel = 'Mieter';

    protected static string|UnitEnum|null $navigationGroup = 'Mieter & Kontakte';

    public static function getModelLabel(): string
    {
        return __('Mieter');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Mieter');
    }

    public static function getNavigationLabel(): string
    {
        return __('Mieter');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Mieter & Kontakte');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['first_name', 'last_name', 'email', 'phone'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        /** @var Renter $record */
        return "Mieter: {$record->first_name} {$record->last_name}";
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var Renter $record */
        $propertyNames = $record->units->pluck('property.name')->filter()->unique()->join(', ') ?: '-';
        $unitNumbers = $record->units->pluck('number')->join(', ') ?: '-';

        return [
            'Liegenschaft' => $propertyNames,
            'Einheit' => $unitNumbers,
            'E-Mail' => $record->email ?? '-',
            'Telefon' => $record->phone ?? '-',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('first_name')->label('Vorname')->required()->maxLength(255),
            Forms\Components\TextInput::make('last_name')->label('Nachname')->required()->maxLength(255),
            Forms\Components\TextInput::make('email')->label('E-Mail')->email()->maxLength(255)->nullable(),
            Forms\Components\TextInput::make('phone')->label('Telefon')->maxLength(50)->nullable(),
            Forms\Components\Select::make('manager_id')
                ->label('Zuständiger Sachbearbeiter')
                ->relationship('manager', 'name')
                ->options(fn () => User::role(['Sachbearbeiter', 'Administrator'])->pluck('name', 'id'))
                ->searchable()
                ->preload()
                ->nullable(),
            Forms\Components\Select::make('units')
                ->relationship(
                    name: 'units',
                    titleAttribute: 'number',
                    modifyQueryUsing: fn ($query) => $query->with('property')
                )
                ->getOptionLabelFromRecordUsing(fn ($record) => "Einheit {$record->number}".($record->property ? " – {$record->property->name} ({$record->property->city}, {$record->property->street})" : ''))
                ->getSearchResultsUsing(function (string $search): array {
                    return Unit::query()
                        ->with('property')
                        ->where(function ($q) use ($search) {
                            $q->where('number', 'like', "%{$search}%")
                                ->orWhereHas('property', function ($propQuery) use ($search) {
                                    $propQuery->where('name', 'like', "%{$search}%")
                                        ->orWhere('city', 'like', "%{$search}%")
                                        ->orWhere('street', 'like', "%{$search}%");
                                });
                        })
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn ($unit) => [
                            $unit->id => "Einheit {$unit->number}".($unit->property ? " – {$unit->property->name} ({$unit->property->city}, {$unit->property->street})" : ''),
                        ])
                        ->toArray();
                })
                ->getOptionLabelsUsing(function (array $values): array {
                    return Unit::query()
                        ->with('property')
                        ->whereIn('id', $values)
                        ->get()
                        ->mapWithKeys(fn ($unit) => [
                            $unit->id => "Einheit {$unit->number}".($unit->property ? " – {$unit->property->name} ({$unit->property->city}, {$unit->property->street})" : ''),
                        ])
                        ->toArray();
                })
                ->multiple()
                ->preload()
                ->searchable()
                ->label('Zugewiesene Wohnung(en) / Einheit(en)')
                ->helperText('Suche nach Einheitsnummer, Liegenschaftsname, Stadt oder Straße.'),
            Forms\Components\DatePicker::make('move_in')->label('Einzugsdatum')->nullable(),
            Forms\Components\DatePicker::make('move_out')->label('Auszugsdatum')->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('first_name')->label('Vorname')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('last_name')->label('Nachname')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('units.property.name')
                    ->label('Liegenschaft')
                    ->badge()
                    ->color('gray')
                    ->default('-')
                    ->searchable(),
                Tables\Columns\TextColumn::make('units.number')
                    ->label('Mieteinheit / Whg.')
                    ->badge()
                    ->color('primary')
                    ->default('-')
                    ->searchable(),
                Tables\Columns\TextColumn::make('manager.name')
                    ->label('Zuständiger Sachbearbeiter')
                    ->badge()
                    ->color('info')
                    ->default('-')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('phone')->label('Telefon')->default('-'),
                Tables\Columns\IconColumn::make('has_account')
                    ->label('Portal-Konto')
                    ->state(fn (Renter $record): bool => $record->user_id !== null)
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),
                Tables\Columns\TextColumn::make('move_in')->label('Mietbeginn')->date(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('manager_id')
                    ->label('Sachbearbeiter')
                    ->relationship('manager', 'name'),
                Tables\Filters\TernaryFilter::make('has_account')
                    ->label('Portal-Zugang vorhanden')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('user_id'),
                        false: fn ($query) => $query->whereNull('user_id'),
                    ),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->headerActions([
                Action::make('exportCsv')
                    ->label('Mieterliste (CSV)')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function (CsvExportService $exportService) {
                        $renters = Renter::with('units.property')->latest()->get();
                        $headers = ['ID', 'Vorname', 'Nachname', 'Liegenschaft', 'Mieteinheit', 'E-Mail', 'Telefon', 'Einzugsdatum', 'Auszugsdatum'];
                        $rows = $renters->map(function ($renter) {
                            return [
                                $renter->id,
                                $renter->first_name,
                                $renter->last_name,
                                $renter->units->pluck('property.name')->filter()->unique()->join(', ') ?: '-',
                                $renter->units->pluck('number')->join(', ') ?: '-',
                                $renter->email ?? '-',
                                $renter->phone ?? '-',
                                $renter->move_in?->format('d.m.Y') ?? '-',
                                $renter->move_out?->format('d.m.Y') ?? '-',
                            ];
                        });

                        return $exportService->streamCsv('mieter_export_'.date('Y-m-d').'.csv', $headers, $rows);
                    }),
            ])
            ->actions([
                Action::make('createUserAccount')
                    ->label('Konto anlegen')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->visible(fn (Renter $record): bool => $record->user_id === null)
                    ->modalHeading(fn (Renter $record): string => "Portal-Zugang für {$record->first_name} {$record->last_name} erstellen")
                    ->modalDescription('Ein Benutzerkonto wird angelegt und dem Mieter zugeordnet. Sie können den zuständigen Sachbearbeiter festlegen und das generierte Passwort kopieren.')
                    ->modalSubmitActionLabel('Benutzerkonto jetzt anlegen')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Vollständiger Name')
                            ->default(fn (Renter $record): string => "{$record->first_name} {$record->last_name}")
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('E-Mail-Adresse (Login-Benutzername)')
                            ->email()
                            ->default(fn (Renter $record): ?string => $record->email)
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('manager_id')
                            ->label('Zuständiger Sachbearbeiter')
                            ->options(fn () => User::role(['Sachbearbeiter', 'Administrator'])->pluck('name', 'id'))
                            ->default(fn (Renter $record) => $record->manager_id ?? $record->units->first()?->property?->manager_id ?? auth()->id())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Der ausgewählte Sachbearbeiter wird für diesen Mieter als Ansprechpartner und für Schadensmeldungen hinterlegt.'),
                        Forms\Components\TextInput::make('password')
                            ->label('Generiertes Initial-Passwort')
                            ->default(fn (): string => Str::password(10, true, true, false, false))
                            ->helperText('Dieses Passwort bitte kopieren und dem Mieter für die Erstanmeldung im Mieterportal übergeben.')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->action(function (Renter $record, array $data): void {
                        $user = User::where('email', $data['email'])->first();
                        if ($user) {
                            $user->update([
                                'name' => $data['name'],
                                'password' => Hash::make($data['password']),
                            ]);
                        } else {
                            $user = User::create([
                                'name' => $data['name'],
                                'email' => $data['email'],
                                'password' => Hash::make($data['password']),
                                'email_verified_at' => now(),
                            ]);
                        }

                        $user->assignRole('Mieter');

                        $record->update([
                            'user_id' => $user->id,
                            'email' => $data['email'],
                            'manager_id' => $data['manager_id'] ?? $record->manager_id,
                        ]);

                        Notification::make()
                            ->title('Benutzerkonto erfolgreich angelegt')
                            ->body("Zugangsdaten für {$data['name']}:\nE-Mail: {$data['email']}\nPasswort: {$data['password']}")
                            ->success()
                            ->duration(20000)
                            ->send();
                    }),

                Action::make('resetPassword')
                    ->label('Passwort neu')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->visible(fn (Renter $record): bool => $record->user_id !== null)
                    ->modalHeading(fn (Renter $record): string => "Neues Passwort für {$record->first_name} {$record->last_name} generieren")
                    ->modalDescription('Generiert ein neues Login-Passwort für das bestehende Portal-Konto des Mieters.')
                    ->modalSubmitActionLabel('Neues Passwort speichern')
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->label('Neues generiertes Passwort')
                            ->default(fn (): string => Str::password(10, true, true, false, false))
                            ->helperText('Kopieren Sie dieses neue Passwort für den Mieter.')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->action(function (Renter $record, array $data): void {
                        if ($record->user) {
                            $record->user->update([
                                'password' => Hash::make($data['password']),
                            ]);

                            Notification::make()
                                ->title('Passwort erfolgreich zurückgesetzt')
                                ->body("Neues Passwort für {$record->first_name} {$record->last_name} ({$record->email}):\n{$data['password']}")
                                ->success()
                                ->duration(20000)
                                ->send();
                        }
                    }),

                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->bulkActions([
                BulkAction::make('createSelectedUserAccounts')
                    ->label('Portal-Konten für Ausgewählte anlegen')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Portal-Konten generieren')
                    ->modalDescription('Für alle ausgewählten Mieter ohne bestehendes Benutzerkonto werden automatisch Zugangsdaten mit sicherem Initial-Passwort angelegt.')
                    ->action(function (Collection $records): void {
                        $created = 0;
                        $credentials = [];

                        foreach ($records as $renter) {
                            if (! $renter->user_id && $renter->email) {
                                $plainPassword = Str::password(10, true, true, false, false);
                                $name = "{$renter->first_name} {$renter->last_name}";

                                $user = User::where('email', $renter->email)->first();
                                if (! $user) {
                                    $user = User::create([
                                        'name' => $name,
                                        'email' => $renter->email,
                                        'password' => Hash::make($plainPassword),
                                        'email_verified_at' => now(),
                                    ]);
                                } else {
                                    $user->update([
                                        'password' => Hash::make($plainPassword),
                                    ]);
                                }

                                $user->assignRole('Mieter');
                                $renter->update(['user_id' => $user->id]);

                                $created++;
                                $credentials[] = "{$name} ({$renter->email}): {$plainPassword}";
                            }
                        }

                        if ($created > 0) {
                            Notification::make()
                                ->title("{$created} Benutzerkonten angelegt")
                                ->body("Erstellte Zugänge:\n".implode("\n", array_slice($credentials, 0, 5)).($created > 5 ? "\n... und weitere" : ''))
                                ->success()
                                ->duration(25000)
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Keine neuen Konten erstellt')
                                ->body('Alle ausgewählten Mieter besitzen bereits ein Konto oder haben keine E-Mail-Adresse hinterlegt.')
                                ->warning()
                                ->send();
                        }
                    }),
                BulkAction::make('exportSelectedCsv')
                    ->label('Ausgewählte exportieren (CSV)')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function (Collection $records, CsvExportService $exportService) {
                        $records->loadMissing('units.property');
                        $headers = ['ID', 'Vorname', 'Nachname', 'Liegenschaft', 'Mieteinheit', 'E-Mail', 'Telefon', 'Einzugsdatum', 'Auszugsdatum'];
                        $rows = $records->map(function ($renter) {
                            return [
                                $renter->id,
                                $renter->first_name,
                                $renter->last_name,
                                $renter->units->pluck('property.name')->filter()->unique()->join(', ') ?: '-',
                                $renter->units->pluck('number')->join(', ') ?: '-',
                                $renter->email ?? '-',
                                $renter->phone ?? '-',
                                $renter->move_in?->format('d.m.Y') ?? '-',
                                $renter->move_out?->format('d.m.Y') ?? '-',
                            ];
                        });

                        return $exportService->streamCsv('mieter_auswahl_'.date('Y-m-d').'.csv', $headers, $rows);
                    }),
                DeleteBulkAction::make(),
                RestoreBulkAction::make(),
                ForceDeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            InvoicesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRenters::route('/'),
            'create' => Pages\CreateRenter::route('/create'),
            'edit' => Pages\EditRenter::route('/{record}/edit'),
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
