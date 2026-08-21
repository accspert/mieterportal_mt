<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaskResource\Pages;
use App\Models\Task;
use BackedEnum;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-check-circle';

    protected static ?int $navigationSort = 105;

    protected static ?string $modelLabel = 'Aufgabe';

    protected static ?string $pluralModelLabel = 'Aufgaben';

    protected static ?string $navigationLabel = 'Aufgaben';

    protected static string|UnitEnum|null $navigationGroup = 'Aufgaben & Kommunikation';

    public static function getModelLabel(): string
    {
        return __('Aufgabe');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Aufgaben');
    }

    public static function getNavigationLabel(): string
    {
        return __('Aufgaben');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Aufgaben & Kommunikation');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('title')
                ->label('Aufgabentitel')
                ->required()
                ->maxLength(255),

            Forms\Components\Textarea::make('description')
                ->label('Beschreibung / Details')
                ->rows(4)
                ->maxLength(65535)
                ->nullable(),

            Forms\Components\Select::make('priority')
                ->label('Priorität')
                ->options([
                    'low' => 'Niedrig',
                    'medium' => 'Mittel',
                    'high' => 'Hoch',
                    'urgent' => 'Dringend / Notfall',
                ])
                ->default('medium')
                ->required(),

            Forms\Components\Select::make('status')
                ->label('Status')
                ->options([
                    'pending' => 'Ausstehend',
                    'in_progress' => 'In Bearbeitung',
                    'completed' => 'Erledigt',
                    'cancelled' => 'Abgebrochen',
                ])
                ->default('pending')
                ->required(),

            Forms\Components\DateTimePicker::make('due_at')
                ->label('Fälligkeitsdatum')
                ->nullable(),

            Forms\Components\Select::make('assigned_to')
                ->relationship('assignee', 'name')
                ->searchable()
                ->preload()
                ->nullable()
                ->label('Zugewiesen an'),

            Forms\Components\Select::make('created_by')
                ->relationship('creator', 'name')
                ->searchable()
                ->preload()
                ->default(fn () => auth()->id())
                ->required()
                ->label('Erstellt von'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('title')->label('Aufgabe')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('priority')
                    ->label('Priorität')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'urgent' => 'Dringend',
                        'high' => 'Hoch',
                        'medium' => 'Mittel',
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
                        'pending' => 'Ausstehend',
                        'in_progress' => 'In Bearbeitung',
                        'completed' => 'Erledigt',
                        'cancelled' => 'Abgebrochen',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'in_progress' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('assignee.name')->label('Zugewiesen an')->sortable()->default('-'),
                Tables\Columns\TextColumn::make('due_at')->dateTime()->sortable()->label('Fällig am'),
                Tables\Columns\TextColumn::make('created_at')->label('Erstellt am')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('priority')
                    ->options([
                        'low' => 'Low',
                        'medium' => 'Medium',
                        'high' => 'High',
                        'urgent' => 'Urgent',
                    ]),
            ])
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
            'index' => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'edit' => Pages\EditTask::route('/{record}/edit'),
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
