<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TicketResource\Pages;
use App\Models\Ticket;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-ticket';

    protected static ?int $navigationSort = 100;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $modelLabel = 'Ticket';

    protected static ?string $pluralModelLabel = 'Tickets & Schadensmeldungen';

    protected static ?string $navigationLabel = 'Tickets & Meldungen';

    protected static string|UnitEnum|null $navigationGroup = 'Tickets';

    public static function getModelLabel(): string
    {
        return __('Ticket');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Tickets & Schadensmeldungen');
    }

    public static function getNavigationLabel(): string
    {
        return __('Tickets & Meldungen');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Tickets');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'description'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        /** @var Ticket $record */
        return "Ticket #{$record->id}: {$record->title}";
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var Ticket $record */
        return [
            'Status' => $record->status,
            'Dringlichkeit' => $record->priority,
            'Objekt' => $record->property?->name ?? '-',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('title')
                ->label('Titel / Betreff')
                ->required()
                ->maxLength(255),

            Forms\Components\Textarea::make('description')
                ->label('Beschreibung des Schadens / Anliegens')
                ->required()
                ->rows(4)
                ->maxLength(65535),

            Forms\Components\Select::make('category')
                ->label('Kategorie')
                ->options([
                    'general' => 'Allgemein',
                    'repair' => 'Reparatur / Handwerker',
                    'billing' => 'Abrechnung / Finanzen',
                    'other' => 'Sonstiges',
                ])
                ->default('general')
                ->required(),

            Forms\Components\Select::make('priority')
                ->label('Dringlichkeit')
                ->options([
                    'low' => 'Niedrig',
                    'medium' => 'Normal / Mittel',
                    'high' => 'Hoch',
                    'urgent' => 'Notfall / Dringend',
                ])
                ->default('medium')
                ->required(),

            Forms\Components\Select::make('status')
                ->label('Bearbeitungsstatus')
                ->options([
                    'open' => 'Offen',
                    'in_progress' => 'In Bearbeitung',
                    'waiting' => 'Wartend auf Rückmeldung',
                    'resolved' => 'Gelöst',
                    'closed' => 'Geschlossen',
                ])
                ->default('open')
                ->required(),

            Forms\Components\Select::make('property_id')
                ->label('Liegenschaft')
                ->relationship('property', 'name')
                ->searchable()
                ->preload()
                ->nullable(),

            Forms\Components\Select::make('unit_id')
                ->label('Mieteinheit')
                ->relationship('unit', 'number')
                ->searchable()
                ->preload()
                ->nullable(),

            Forms\Components\Select::make('renter_id')
                ->label('Gemeldet von (Mieter)')
                ->relationship('renter', 'first_name')
                ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name}")
                ->searchable()
                ->preload()
                ->nullable(),

            Forms\Components\Select::make('assigned_to')
                ->label('Zugewiesener Sachbearbeiter')
                ->relationship('assignee', 'name')
                ->searchable()
                ->preload()
                ->nullable(),

            Forms\Components\DateTimePicker::make('closed_at')
                ->label('Abgeschlossen am')
                ->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('title')->label('Betreff / Schaden')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('property.name')->label('Liegenschaft / Gebäude')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('unit.number')
                    ->label('Wohnung / Einheit')
                    ->badge()
                    ->color('primary')
                    ->default('-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('renter.last_name')
                    ->label('Mietername')
                    ->formatStateUsing(fn ($record) => $record->renter ? "{$record->renter->first_name} {$record->renter->last_name}" : ($record->creator?->name ?? '-'))
                    ->description(fn ($record) => $record->renter?->phone ? "Tel: {$record->renter->phone}" : ($record->renter?->email ?? null))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('priority')
                    ->label('Priorität')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'urgent' => 'Notfall',
                        'high' => 'Hoch',
                        'medium' => 'Normal',
                        'low' => 'Niedrig',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'urgent' => 'danger',
                        'high' => 'warning',
                        'medium' => 'info',
                        'low' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'open' => 'Offen',
                        'in_progress' => 'In Bearbeitung',
                        'waiting' => 'Wartend',
                        'resolved' => 'Gelöst',
                        'closed' => 'Geschlossen',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'open' => 'warning',
                        'in_progress' => 'info',
                        'waiting' => 'gray',
                        'resolved' => 'success',
                        'closed' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('assignee.name')
                    ->label('Zuständig')
                    ->badge()
                    ->color('info')
                    ->default('Nicht zugewiesen')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('Erstellt am')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\Filter::make('my_tickets')
                    ->label('Nur meine zugewiesenen Tickets')
                    ->query(fn (Builder $query): Builder => $query->where('assigned_to', auth()->id())),
                Tables\Filters\SelectFilter::make('assigned_to')
                    ->label('Sachbearbeiter')
                    ->relationship('assignee', 'name'),
                Tables\Filters\SelectFilter::make('property_id')
                    ->label('Liegenschaft')
                    ->relationship('property', 'name'),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'open' => 'Offen',
                        'in_progress' => 'In Bearbeitung',
                        'waiting' => 'Wartet',
                        'resolved' => 'Gelöst',
                        'closed' => 'Geschlossen',
                    ]),
                Tables\Filters\SelectFilter::make('priority')
                    ->label('Dringlichkeit')
                    ->options([
                        'urgent' => 'Notfall / Dringend',
                        'high' => 'Hoch',
                        'medium' => 'Normal',
                        'low' => 'Niedrig',
                    ]),
                Tables\Filters\SelectFilter::make('category')
                    ->label('Kategorie')
                    ->options([
                        'repair' => 'Reparatur / Handwerker',
                        'general' => 'Allgemein',
                        'billing' => 'Abrechnung',
                        'other' => 'Sonstiges',
                    ]),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->headerActions([
                Action::make('exportCsv')
                    ->label('CSV Export')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function (CsvExportService $exportService) {
                        $tickets = Ticket::with(['property', 'tenant', 'assignee'])->latest()->get();
                        $headers = ['Ticket ID', 'Titel', 'Kategorie', 'Dringlichkeit', 'Status', 'Objekt', 'Mieter', 'Zustaendig', 'Erstellt am'];
                        $rows = $tickets->map(function ($ticket) {
                            return [
                                $ticket->id,
                                $ticket->title,
                                $ticket->category,
                                $ticket->priority,
                                $ticket->status,
                                $ticket->property?->name ?? '-',
                                $ticket->tenant ? "{$ticket->tenant->first_name} {$ticket->tenant->last_name}" : '-',
                                $ticket->assignee?->name ?? '-',
                                $ticket->created_at->format('d.m.Y H:i'),
                            ];
                        });

                        return $exportService->streamCsv('tickets_export_'.date('Y-m-d').'.csv', $headers, $rows);
                    }),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->bulkActions([
                BulkAction::make('updateStatus')
                    ->label('Status ändern')
                    ->icon('heroicon-o-arrow-path')
                    ->color('primary')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Neuer Status')
                            ->options([
                                'open' => 'Offen',
                                'in_progress' => 'In Bearbeitung',
                                'waiting' => 'Wartet auf Rückmeldung',
                                'resolved' => 'Gelöst',
                                'closed' => 'Geschlossen',
                            ])
                            ->required(),
                    ])
                    ->action(function (Collection $records, array $data) {
                        $records->each(fn (Ticket $t) => $t->update(['status' => $data['status']]));
                        Notification::make()
                            ->title('Status aktualisiert')
                            ->body($records->count().' Tickets auf "'.$data['status'].'" gesetzt.')
                            ->success()
                            ->send();
                    }),
                BulkAction::make('assignStaff')
                    ->label('Sachbearbeiter zuweisen')
                    ->icon('heroicon-o-user-plus')
                    ->color('info')
                    ->schema([
                        Forms\Components\Select::make('assigned_to')
                            ->label('Sachbearbeiter')
                            ->options(User::role(['Administrator', 'Sachbearbeiter'])->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (Collection $records, array $data) {
                        $records->each(fn (Ticket $t) => $t->update(['assigned_to' => $data['assigned_to']]));
                        Notification::make()
                            ->title('Tickets zugewiesen')
                            ->body($records->count().' Tickets wurden zugewiesen.')
                            ->success()
                            ->send();
                    }),
                BulkAction::make('exportSelectedCsv')
                    ->label('Ausgewählte exportieren (CSV)')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function (Collection $records, CsvExportService $exportService) {
                        $headers = ['Ticket ID', 'Titel', 'Kategorie', 'Dringlichkeit', 'Status', 'Objekt', 'Mieter', 'Zustaendig', 'Erstellt am'];
                        $rows = $records->map(function ($ticket) {
                            return [
                                $ticket->id,
                                $ticket->title,
                                $ticket->category,
                                $ticket->priority,
                                $ticket->status,
                                $ticket->property?->name ?? '-',
                                $ticket->tenant ? "{$ticket->tenant->first_name} {$ticket->tenant->last_name}" : '-',
                                $ticket->assignee?->name ?? '-',
                                $ticket->created_at->format('d.m.Y H:i'),
                            ];
                        });

                        return $exportService->streamCsv('tickets_auswahl_'.date('Y-m-d').'.csv', $headers, $rows);
                    }),
                DeleteBulkAction::make(),
                RestoreBulkAction::make(),
                ForceDeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTickets::route('/'),
            'create' => Pages\CreateTicket::route('/create'),
            'edit' => Pages\EditTicket::route('/{record}/edit'),
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
