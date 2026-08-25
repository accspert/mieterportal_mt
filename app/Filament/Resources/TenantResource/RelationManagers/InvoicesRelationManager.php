<?php

namespace App\Filament\Resources\TenantResource\RelationManagers;

use App\Models\Invoice;
use App\Models\Tenant;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    protected static ?string $recordTitleAttribute = 'invoice_number';

    protected static ?string $title = 'Rechnungen & Abrechnungen';

    protected static ?string $modelLabel = 'Rechnung';

    protected static ?string $pluralModelLabel = 'Rechnungen';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('invoice_number')
                ->label('Rechnungsnummer')
                ->default(fn () => 'RE-'.date('Y').'-'.strtoupper(substr(uniqid(), -6)))
                ->required()
                ->maxLength(255)
                ->unique(Invoice::class, 'invoice_number', ignoreRecord: true),

            Forms\Components\Select::make('unit_id')
                ->label('Mieteinheit / Wohnung')
                ->options(function () {
                    /** @var Tenant $tenant */
                    $tenant = $this->getOwnerRecord();

                    return $tenant->units()->with('property')->get()->mapWithKeys(function ($unit) {
                        return [$unit->id => "Einheit {$unit->number}".($unit->property ? " – {$unit->property->name}" : '')];
                    });
                })
                ->default(function () {
                    /** @var Tenant $tenant */
                    $tenant = $this->getOwnerRecord();

                    return $tenant->units()->first()?->id;
                })
                ->nullable()
                ->helperText('Optional: Wählen Sie die zugehörige Mieteinheit des Mieters.'),

            Forms\Components\TextInput::make('amount')
                ->label('Betrag')
                ->numeric()
                ->prefix('€')
                ->placeholder('z.B. 650.00')
                ->required(),

            Forms\Components\DatePicker::make('due_date')
                ->label('Fälligkeitsdatum')
                ->default(now()->addDays(14))
                ->required(),

            Forms\Components\Select::make('status')
                ->label('Zahlungsstatus')
                ->options([
                    'draft' => 'Entwurf',
                    'sent' => 'Offen / Ausstehend',
                    'paid' => 'Bezahlt',
                    'overdue' => 'Überfällig',
                    'cancelled' => 'Storniert',
                ])
                ->default('sent')
                ->required(),

            Forms\Components\FileUpload::make('pdf_path')
                ->label('Rechnungsbeleg / PDF hochladen (optional)')
                ->disk('public')
                ->directory('invoices')
                ->preserveFilenames()
                ->acceptedFileTypes(['application/pdf', 'image/*'])
                ->maxSize(25600)
                ->helperText('Optional: Laden Sie ein externes Rechnungs-PDF hoch. Falls leer, generiert das System den Beleg automatisch.')
                ->nullable(),

            Forms\Components\Textarea::make('notes')
                ->label('Bemerkungen / Verwendungszweck')
                ->rows(3)
                ->maxLength(65535)
                ->nullable(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Rechnungsnr.')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('unit.number')
                    ->label('Einheit')
                    ->badge()
                    ->color('gray')
                    ->default('-'),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Betrag')
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Fälligkeit')
                    ->date()
                    ->sortable(),
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
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
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
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Rechnung erstellen / Beleg hochladen')
                    ->icon('heroicon-o-plus-circle'),
            ])
            ->actions([
                Action::make('downloadPdf')
                    ->label('PDF Beleg')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->url(fn (Invoice $record): string => route('portal.invoices.download', $record))
                    ->openUrlInNewTab(),
                Action::make('markAsPaid')
                    ->label('Als Bezahlt')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Invoice $record) => $record->status !== 'paid')
                    ->action(function (Invoice $record) {
                        $record->update(['status' => 'paid']);
                        Notification::make()
                            ->title('Rechnung als bezahlt markiert')
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }
}
