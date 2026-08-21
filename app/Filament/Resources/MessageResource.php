<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MessageResource\Pages;
use App\Models\Message;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class MessageResource extends Resource
{
    protected static ?string $model = Message::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?int $navigationSort = 110;

    protected static ?string $modelLabel = 'Nachricht';

    protected static ?string $pluralModelLabel = 'Nachrichten';

    protected static ?string $navigationLabel = 'Nachrichten & Chat';

    protected static string|UnitEnum|null $navigationGroup = 'Aufgaben & Kommunikation';

    public static function getModelLabel(): string
    {
        return __('Nachricht');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Nachrichten');
    }

    public static function getNavigationLabel(): string
    {
        return __('Nachrichten & Chat');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Aufgaben & Kommunikation');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('subject')
                ->label('Betreff')
                ->default('(Ohne Betreff)'),

            Forms\Components\Select::make('from_user_id')
                ->relationship('sender', 'name')
                ->searchable()
                ->preload()
                ->label('Absender'),

            Forms\Components\Select::make('to_user_id')
                ->relationship('recipient', 'name')
                ->searchable()
                ->preload()
                ->nullable()
                ->label('Empfänger'),

            Forms\Components\DateTimePicker::make('created_at')
                ->label('Gesendet am'),

            Forms\Components\DateTimePicker::make('read_at')
                ->label('Gelesen am'),

            Forms\Components\TextInput::make('thread_id')
                ->label('Thread-ID'),

            Forms\Components\Textarea::make('body')
                ->label('Nachrichtentext')
                ->rows(6)
                ->columnSpanFull(),
        ]);
    }

    public static function getReplyAction(): Action
    {
        return Action::make('reply')
            ->label('Antworten')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('primary')
            ->button()
            ->modalHeading(fn (Message $record): string => 'Antwort an '.($record->sender?->name ?? 'Absender'))
            ->modalDescription(fn (Message $record): string => 'Originalnachricht vom '.$record->created_at->format('d.m.Y H:i').' Uhr')
            ->form([
                Forms\Components\Placeholder::make('original_sender')
                    ->label('Absender')
                    ->content(fn (Message $record): string => ($record->sender?->name ?? 'Unbekannt').' ('.($record->sender?->email ?? '-').')'),
                Forms\Components\Placeholder::make('original_message')
                    ->label('Originalnachricht')
                    ->content(fn (Message $record): string => $record->body),
                Forms\Components\TextInput::make('subject')
                    ->label('Betreff der Antwort')
                    ->default(fn (Message $record): string => str_starts_with($record->subject ?? '', 'Re: ') ? ($record->subject ?? '') : 'Re: '.($record->subject ?: 'Nachricht'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('body')
                    ->label('Ihre Antwort')
                    ->placeholder('Geben Sie hier Ihre Antwort ein...')
                    ->rows(6)
                    ->required()
                    ->maxLength(65535),
            ])
            ->action(function (Message $record, array $data): void {
                $threadId = $record->thread_id ?: ('thread_'.$record->id);
                if (! $record->thread_id) {
                    $record->update(['thread_id' => $threadId]);
                }

                if (! $record->read_at) {
                    $record->update(['read_at' => now()]);
                }

                Message::create([
                    'thread_id' => $threadId,
                    'from_user_id' => auth()->id(),
                    'to_user_id' => $record->from_user_id,
                    'subject' => $data['subject'],
                    'body' => $data['body'],
                ]);

                Notification::make()
                    ->title('Antwort erfolgreich gesendet')
                    ->body('Die Antwort wurde an '.($record->sender?->name ?? 'den Absender').' zugestellt.')
                    ->success()
                    ->send();
            });
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordAction(ViewAction::class)
            ->recordUrl(null)
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('subject')->label('Betreff')->searchable()->sortable()->default('(Ohne Betreff)'),
                Tables\Columns\TextColumn::make('sender.name')->label('Absender')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('recipient.name')->label('Empfänger')->searchable()->sortable()->default('-'),
                Tables\Columns\TextColumn::make('read_at')->dateTime()->sortable()->label('Gelesen am'),
                Tables\Columns\TextColumn::make('created_at')->label('Gesendet am')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('unread')
                    ->label('Nur ungelesene Nachrichten')
                    ->query(fn ($query) => $query->whereNull('read_at')),
            ])
            ->actions([
                static::getReplyAction(),
                ViewAction::make()
                    ->label('Details')
                    ->modalHeading('Nachrichtendetails')
                    ->extraModalActions([
                        static::getReplyAction(),
                    ]),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMessages::route('/'),
            'create' => Pages\CreateMessage::route('/create'),
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
