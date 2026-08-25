<?php

namespace App\Filament\Resources\TenantResource\RelationManagers;

use App\Models\Document;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $recordTitleAttribute = 'filename';

    protected static ?string $title = 'Dokumente & Verträge';

    protected static ?string $modelLabel = 'Dokument';

    protected static ?string $pluralModelLabel = 'Dokumente';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\FileUpload::make('path')
                ->label('Dokument-Datei (PDF, Word, Bild etc.)')
                ->disk('public')
                ->directory('documents')
                ->preserveFilenames()
                ->maxSize(25600)
                ->live()
                ->afterStateUpdated(function ($state, $set, $get) {
                    if ($state && ! $get('filename')) {
                        $clientOriginalName = is_string($state) ? basename($state) : (method_exists($state, 'getClientOriginalName') ? $state->getClientOriginalName() : basename($state));
                        $set('filename', $clientOriginalName);
                    }
                })
                ->required(),

            Forms\Components\TextInput::make('filename')
                ->label('Dokument-Titel / Bezeichnung')
                ->placeholder('z.B. Mietvertrag.pdf')
                ->required()
                ->maxLength(255),

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
                ->live()
                ->required(),

            Forms\Components\TextInput::make('amount')
                ->label('Rechnungsbetrag')
                ->numeric()
                ->prefix('€')
                ->placeholder('z.B. 450.00')
                ->visible(fn ($get) => in_array($get('category'), ['invoice', 'utility_bill']))
                ->helperText('Wird in die Zahlungsübersicht des Mieters übernommen.')
                ->nullable(),

            Forms\Components\DatePicker::make('due_date')
                ->label('Fälligkeitsdatum')
                ->default(now()->addDays(14))
                ->visible(fn ($get) => in_array($get('category'), ['invoice', 'utility_bill']))
                ->helperText('Fälligkeit für die Zahlungsübersicht.')
                ->nullable(),

            Forms\Components\TextInput::make('version')
                ->label('Version')
                ->numeric()
                ->default(1)
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('filename')
                    ->label('Dokumentenname')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('category')
                    ->label('Kategorie')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'contract' => 'Mietvertrag',
                        'invoice' => 'Rechnung',
                        'utility_bill' => 'Nebenkosten',
                        'protocol' => 'Protokoll',
                        default => 'Allgemein',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'contract' => 'success',
                        'invoice' => 'warning',
                        'utility_bill' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Betrag')
                    ->money('EUR')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Fälligkeit')
                    ->date()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('version')
                    ->label('Version')
                    ->formatStateUsing(fn ($state) => "v{$state}"),
                Tables\Columns\TextColumn::make('uploader.name')
                    ->label('Hochgeladen von')
                    ->default('System'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Datum')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Kategorie')
                    ->options([
                        'contract' => 'Mietvertrag',
                        'invoice' => 'Rechnung',
                        'utility_bill' => 'Nebenkosten',
                        'protocol' => 'Protokoll',
                        'general' => 'Allgemein',
                    ]),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Dokument hochladen')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['uploaded_by'] = auth()->id();

                        if (! empty($data['path']) && Storage::disk('public')->exists($data['path'])) {
                            $data['size'] = Storage::disk('public')->size($data['path']);
                            $data['mime_type'] = Storage::disk('public')->mimeType($data['path']) ?? 'application/pdf';
                        } else {
                            $data['mime_type'] = 'application/pdf';
                        }

                        return $data;
                    }),
            ])
            ->actions([
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn (Document $record): string => route('portal.documents.download', $record))
                    ->openUrlInNewTab(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }
}
