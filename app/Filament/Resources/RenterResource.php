<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RenterResource\Pages;
use App\Filament\Resources\RenterResource\RelationManagers\DocumentsRelationManager;
use App\Filament\Resources\RenterResource\RelationManagers\InvoicesRelationManager;
use App\Models\Renter;
use App\Models\Unit;
use App\Models\User;
use App\Services\CsvExportService;
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
            Forms\Components\TextInput::make('phone')->label('Telefon')->placeholder('+41 79 123 45 67')->maxLength(50)->nullable(),
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
                TrashedFilter::make(),
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
                Action::make('invitePortalAccess')
                    ->label('Zugang einladen')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->visible(fn (Renter $record): bool => $record->user_id === null)
                    ->modalHeading(fn (Renter $record): string => "Portal-Zugang für {$record->first_name} {$record->last_name}")
                    ->modalDescription('Der Mieter erhält eine Einladung per E-Mail und legt sein Passwort selbst fest. Niemand bei der Verwaltung sieht das Passwort.')
                    ->modalSubmitActionLabel('Einladung senden')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Vollständiger Name')
                            ->default(fn (Renter $record): string => "{$record->first_name} {$record->last_name}")
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('E-Mail-Adresse (Login)')
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
                    ])
                    ->action(function (Renter $record, array $data, PortalAccessService $portalAccess): void {
                        try {
                            $portalAccess->invite($record, $data['email'], $data['name']);
                        } catch (RuntimeException $exception) {
                            Notification::make()->title('Kein Zugang angelegt')->body($exception->getMessage())->danger()->send();

                            return;
                        }

                        $record->update(['manager_id' => $data['manager_id'] ?? $record->manager_id]);

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
                    ->visible(fn (Renter $record): bool => $record->user_id !== null)
                    ->requiresConfirmation()
                    ->modalHeading(fn (Renter $record): string => "Passwort-Link an {$record->first_name} {$record->last_name} senden")
                    ->modalDescription('Der Mieter erhält eine E-Mail mit einem Link, über den er ein neues Passwort festlegt.')
                    ->modalSubmitActionLabel('Link senden')
                    ->action(function (Renter $record, PortalAccessService $portalAccess): void {
                        if ($record->user) {
                            $portalAccess->sendPasswordLink($record->user);

                            Notification::make()->title('Passwort-Link gesendet')->body("E-Mail an {$record->user->email} wurde versendet.")->success()->send();
                        }
                    }),

                Action::make('uploadDocument')
                    ->label('Dokument hochladen')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('info')
                    ->modalHeading(fn (Renter $record): string => "Dokument für {$record->first_name} {$record->last_name} hochladen")
                    ->schema([
                        Forms\Components\FileUpload::make('path')
                            ->label('Dokument')
                            ->disk('public')
                            ->directory('documents')
                            ->preserveFilenames()
                            ->required(),
                        Forms\Components\TextInput::make('filename')
                            ->label('Titel')
                            ->required(),
                        Forms\Components\Select::make('category')
                            ->label('Kategorie')
                            ->options([
                                'contract' => 'Mietvertrag / Vertrag',
                                'invoice' => 'Abrechnung / Rechnung',
                                'utility_bill' => 'Nebenkostenabrechnung',
                                'protocol' => 'Übergabeprotokoll',
                                'general' => 'Allgemeine Unterlage',
                            ])
                            ->default('contract')
                            ->required(),
                    ])
                    ->action(function (Renter $record, array $data): void {
                        $record->documents()->create([
                            'filename' => $data['filename'],
                            'category' => $data['category'],
                            'path' => $data['path'],
                            'uploaded_by' => auth()->id(),
                            'size' => 0,
                            'mime_type' => 'application/pdf',
                        ]);

                        Notification::make()
                            ->title('Dokument erfolgreich hochgeladen')
                            ->success()
                            ->send();
                    }),

                Action::make('createInvoice')
                    ->label('Rechnung erstellen')
                    ->icon('heroicon-o-document-plus')
                    ->color('warning')
                    ->modalHeading(fn (Renter $record): string => "Rechnung für {$record->first_name} {$record->last_name} erstellen")
                    ->schema([
                        Forms\Components\TextInput::make('invoice_number')
                            ->label('Rechnungsnummer')
                            ->required(),
                        Forms\Components\Select::make('unit_id')
                            ->label('Mieteinheit')
                            ->options(fn (Renter $record) => $record->units()->pluck('number', 'id'))
                            ->nullable(),
                        Forms\Components\TextInput::make('amount')
                            ->label('Betrag')
                            ->numeric()
                            ->required(),
                        Forms\Components\DatePicker::make('due_date')
                            ->label('Fälligkeitsdatum')
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'draft' => 'Entwurf',
                                'sent' => 'Ausstehend',
                                'paid' => 'Bezahlt',
                                'overdue' => 'Überfällig',
                                'cancelled' => 'Storniert',
                            ])
                            ->default('sent')
                            ->required(),
                        Forms\Components\FileUpload::make('pdf_path')
                            ->label('Rechnungs-PDF (optional)')
                            ->disk('public')
                            ->directory('invoices')
                            ->nullable(),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notizen')
                            ->nullable(),
                    ])
                    ->action(function (Renter $record, array $data): void {
                        $record->invoices()->create([
                            'invoice_number' => $data['invoice_number'],
                            'unit_id' => $data['unit_id'] ?? null,
                            'amount' => $data['amount'],
                            'due_date' => $data['due_date'],
                            'status' => $data['status'],
                            'pdf_path' => $data['pdf_path'] ?? null,
                            'notes' => $data['notes'] ?? null,
                        ]);

                        Notification::make()
                            ->title('Rechnung erfolgreich erstellt')
                            ->success()
                            ->send();
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
                    ->modalDescription('Alle ausgewählten Mieter ohne Zugang erhalten eine Einladung per E-Mail und legen ihr Passwort selbst fest.')
                    ->action(function (Collection $records, PortalAccessService $portalAccess): void {
                        $invited = 0;
                        $skipped = [];

                        foreach ($records as $renter) {
                            if ($renter->user_id !== null) {
                                continue;
                            }

                            try {
                                $portalAccess->invite($renter);
                                $invited++;
                            } catch (RuntimeException $exception) {
                                $skipped[] = "{$renter->first_name} {$renter->last_name}: {$exception->getMessage()}";
                            }
                        }

                        Notification::make()
                            ->title($invited > 0 ? "{$invited} Einladungen gesendet" : 'Keine Einladungen gesendet')
                            ->body($skipped === [] ? null : "Nicht eingeladen:\n".implode("\n", array_slice($skipped, 0, 5)).(count($skipped) > 5 ? "\n… und weitere" : ''))
                            ->status($skipped === [] ? 'success' : 'warning')
                            ->duration(20000)
                            ->send();
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
            DocumentsRelationManager::class,
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
