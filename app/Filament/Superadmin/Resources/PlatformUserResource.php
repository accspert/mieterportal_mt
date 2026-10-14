<?php

namespace App\Filament\Superadmin\Resources;

use App\Filament\Superadmin\Resources\PlatformUserResource\Pages;
use App\Models\User;
use App\Services\ImpersonationService;
use App\Services\PortalAccessService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * All user accounts of the platform, across every property management.
 */
class PlatformUserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'benutzer';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'email';

    protected static ?string $modelLabel = 'Benutzer';

    protected static ?string $pluralModelLabel = 'Benutzer';

    protected static ?string $navigationLabel = 'Benutzer';

    public static function getEloquentQuery(): Builder
    {
        // Explicitly platform-wide, independent of any active property management.
        return parent::getEloquentQuery()->withoutGlobalScopes()->with(['companyTenant', 'roles']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('name')->label('Name')->required()->maxLength(255),
            Forms\Components\TextInput::make('email')
                ->label('E-Mail-Adresse')
                ->email()
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true)
                ->dehydrateStateUsing(fn (string $state): string => mb_strtolower(trim($state))),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->label('E-Mail')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('companyTenant.company_name')->label('Verwaltung')->default('Plattform')->sortable(),
                Tables\Columns\TextColumn::make('roles.name')->label('Rollen')->badge(),
                Tables\Columns\IconColumn::make('is_super_admin')->label('Superadmin')->boolean(),
                Tables\Columns\TextColumn::make('created_at')->label('Angelegt')->dateTime('d.m.Y')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('company_tenant_id')
                    ->label('Verwaltung')
                    ->relationship('companyTenant', 'company_name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_super_admin')->label('Superadmin'),
            ])
            ->actions([
                Action::make('impersonate')
                    ->label('Als Benutzer anmelden')
                    ->icon('heroicon-o-arrow-right-end-on-rectangle')
                    ->color('gray')
                    ->visible(fn (User $record): bool => ! $record->isSuperAdmin())
                    ->requiresConfirmation()
                    ->modalDescription('Sie arbeiten im Konto dieses Benutzers, z. B. für Support. Über den Hinweis oben auf der Seite kehren Sie zurück.')
                    ->action(function (User $record, ImpersonationService $impersonation) {
                        try {
                            return redirect()->to($impersonation->start(auth()->user(), $record));
                        } catch (RuntimeException $exception) {
                            Notification::make()->title($exception->getMessage())->danger()->send();

                            return null;
                        }
                    }),
                Action::make('sendPasswordLink')
                    ->label('Passwort-Link senden')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription('Die Person erhält eine E-Mail mit einem Link, über den sie ein neues Passwort festlegt.')
                    ->action(function (User $record, PortalAccessService $portalAccess): void {
                        $portalAccess->sendPasswordLink($record);

                        Notification::make()->title('Passwort-Link gesendet')->body("E-Mail an {$record->email} wurde versendet.")->success()->send();
                    }),
                Action::make('resetMultiFactorAuthentication')
                    ->label('Zwei-Faktor zurücksetzen')
                    ->icon('heroicon-o-device-phone-mobile')
                    ->color('warning')
                    ->visible(fn (User $record): bool => $record->hasMultiFactorAuthentication())
                    ->requiresConfirmation()
                    ->modalHeading(fn (User $record): string => "Zwei-Faktor-Anmeldung von {$record->email} zurücksetzen?")
                    ->modalDescription('Zum Beispiel bei verlorenem Smartphone oder gelöschtem Eintrag in der Authenticator-App. Die Person meldet sich danach nur mit dem Passwort an und richtet den zweiten Faktor beim nächsten Besuch des Verwaltungsbereichs neu ein. Bitte vorher sicherstellen, dass die Anfrage wirklich von dieser Person stammt.')
                    ->modalSubmitActionLabel('Zurücksetzen')
                    ->action(function (User $record): void {
                        $record->forceFill([
                            'app_authentication_secret' => null,
                            'app_authentication_recovery_codes' => null,
                            'has_email_authentication' => false,
                        ])->save();

                        Log::info('Multi-factor authentication reset by super admin', ['super_admin' => auth()->id(), 'user' => $record->id]);

                        Notification::make()->title('Zwei-Faktor-Anmeldung zurückgesetzt')->body("{$record->email} richtet sie beim nächsten Login neu ein.")->success()->send();
                    }),
                Action::make('toggleSuperAdmin')
                    ->label(fn (User $record): string => $record->isSuperAdmin() ? 'Superadmin entziehen' : 'Zum Superadmin machen')
                    ->icon('heroicon-o-shield-check')
                    ->color('danger')
                    ->visible(fn (User $record): bool => $record->isNot(auth()->user()))
                    ->requiresConfirmation()
                    ->modalDescription(fn (User $record): string => $record->isSuperAdmin()
                        ? 'Die Person verliert den Zugang zum Backoffice.'
                        : 'Die Person erhält vollen Zugriff auf alle Verwaltungen und Benutzer der Plattform.')
                    ->action(function (User $record): void {
                        $record->forceFill(['is_super_admin' => ! $record->isSuperAdmin()])->save();

                        Notification::make()->title($record->isSuperAdmin() ? 'Superadmin-Rechte vergeben' : 'Superadmin-Rechte entzogen')->success()->send();
                    }),
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (User $record): bool => $record->isNot(auth()->user())
                        && ! ($record->isSuperAdmin() && User::withoutGlobalScopes()->where('is_super_admin', true)->count() <= 1)),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlatformUsers::route('/'),
            'edit' => Pages\EditPlatformUser::route('/{record}/edit'),
        ];
    }
}
