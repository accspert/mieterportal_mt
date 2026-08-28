<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\Invoice;
use App\Models\Unit;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 120;

    protected static ?string $recordTitleAttribute = 'invoice_number';

    protected static ?string $modelLabel = 'Rechnung';

    protected static ?string $pluralModelLabel = 'Rechnungen';

    protected static ?string $navigationLabel = 'Rechnungen';

    protected static string|UnitEnum|null $navigationGroup = 'Finanzen';

    public static function getModelLabel(): string
    {
        return __('Rechnung');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Rechnungen');
    }

    public static function getNavigationLabel(): string
    {
        return __('Rechnungen');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Finanzen');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'invoice_number',
            'notes',
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        /** @var Invoice $record */
        return "Rechnung: {$record->invoice_number}";
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var Invoice $record */
        return [
            'Betrag' => number_format($record->amount, 2, ',', '.').' €',
            'Status' => $record->status,
            'Empfänger' => $record->renter ? "{$record->renter->first_name} {$record->renter->last_name}" : ($record->owner ? ($record->owner->company_name ?: "{$record->owner->first_name} {$record->owner->last_name}") : '-'),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('invoice_number')
                ->label('Rechnungsnummer')
                ->required()
                ->maxLength(255)
                ->unique(Invoice::class, 'invoice_number', ignoreRecord: true),

            Forms\Components\Select::make('renter_id')
                ->relationship('renter', 'first_name')
                ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name}")
                ->searchable()
                ->preload()
                ->nullable()
                ->label('Mieter (Empfänger)'),

            Forms\Components\Select::make('owner_id')
                ->relationship('owner', 'company_name')
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->company_name ?: "{$record->first_name} {$record->last_name}")
                ->searchable()
                ->preload()
                ->nullable()
                ->label('Eigentümer (Abrechnung)'),

            Forms\Components\Select::make('unit_id')
                ->relationship(
                    name: 'unit',
                    titleAttribute: 'number',
                    modifyQueryUsing: fn ($query) => $query->with('property')
                )
                ->getOptionLabelFromRecordUsing(fn ($record) => "Einheit {$record->number}".($record->property ? " – Liegenschaft {$record->property->name} ({$record->property->city})" : ''))
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
                            $unit->id => "Einheit {$unit->number}".($unit->property ? " – Liegenschaft {$unit->property->name} ({$unit->property->city})" : ''),
                        ])
                        ->toArray();
                })
                ->getOptionLabelUsing(function ($value): ?string {
                    $unit = Unit::with('property')->find($value);

                    return $unit ? "Einheit {$unit->number}".($unit->property ? " – Liegenschaft {$unit->property->name} ({$unit->property->city})" : '') : null;
                })
                ->searchable()
                ->preload()
                ->nullable()
                ->label('Mieteinheit'),

            Forms\Components\TextInput::make('amount')
                ->label('Betrag')
                ->numeric()
                ->prefix('€')
                ->required(),

            Forms\Components\DatePicker::make('due_date')
                ->label('Fälligkeitsdatum')
                ->required(),

            Forms\Components\Select::make('status')
                ->label('Zahlungsstatus')
                ->options([
                    'draft' => 'Entwurf',
                    'sent' => 'Versendet / Offen',
                    'paid' => 'Bezahlt',
                    'overdue' => 'Überfällig',
                    'cancelled' => 'Storniert',
                ])
                ->default('draft')
                ->required(),

            Forms\Components\FileUpload::make('pdf_path')
                ->label('Rechnungsbeleg / PDF hochladen (optional)')
                ->disk('public')
                ->directory('invoices')
                ->preserveFilenames()
                ->acceptedFileTypes(['application/pdf', 'image/*'])
                ->maxSize(25600)
                ->helperText('Optional: Laden Sie eine externe Rechnungs-PDF hoch. Falls leer, generiert das System den Beleg automatisch.')
                ->nullable(),

            Forms\Components\Textarea::make('notes')
                ->label('Bemerkungen / Buchungstext')
                ->rows(3)
                ->maxLength(65535)
                ->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')->label('Rechnungsnr.')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('recipient')
                    ->label('Empfänger / Partei')
                    ->state(function (Invoice $record): string {
                        if ($record->renter) {
                            return "Mieter: {$record->renter->first_name} {$record->renter->last_name}";
                        }
                        if ($record->owner) {
                            return 'Eigentümer: '.($record->owner->company_name ?: "{$record->owner->first_name} {$record->owner->last_name}");
                        }

                        return '-';
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $q) use ($search) {
                            $q->whereHas('renter', function (Builder $tQuery) use ($search) {
                                $tQuery->where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%")
                                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                                    ->orWhere('email', 'like', "%{$search}%");
                            })->orWhereHas('owner', function (Builder $oQuery) use ($search) {
                                $oQuery->where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%")
                                    ->orWhere('company_name', 'like', "%{$search}%")
                                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                                    ->orWhere('email', 'like', "%{$search}%");
                            });
                        });
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->leftJoin('renters', 'invoices.renter_id', '=', 'renters.id')
                            ->leftJoin('owners', 'invoices.owner_id', '=', 'owners.id')
                            ->orderByRaw("COALESCE(renters.last_name, owners.last_name, owners.company_name) {$direction}")
                            ->select('invoices.*');
                    }),
                Tables\Columns\TextColumn::make('unit.number')->label('Einheit')->searchable()->sortable()->default('-'),
                Tables\Columns\TextColumn::make('amount')->label('Betrag')->money('EUR')->sortable(),
                Tables\Columns\TextColumn::make('due_date')->label('Fälligkeit')->date()->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Entwurf',
                        'sent' => 'Ausstehend',
                        'paid' => 'Bezahlt',
                        'overdue' => 'Überfällig',
                        'cancelled' => 'Storniert',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'sent' => 'warning',
                        'paid' => 'success',
                        'overdue' => 'danger',
                        'cancelled' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('notes')->label('Bemerkung / Beleg')->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')->label('Erstellt am')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Zahlungsstatus')
                    ->options([
                        'draft' => 'Entwurf',
                        'sent' => 'Ausstehend',
                        'paid' => 'Bezahlt',
                        'overdue' => 'Überfällig',
                        'cancelled' => 'Storniert',
                    ]),
                Tables\Filters\SelectFilter::make('renter_id')
                    ->label('Mieter')
                    ->relationship('renter', 'last_name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->last_name}, {$record->first_name}".($record->email ? " ({$record->email})" : ''))
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('owner_id')
                    ->label('Eigentümer')
                    ->relationship('owner', 'company_name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->company_name ?: "{$record->last_name}, {$record->first_name}")
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('unit_id')
                    ->label('Mieteinheit')
                    ->relationship('unit', 'number')
                    ->getOptionLabelFromRecordUsing(fn ($record) => "Einheit {$record->number}".($record->property ? " – {$record->property->name}" : ''))
                    ->searchable()
                    ->preload(),
                TrashedFilter::make(),
            ])
            ->headerActions([
                Action::make('exportCsv')
                    ->label('CSV Export')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function (CsvExportService $exportService) {
                        $invoices = Invoice::with(['renter', 'owner', 'unit'])->latest('due_date')->get();
                        $headers = ['Rechnungsnummer', 'Empfaenger', 'Einheit', 'Betrag', 'Faelligkeitsdatum', 'Status'];
                        $rows = $invoices->map(function ($inv) {
                            $recipient = $inv->renter ? "{$inv->renter->first_name} {$inv->renter->last_name}" : ($inv->owner ? ($inv->owner->company_name ?: "{$inv->owner->first_name} {$inv->owner->last_name}") : '-');

                            return [
                                $inv->invoice_number,
                                $recipient,
                                $inv->unit?->number ?? '-',
                                number_format($inv->amount, 2, ',', '.').' €',
                                $inv->due_date?->format('d.m.Y') ?? '-',
                                $inv->status,
                            ];
                        });

                        return $exportService->streamCsv('rechnungen_export_'.date('Y-m-d').'.csv', $headers, $rows);
                    }),
            ])
            ->actions([
                Action::make('downloadPdf')
                    ->label('PDF Beleg')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->url(fn (Invoice $record): string => route('portal.invoices.download', $record))
                    ->openUrlInNewTab(),
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->bulkActions([
                BulkAction::make('markAsPaid')
                    ->label('Als Bezahlt markieren')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function (Collection $records) {
                        $records->each(fn (Invoice $invoice) => $invoice->update(['status' => 'paid']));
                        Notification::make()
                            ->title('Status aktualisiert')
                            ->body($records->count().' Rechnungen als bezahlt markiert.')
                            ->success()
                            ->send();
                    }),
                BulkAction::make('exportSelectedCsv')
                    ->label('Ausgewählte exportieren (CSV)')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function (Collection $records, CsvExportService $exportService) {
                        $headers = ['Rechnungsnummer', 'Empfaenger', 'Einheit', 'Betrag', 'Faelligkeitsdatum', 'Status'];
                        $rows = $records->map(function ($inv) {
                            $recipient = $inv->renter ? "{$inv->renter->first_name} {$inv->renter->last_name}" : ($inv->owner ? ($inv->owner->company_name ?: "{$inv->owner->first_name} {$inv->owner->last_name}") : '-');

                            return [
                                $inv->invoice_number,
                                $recipient,
                                $inv->unit?->number ?? '-',
                                number_format($inv->amount, 2, ',', '.').' €',
                                $inv->due_date?->format('d.m.Y') ?? '-',
                                $inv->status,
                            ];
                        });

                        return $exportService->streamCsv('rechnungen_auswahl_'.date('Y-m-d').'.csv', $headers, $rows);
                    }),
                DeleteBulkAction::make(),
                RestoreBulkAction::make(),
                ForceDeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
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
