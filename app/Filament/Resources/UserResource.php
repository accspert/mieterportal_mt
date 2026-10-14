<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Services\PortalAccessService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;
use Spatie\Permission\Models\Role;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 100;

    protected static ?string $modelLabel = 'Benutzer';

    protected static ?string $pluralModelLabel = 'Benutzer';

    protected static ?string $navigationLabel = 'Benutzerkonten';

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    public static function getModelLabel(): string
    {
        return __('Benutzer');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Benutzer');
    }

    public static function getNavigationLabel(): string
    {
        return __('Benutzerkonten');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Administration');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('name')
                ->label('Vollständiger Name')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('email')
                ->label('E-Mail-Adresse')
                ->email()
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),

            Forms\Components\Select::make('roles')
                ->label('Zugeordnete Rollen')
                // New accounts here are staff; renters and owners are invited from their own records,
                // so that their account is linked to the renter/owner record.
                ->relationship('roles', 'name', modifyQueryUsing: fn (Builder $query, string $operation): Builder => $operation === 'create'
                    ? $query->whereIn('name', ['Administrator', 'Sachbearbeiter'])
                    : $query)
                ->multiple()
                ->preload()
                ->searchable()
                ->required()
                // The option list is not enforced by Filament: check every submitted role on the server.
                ->nestedRecursiveRules(
                    [fn (): In => Rule::in(Role::query()->whereIn('name', ['Administrator', 'Sachbearbeiter'])->pluck('id')->all())],
                    condition: fn (string $operation): bool => $operation === 'create',
                )
                ->helperText(fn (string $operation): ?string => $operation === 'create'
                    ? 'Die Person erhält eine Einladung per E-Mail und legt ihr Passwort selbst fest. Mieter und Eigentümer laden Sie unter «Mieter» bzw. «Eigentümer» ein.'
                    : null),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('name')->label('Name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->label('E-Mail')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Rollen')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Administrator' => 'danger',
                        'Sachbearbeiter' => 'info',
                        'Eigentümer' => 'warning',
                        'Mieter' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')->label('Registriert am')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->label('Rolle filtern')
                    ->relationship('roles', 'name'),
            ])
            ->actions([
                Action::make('sendPasswordLink')
                    ->label('Passwort-Link senden')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription('Die Person erhält eine E-Mail mit einem Link, über den sie ein neues Passwort festlegt.')
                    ->modalSubmitActionLabel('Link senden')
                    ->action(function (User $record, PortalAccessService $portalAccess): void {
                        $portalAccess->sendPasswordLink($record);

                        Notification::make()->title('Passwort-Link gesendet')->body("E-Mail an {$record->email} wurde versendet.")->success()->send();
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return $user->hasRole('Administrator');
    }
}
