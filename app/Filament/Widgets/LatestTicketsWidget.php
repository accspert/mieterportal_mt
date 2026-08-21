<?php

namespace App\Filament\Widgets;

use App\Models\Ticket;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestTicketsWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function getTableHeading(): ?string
    {
        return 'Neueste Meldungen & Schadensfälle';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Ticket::query()->latest()->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('title')->label('Titel')->searchable(),
                Tables\Columns\TextColumn::make('property.name')->label('Objekt')->default('-'),
                Tables\Columns\TextColumn::make('priority')
                    ->label('Dringlichkeit')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'urgent' => 'danger',
                        'high' => 'warning',
                        'medium' => 'info',
                        'low' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'open' => 'primary',
                        'in_progress' => 'warning',
                        'waiting' => 'gray',
                        'resolved', 'done' => 'success',
                        'closed' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')->label('Gemeldet am')->dateTime('d.m.Y H:i'),
            ]);
    }
}
