<?php

namespace App\Filament\Superadmin\Resources;

use App\Filament\Superadmin\Resources\CompanyTenantResource\Pages;
use App\Models\CompanyTenant;
use App\Models\User;
use App\Services\ImpersonationService;
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
use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * All property managements of the platform.
 */
class CompanyTenantResource extends Resource
{
    protected static ?string $model = CompanyTenant::class;

    protected static ?string $slug = 'verwaltungen';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'company_name';

    protected static ?string $modelLabel = 'Verwaltung';

    protected static ?string $pluralModelLabel = 'Verwaltungen';

    protected static ?string $navigationLabel = 'Verwaltungen';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('company_name')->label('Name der Verwaltung')->required()->maxLength(255),
            Forms\Components\TextInput::make('email')->label('Kontakt-E-Mail')->email()->maxLength(255),
            Forms\Components\TextInput::make('phone')->label('Telefon')->maxLength(50),
            Forms\Components\Select::make('plan')
                ->label('Paket')
                ->options(collect(config('plans.plans'))->mapWithKeys(fn (array $plan, string $key): array => [$key => "{$plan['name']} – {$plan['price']}"]))
                ->required(),
            Forms\Components\Toggle::make('is_active')->label('Aktiv (Zugang für alle Benutzer der Verwaltung)')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('company_name')->label('Verwaltung')->searchable()->sortable()
                    ->description(fn (CompanyTenant $record): string => $record->id),
                Tables\Columns\TextColumn::make('email')->label('Kontakt')->searchable()->default('-'),
                Tables\Columns\TextColumn::make('plan')->label('Paket')->badge()
                    ->formatStateUsing(fn (?string $state): string => config("plans.plans.{$state}.name", (string) $state)),
                Tables\Columns\TextColumn::make('users_count')->label('Benutzer')->counts('users')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->label('Aktiv')->boolean(),
                Tables\Columns\TextColumn::make('created_at')->label('Registriert')->dateTime('d.m.Y')->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Status')->trueLabel('Aktiv')->falseLabel('Gesperrt'),
                Tables\Filters\SelectFilter::make('plan')->label('Paket')
                    ->options(collect(config('plans.plans'))->map(fn (array $plan): string => $plan['name'])),
            ])
            ->actions([
                Action::make('impersonateAdministrator')
                    ->label('Als Admin anmelden')
                    ->icon('heroicon-o-arrow-right-end-on-rectangle')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalDescription('Sie arbeiten im Konto des Administrators dieser Verwaltung, z. B. für Support. Über den Hinweis oben auf der Seite kehren Sie zurück.')
                    ->action(function (CompanyTenant $record, ImpersonationService $impersonation) {
                        $administrator = User::query()
                            ->where('company_tenant_id', $record->getKey())
                            ->whereHas('roles', fn ($query) => $query->where('name', 'Administrator'))
                            ->oldest()
                            ->first();

                        if ($administrator === null) {
                            Notification::make()->title('Diese Verwaltung hat keinen Administrator.')->danger()->send();

                            return null;
                        }

                        try {
                            return redirect()->to($impersonation->start(auth()->user(), $administrator));
                        } catch (RuntimeException $exception) {
                            Notification::make()->title($exception->getMessage())->danger()->send();

                            return null;
                        }
                    }),
                Action::make('toggleActive')
                    ->label(fn (CompanyTenant $record): string => $record->isActive() ? 'Sperren' : 'Aktivieren')
                    ->icon(fn (CompanyTenant $record): string => $record->isActive() ? 'heroicon-o-lock-closed' : 'heroicon-o-lock-open')
                    ->color(fn (CompanyTenant $record): string => $record->isActive() ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->modalDescription(fn (CompanyTenant $record): string => $record->isActive()
                        ? 'Alle Benutzer dieser Verwaltung verlieren sofort den Zugang zu Portal, Admin-Panel und API. Die Daten bleiben erhalten.'
                        : 'Alle Benutzer dieser Verwaltung erhalten wieder Zugang.')
                    ->action(function (CompanyTenant $record): void {
                        $record->update(['is_active' => ! $record->isActive()]);

                        Notification::make()->title($record->isActive() ? 'Verwaltung aktiviert' : 'Verwaltung gesperrt')->success()->send();
                    }),
                EditAction::make(),
                DeleteAction::make()
                    ->modalHeading(fn (CompanyTenant $record): string => "{$record->company_name} endgültig löschen?")
                    ->modalDescription('Die Verwaltung wird mit allen Benutzern, Liegenschaften, Tickets, Dokumenten, Rechnungen und hochgeladenen Dateien unwiderruflich gelöscht.')
                    ->after(function (CompanyTenant $record): void {
                        // Database records are removed by the foreign keys; the files live in the tenant's storage directory.
                        File::deleteDirectory(storage_path(config('tenancy.filesystem.suffix_base').$record->getKey()));
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanyTenants::route('/'),
            'edit' => Pages\EditCompanyTenant::route('/{record}/edit'),
        ];
    }
}
