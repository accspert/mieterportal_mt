<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentResource\Pages;
use App\Models\Document;
use App\Models\Owner;
use App\Models\Property;
use App\Models\Renter;
use App\Models\Unit;
use BackedEnum;
use Filament\Actions\Action;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 100;

    protected static ?string $modelLabel = 'Dokument';

    protected static ?string $pluralModelLabel = 'Dokumente';

    protected static ?string $navigationLabel = 'Dokumentenablage';

    protected static string|UnitEnum|null $navigationGroup = 'Dokumente';

    public static function getModelLabel(): string
    {
        return __('Dokument');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Dokumente');
    }

    public static function getNavigationLabel(): string
    {
        return __('Dokumentenablage');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Dokumente');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['filename'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        /** @var Document $record */
        return "Dokument: {$record->filename}";
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var Document $record */
        $target = '-';
        if ($record->documentable instanceof Renter) {
            $target = "Mieter: {$record->documentable->first_name} {$record->documentable->last_name}";
        } elseif ($record->documentable instanceof Owner) {
            $target = 'Eigentümer: '.($record->documentable->company_name ?: "{$record->documentable->first_name} {$record->documentable->last_name}");
        } elseif ($record->documentable instanceof Property) {
            $target = "Liegenschaft: {$record->documentable->name}";
        } elseif ($record->documentable instanceof Unit) {
            $target = "Einheit: {$record->documentable->number}";
        }

        return [
            'Kategorie' => ucfirst($record->category ?? 'Allgemein'),
            'Zugeordnet an' => $target,
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\FileUpload::make('path')
                ->label('Dokument-Datei hochladen (PDF, Word, Bild etc.)')
                ->disk('public')
                ->directory('documents')
                ->maxSize(25600)
                ->live()
                ->afterStateUpdated(function ($state, $set, $get) {
                    if ($state && ! $get('filename')) {
                        $file = is_array($state) ? reset($state) : $state;
                        $rawName = '';
                        if (is_string($file)) {
                            $rawName = basename($file);
                        } elseif (is_object($file) && method_exists($file, 'getClientOriginalName')) {
                            $rawName = $file->getClientOriginalName();
                        }
                        if ($rawName) {
                            $cleanName = mb_convert_encoding((string) $rawName, 'UTF-8', 'UTF-8');
                            $cleanName = iconv('UTF-8', 'UTF-8//IGNORE', $cleanName) ?: $rawName;
                            $set('filename', $cleanName);
                        }
                    }
                })
                ->required(),

            Forms\Components\TextInput::make('filename')
                ->label('Dokument-Titel / Bezeichnung')
                ->placeholder('z.B. Mietvertrag_Wohnung_4B.pdf')
                ->required()
                ->maxLength(255),

            Forms\Components\Select::make('category')
                ->label('Kategorie')
                ->options([
                    'contract' => 'Mietvertrag / Vertrag',
                    'invoice' => 'Abrechnung / Rechnung',
                    'utility_bill' => 'Nebenkostenabrechnung',
                    'protocol' => 'Übergabeprotokoll',
                    'general' => 'Allgemeine Unterlage / Hausordnung',
                ])
                ->default('general')
                ->live()
                ->required(),

            Forms\Components\TextInput::make('amount')
                ->label('Rechnungsbetrag')
                ->numeric()
                ->prefix('€')
                ->placeholder('z.B. 450.00')
                ->visible(fn ($get) => in_array($get('category'), ['invoice', 'utility_bill']))
                ->helperText('Wird in die Rechnungsübersicht des Mieters übernommen.')
                ->nullable(),

            Forms\Components\DatePicker::make('due_date')
                ->label('Fälligkeitsdatum')
                ->default(now()->addDays(14))
                ->visible(fn ($get) => in_array($get('category'), ['invoice', 'utility_bill']))
                ->helperText('Fälligkeit für die Zahlungsübersicht.')
                ->nullable(),

            Forms\Components\Select::make('documentable_type')
                ->label('Zuordnungstyp (Wohin gehört das Dokument?)')
                ->options([
                    Renter::class => 'Mieter (z.B. Mietvertrag, Ausweise)',
                    Property::class => 'Liegenschaft (z.B. Energieausweis, Hausordnung)',
                    Unit::class => 'Mieteinheit / Wohnung (z.B. Grundriss, Protokoll)',
                    Owner::class => 'Eigentümer (z.B. Verwaltervertrag, Abrechnung)',
                ])
                ->live()
                ->required(),

            Forms\Components\Select::make('documentable_id')
                ->label('Zugeordneter Datensatz')
                ->options(function ($get) {
                    $type = $get('documentable_type');

                    return match ($type) {
                        Renter::class => Renter::all()->mapWithKeys(function ($t) {
                            $label = "{$t->first_name} {$t->last_name}".($t->email ? " ({$t->email})" : '');

                            return [$t->id => mb_convert_encoding($label, 'UTF-8', 'UTF-8')];
                        }),
                        Property::class => Property::all()->mapWithKeys(function ($p) {
                            $label = "{$p->name}".($p->city ? " ({$p->city})" : '');

                            return [$p->id => mb_convert_encoding($label, 'UTF-8', 'UTF-8')];
                        }),
                        Unit::class => Unit::with('property')->get()->mapWithKeys(function ($u) {
                            $label = "Einheit {$u->number}".($u->property ? " - {$u->property->name}" : '');

                            return [$u->id => mb_convert_encoding($label, 'UTF-8', 'UTF-8')];
                        }),
                        Owner::class => Owner::all()->mapWithKeys(function ($o) {
                            $label = $o->company_name ?: "{$o->first_name} {$o->last_name}";

                            return [$o->id => mb_convert_encoding($label, 'UTF-8', 'UTF-8')];
                        }),
                        default => [],
                    };
                })
                ->searchable()
                ->preload()
                ->required(),

            Forms\Components\TextInput::make('version')
                ->label('Dokument-Version')
                ->numeric()
                ->default(1)
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('filename')->label('Dokumentname / Titel')->searchable()->sortable(),
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
                Tables\Columns\TextColumn::make('documentable')
                    ->label('Zugeordnet an')
                    ->state(function (Document $record): string {
                        if (! $record->documentable) {
                            return '-';
                        }
                        if ($record->documentable instanceof Renter) {
                            return "Mieter: {$record->documentable->first_name} {$record->documentable->last_name}";
                        }
                        if ($record->documentable instanceof Property) {
                            return "Liegenschaft: {$record->documentable->name}";
                        }
                        if ($record->documentable instanceof Unit) {
                            return "Einheit: {$record->documentable->number}".($record->documentable->property ? " ({$record->documentable->property->name})" : '');
                        }
                        if ($record->documentable instanceof Owner) {
                            return 'Eigentümer: '.($record->documentable->company_name ?: "{$record->documentable->first_name} {$record->documentable->last_name}");
                        }

                        return class_basename($record->documentable_type);
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $q) use ($search) {
                            $q->where(function ($sub) use ($search) {
                                $sub->where('documentable_type', Renter::class)
                                    ->whereIn('documentable_id', Renter::where('first_name', 'like', "%{$search}%")
                                        ->orWhere('last_name', 'like', "%{$search}%")
                                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                                        ->orWhere('email', 'like', "%{$search}%")
                                        ->pluck('id'));
                            })
                                ->orWhere(function ($sub) use ($search) {
                                    $sub->where('documentable_type', Owner::class)
                                        ->whereIn('documentable_id', Owner::where('first_name', 'like', "%{$search}%")
                                            ->orWhere('last_name', 'like', "%{$search}%")
                                            ->orWhere('company_name', 'like', "%{$search}%")
                                            ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                                            ->orWhere('email', 'like', "%{$search}%")
                                            ->pluck('id'));
                                })
                                ->orWhere(function ($sub) use ($search) {
                                    $sub->where('documentable_type', Property::class)
                                        ->whereIn('documentable_id', Property::where('name', 'like', "%{$search}%")
                                            ->orWhere('city', 'like', "%{$search}%")
                                            ->pluck('id'));
                                })
                                ->orWhere(function ($sub) use ($search) {
                                    $sub->where('documentable_type', Unit::class)
                                        ->whereIn('documentable_id', Unit::where('number', 'like', "%{$search}%")->pluck('id'));
                                });
                        });
                    }),
                Tables\Columns\TextColumn::make('version')->label('Version')->sortable(),
                Tables\Columns\TextColumn::make('uploader.name')->label('Hochgeladen von')->default('System')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('Erstellt am')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Kategorie')
                    ->options([
                        'general' => 'Allgemein',
                        'contract' => 'Mietvertrag',
                        'invoice' => 'Rechnung',
                        'utility_bill' => 'Nebenkosten',
                        'protocol' => 'Protokoll',
                    ]),
                Tables\Filters\SelectFilter::make('renter_id')
                    ->label('Mieter')
                    ->options(fn () => Renter::all()->mapWithKeys(fn ($t) => [$t->id => "{$t->last_name}, {$t->first_name}".($t->email ? " ({$t->email})" : '')]))
                    ->searchable()
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        return $query->where('documentable_type', Renter::class)->where('documentable_id', $data['value']);
                    }),
                Tables\Filters\SelectFilter::make('owner_id')
                    ->label('Eigentümer')
                    ->options(fn () => Owner::all()->mapWithKeys(fn ($o) => [$o->id => ($o->company_name ?: "{$o->last_name}, {$o->first_name}")]))
                    ->searchable()
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        return $query->where('documentable_type', Owner::class)->where('documentable_id', $data['value']);
                    }),
                Tables\Filters\SelectFilter::make('property_id')
                    ->label('Liegenschaft')
                    ->options(fn () => Property::all()->mapWithKeys(fn ($p) => [$p->id => "{$p->name} ({$p->city})"]))
                    ->searchable()
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        return $query->where('documentable_type', Property::class)->where('documentable_id', $data['value']);
                    }),
                Tables\Filters\SelectFilter::make('unit_id')
                    ->label('Mieteinheit')
                    ->options(fn () => Unit::with('property')->get()->mapWithKeys(fn ($u) => [$u->id => "Einheit {$u->number}".($u->property ? " – {$u->property->name}" : '')]))
                    ->searchable()
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        return $query->where('documentable_type', Unit::class)->where('documentable_id', $data['value']);
                    }),
                TrashedFilter::make(),
            ])
            ->actions([
                Action::make('download')
                    ->label('Herunterladen')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn (Document $record): string => route('portal.documents.download', $record))
                    ->openUrlInNewTab(),
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
            'index' => Pages\ListDocuments::route('/'),
            'create' => Pages\CreateDocument::route('/create'),
            'edit' => Pages\EditDocument::route('/{record}/edit'),
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
